"""Backend tests for AdminKit — Modul Perizinan (Laravel + Inertia).

Login as zulfame (Super Admin) via session cookies + XSRF-TOKEN header.
Only creates data with prefix 'qa' and cleans up after.
"""
import os
import re
from urllib.parse import unquote

import pytest
import requests
from dotenv import dotenv_values

env = dotenv_values("/app/adminkit/.env")
BASE_URL = (os.environ.get("APP_URL") or env.get("APP_URL") or "https://inertia-admin-panel.preview.emergentagent.com").rstrip("/")
USERNAME = "zulfame"
PASSWORD = "password"


def _xsrf(session):
    tok = session.cookies.get("XSRF-TOKEN")
    assert tok, "XSRF-TOKEN cookie missing"
    return unquote(tok)


def _inertia_headers(session):
    v = getattr(session, "_inertia_version", "")
    return {
        "X-XSRF-TOKEN": _xsrf(session),
        "X-Inertia": "true",
        "X-Inertia-Version": v,
        "X-Requested-With": "XMLHttpRequest",
        "Accept": "application/json",
        "Content-Type": "application/json",
        "Referer": f"{BASE_URL}/",
    }


@pytest.fixture(scope="session")
def session():
    s = requests.Session()
    r = s.get(f"{BASE_URL}/login", timeout=30)
    assert r.status_code == 200

    m = re.search(r'"version":"([^"]+)"', r.text)
    version = m.group(1) if m else ""
    s._inertia_version = version

    headers = _inertia_headers(s)
    headers["Referer"] = f"{BASE_URL}/login"
    r = s.post(
        f"{BASE_URL}/login",
        json={"credential": USERNAME, "password": PASSWORD, "remember": False},
        headers=headers, timeout=30, allow_redirects=False,
    )
    assert r.status_code in (200, 302, 303), f"Login failed: {r.status_code} {r.text[:400]}"

    # Verify authed
    v = s._inertia_version
    r = s.get(f"{BASE_URL}/permissions", headers={"X-Inertia": "true", "X-Inertia-Version": v, "Accept": "application/json"}, timeout=30)
    assert r.status_code == 200, f"Auth verification failed: {r.status_code} — {r.text[:300]}"
    return s


def _get(session, path):
    v = getattr(session, "_inertia_version", "")
    return session.get(f"{BASE_URL}{path}", headers={"X-Inertia": "true", "X-Inertia-Version": v, "Accept": "application/json"}, timeout=30)


def _post(session, path, payload):
    return session.post(f"{BASE_URL}{path}", json=payload, headers=_inertia_headers(session), timeout=30, allow_redirects=False)


def _put(session, path, payload):
    return session.put(f"{BASE_URL}{path}", json=payload, headers=_inertia_headers(session), timeout=30, allow_redirects=False)


def _delete(session, path):
    return session.delete(f"{BASE_URL}{path}", headers=_inertia_headers(session), timeout=30, allow_redirects=False)


def _rows(session, query=""):
    r = _get(session, f"/permissions{query}")
    assert r.status_code == 200
    return r.json()["props"]["permissions"]["data"]


def _find(session, name):
    for row in _rows(session, f"?search={name}&per_page=100"):
        if row["name"] == name:
            return row
    return None


# ---------- INDEX / FILTERS ----------
class TestPermissionsIndex:
    def test_page_loads_with_expected_props(self, session):
        r = _get(session, "/permissions")
        assert r.status_code == 200
        props = r.json()["props"]
        assert "permissions" in props and "entityOptions" in props
        entities = {o["value"] for o in props["entityOptions"]}
        for e in ["activity", "appearance", "dashboard", "permissions", "profile", "roles", "storage", "users"]:
            assert e in entities, f"missing entity: {e}"

    def test_search_users(self, session):
        rows = _rows(session, "?search=users")
        assert len(rows) >= 2
        assert all("users" in row["name"] for row in rows)

    def test_filter_by_entity(self, session):
        rows = _rows(session, "?entity=roles")
        assert len(rows) >= 1
        assert all(row["name"].startswith("roles.") for row in rows)

    def test_sort_guard_name(self, session):
        r = _get(session, "/permissions?sort=guard_name&dir=desc")
        assert r.status_code == 200
        f = r.json()["props"]["filters"]
        assert f["sort"] == "guard_name" and f["dir"] == "desc"

    def test_sort_name_asc(self, session):
        r = _get(session, "/permissions?sort=name&dir=asc&per_page=5")
        assert r.status_code == 200
        names = [row["name"] for row in r.json()["props"]["permissions"]["data"]]
        assert names == sorted(names)

    def test_pagination(self, session):
        r = _get(session, "/permissions?per_page=25")
        assert r.status_code == 200
        meta = r.json()["props"]["permissions"]["meta"]
        assert meta["per_page"] in (10, 25)  # TableQuery may clamp
        assert "page" in meta and "last_page" in meta and "total" in meta


# ---------- CRUD ----------
class TestPermissionCRUD:
    def test_create_valid(self, session):
        # Ensure clean state
        existing = _find(session, "qa_module.view")
        if existing and not existing["locked"]:
            _delete(session, f"/permissions/{existing['id']}")

        r = _post(session, "/permissions", {"name": "qa_module.view"})
        assert r.status_code in (200, 302, 303), f"got {r.status_code}: {r.text[:300]}"
        row = _find(session, "qa_module.view")
        assert row and row["locked"] is False
        assert row["entity"] == "qa_module" and row["ability"] == "view"

    def test_duplicate_returns_422(self, session):
        r = _post(session, "/permissions", {"name": "users.view"})
        assert r.status_code == 422

    def test_uppercase_returns_422(self, session):
        r = _post(session, "/permissions", {"name": "Projects.View"})
        assert r.status_code == 422

    def test_no_dot_returns_422(self, session):
        r = _post(session, "/permissions", {"name": "projects"})
        assert r.status_code == 422

    def test_update_own(self, session):
        row = _find(session, "qa_module.view") or _find(session, "qa_module.manage")
        if not row:
            _post(session, "/permissions", {"name": "qa_module.view"})
            row = _find(session, "qa_module.view")
        r = _put(session, f"/permissions/{row['id']}", {"name": "qa_module.manage"})
        assert r.status_code in (200, 302, 303), f"got {r.status_code}: {r.text[:300]}"
        assert _find(session, "qa_module.manage") is not None
        assert _find(session, "qa_module.view") is None

    def test_delete_own(self, session):
        row = _find(session, "qa_module.manage")
        if not row:
            _post(session, "/permissions", {"name": "qa_module.manage"})
            row = _find(session, "qa_module.manage")
        r = _delete(session, f"/permissions/{row['id']}")
        assert r.status_code in (200, 302, 303)
        assert _find(session, "qa_module.manage") is None


# ---------- LOCK PROTECTION ----------
class TestCoreLock:
    def test_index_marks_locked(self, session):
        row = _find(session, "users.view")
        assert row and row["locked"] is True

    def test_update_core_403(self, session):
        row = _find(session, "users.view")
        r = _put(session, f"/permissions/{row['id']}", {"name": "users.viewx"})
        assert r.status_code == 403, f"expected 403, got {r.status_code}"
        assert _find(session, "users.view") is not None

    def test_delete_core_403(self, session):
        row = _find(session, "permissions.manage")
        assert row and row["locked"]
        r = _delete(session, f"/permissions/{row['id']}")
        assert r.status_code == 403
        assert _find(session, "permissions.manage") is not None


# ---------- BULK ----------
class TestBulkDestroy:
    def test_skips_core_deletes_others(self, session):
        for name in ["qa_a.view", "qa_b.view"]:
            if not _find(session, name):
                _post(session, "/permissions", {"name": name})
        a = _find(session, "qa_a.view")
        b = _find(session, "qa_b.view")
        core = _find(session, "users.view")
        assert a and b and core and core["locked"]

        r = _post(session, "/permissions/bulk-destroy", {"ids": [a["id"], b["id"], core["id"]]})
        assert r.status_code in (200, 302, 303), f"got {r.status_code}: {r.text[:300]}"

        assert _find(session, "qa_a.view") is None
        assert _find(session, "qa_b.view") is None
        assert _find(session, "users.view") is not None  # core preserved

    def test_empty_ids_returns_422(self, session):
        r = _post(session, "/permissions/bulk-destroy", {})
        assert r.status_code == 422


# ---------- AUDIT TRAIL ----------
class TestAuditTrail:
    def test_audit_records_permission_actions(self, session):
        # Create then delete a permission to generate audit rows
        if not _find(session, "qa_audit.view"):
            _post(session, "/permissions", {"name": "qa_audit.view"})
        row = _find(session, "qa_audit.view")
        assert row
        _delete(session, f"/permissions/{row['id']}")

        r = _get(session, "/audit-trail?search=qa_audit")
        assert r.status_code == 200
        j = r.json()["props"]
        # find activities/logs table (structure: {logs|activities: {data: [...]}})
        container = None
        for key in ("logs", "activities"):
            if isinstance(j.get(key), dict) and "data" in j[key]:
                container = j[key]
                break
        assert container is not None, f"no audit container in props keys={list(j.keys())}"
        acts = container["data"]
        found_add = found_del = False
        for act in acts:
            desc = ((act.get("description") or act.get("action") or "")).lower()
            module = (act.get("module") or "").lower()
            if "qa_audit" in desc and module == "perizinan":
                if "menambah" in desc:
                    found_add = True
                if "menghapus" in desc:
                    found_del = True
        assert found_add, f"audit 'Menambah' entry not found in {len(acts)} rows"
        assert found_del, "audit 'Menghapus' entry not found"


# ---------- REGRESSION on other endpoints ----------
class TestRegression:
    @pytest.mark.parametrize("path", ["/roles", "/users", "/audit-trail", "/appearance", "/storage-settings", "/profile"])
    def test_endpoint_ok(self, session, path):
        r = _get(session, path)
        assert r.status_code == 200


# ---------- CLEANUP ----------
@pytest.fixture(scope="session", autouse=True)
def _cleanup(session):
    yield
    for name in ["qa_module.view", "qa_module.manage", "qa_a.view", "qa_b.view", "qa_audit.view"]:
        row = _find(session, name)
        if row and not row["locked"]:
            _delete(session, f"/permissions/{row['id']}")



# =====================================================================
# NEW FEATURES: Role permission matrix, Generator, Exports, User import
# =====================================================================

def _roles(session):
    r = _get(session, "/roles")
    assert r.status_code == 200
    return r.json()["props"]["roles"]


def _role_by_name(session, name):
    for role in _roles(session):
        if role["name"] == name:
            return role
    return None


def _multipart_headers(session):
    """Headers for multipart file upload (do NOT set Content-Type; requests sets it)."""
    v = getattr(session, "_inertia_version", "")
    return {
        "X-XSRF-TOKEN": _xsrf(session),
        "X-Inertia": "true",
        "X-Inertia-Version": v,
        "X-Requested-With": "XMLHttpRequest",
        "Accept": "application/json",
        "Referer": f"{BASE_URL}/",
    }


# ---------- ROLE PERMISSION MATRIX ----------
class TestRolePermissionMatrix:
    def test_show_role_returns_matrix(self, session):
        role = _role_by_name(session, "Super Admin") or _roles(session)[0]
        r = _get(session, f"/roles/{role['id']}")
        assert r.status_code == 200
        props = r.json()["props"]
        assert "matrix" in props and isinstance(props["matrix"], list)
        entities = {g["entity"] for g in props["matrix"]}
        for e in ["activity", "appearance", "dashboard", "permissions", "profile", "roles", "storage", "users"]:
            assert e in entities, f"missing matrix entity {e}"
        # abilities shape
        first = props["matrix"][0]
        assert "abilities" in first and first["abilities"]
        assert "name" in first["abilities"][0] and "label" in first["abilities"][0]

    def test_super_admin_is_locked(self, session):
        role = _role_by_name(session, "Super Admin")
        assert role is not None
        r = _get(session, f"/roles/{role['id']}")
        assert r.json()["props"]["role"]["locked"] is True

    def test_super_admin_sync_403(self, session):
        role = _role_by_name(session, "Super Admin")
        r = _put(session, f"/roles/{role['id']}/permissions", {"permissions": ["users.view"]})
        assert r.status_code == 403, f"expected 403, got {r.status_code}: {r.text[:200]}"

    def test_sync_non_super_admin_and_persist(self, session):
        # Use any non-super role that exists (user-owned like 'Akunting' preferred).
        target = None
        for role in _roles(session):
            if not role["locked"] and role["users_count"] == 0:
                target = role
                break
        # If none with users_count=0, use first non-locked (we WILL restore to original).
        if target is None:
            for role in _roles(session):
                if not role["locked"]:
                    target = role
                    break
        assert target is not None
        original = list(target["permissions"])

        try:
            # Set to specific set
            new_perms = ["users.view", "users.manage", "dashboard.view"]
            r = _put(session, f"/roles/{target['id']}/permissions", {"permissions": new_perms})
            assert r.status_code in (200, 302, 303), r.text[:200]

            # Reload and verify
            r = _get(session, f"/roles/{target['id']}")
            got = set(r.json()["props"]["role"]["permissions"])
            assert got == set(new_perms), f"got {got}"
        finally:
            # Restore
            _put(session, f"/roles/{target['id']}/permissions", {"permissions": original})
            r = _get(session, f"/roles/{target['id']}")
            assert set(r.json()["props"]["role"]["permissions"]) == set(original)

    def test_sync_unknown_permission_returns_422(self, session):
        target = None
        for role in _roles(session):
            if not role["locked"]:
                target = role
                break
        assert target
        r = _put(session, f"/roles/{target['id']}/permissions", {"permissions": ["does.not_exist_qa"]})
        assert r.status_code == 422


# ---------- PERMISSION GENERATOR ----------
class TestPermissionGenerator:
    ABILITIES = ["view", "view_any", "create", "update", "delete", "delete_any"]

    def _cleanup_qa_projects(self, session):
        # Fetch and delete all qa_projects.* rows
        r = _get(session, "/permissions?entity=qa_projects&per_page=50")
        for row in r.json()["props"]["permissions"]["data"]:
            if not row["locked"]:
                _delete(session, f"/permissions/{row['id']}")

    def test_generate_creates_6(self, session):
        self._cleanup_qa_projects(session)
        r = _post(session, "/permissions/generate", {"entity": "qa_projects", "abilities": self.ABILITIES})
        assert r.status_code in (200, 302, 303), f"got {r.status_code}: {r.text[:300]}"
        rows = _get(session, "/permissions?entity=qa_projects&per_page=50").json()["props"]["permissions"]["data"]
        names = {row["name"] for row in rows}
        for ab in self.ABILITIES:
            assert f"qa_projects.{ab}" in names, f"missing qa_projects.{ab}"

    def test_generate_second_time_skips_all(self, session):
        # Already created — running again should return error 'sudah ada'
        r = _post(session, "/permissions/generate", {"entity": "qa_projects", "abilities": self.ABILITIES})
        assert r.status_code in (200, 302, 303)
        # Count remains 6
        rows = _get(session, "/permissions?entity=qa_projects&per_page=50").json()["props"]["permissions"]["data"]
        qa_rows = [r for r in rows if r["name"].startswith("qa_projects.")]
        assert len(qa_rows) == 6

    def test_generate_uppercase_entity_422(self, session):
        r = _post(session, "/permissions/generate", {"entity": "QA Projects", "abilities": ["view"]})
        assert r.status_code == 422

    def test_generate_no_abilities_422(self, session):
        r = _post(session, "/permissions/generate", {"entity": "qa_something", "abilities": []})
        assert r.status_code == 422

    def test_cleanup_qa_projects(self, session):
        self._cleanup_qa_projects(session)
        rows = _get(session, "/permissions?entity=qa_projects&per_page=50").json()["props"]["permissions"]["data"]
        assert not any(r["name"].startswith("qa_projects.") for r in rows)


# ---------- CSV EXPORT ----------
class TestCsvExport:
    UTF8_BOM = b"\xef\xbb\xbf"

    def _download(self, session, path):
        v = getattr(session, "_inertia_version", "")
        r = session.get(f"{BASE_URL}{path}", headers={"Accept": "text/csv,*/*"}, timeout=60)
        return r

    def test_users_export(self, session):
        r = self._download(session, "/users/export")
        assert r.status_code == 200
        assert "text/csv" in r.headers.get("Content-Type", "")
        body = r.content
        assert body.startswith(self.UTF8_BOM), f"missing BOM: {body[:20]!r}"
        # Header row (Indonesian)
        first_line = body[len(self.UTF8_BOM):].split(b"\n")[0].decode("utf-8")
        for col in ["Nama Lengkap", "Nama Pengguna", "Alamat Email", "Nomor HP", "Peranan", "Status", "Terakhir Login"]:
            assert col in first_line, f"missing column {col} in {first_line}"

    def test_users_export_respects_role_filter(self, session):
        # Compare CSV row count with table count filtered by role=Super Admin
        r = _get(session, "/users?role=Super Admin&per_page=100")
        table_count = len(r.json()["props"]["users"]["data"])
        csv = self._download(session, "/users/export?role=Super Admin").content
        lines = [l for l in csv[len(self.UTF8_BOM):].split(b"\n") if l.strip()]
        data_rows = len(lines) - 1  # minus header
        assert data_rows == table_count, f"csv rows {data_rows} != table rows {table_count}"

    def test_permissions_export(self, session):
        r = self._download(session, "/permissions/export?entity=users")
        assert r.status_code == 200
        assert "text/csv" in r.headers.get("Content-Type", "")
        body = r.content
        assert body.startswith(self.UTF8_BOM)
        first_line = body[len(self.UTF8_BOM):].split(b"\n")[0].decode("utf-8")
        for col in ["Nama Izin", "Entitas", "Aksi", "Guard", "Jumlah Peranan"]:
            assert col in first_line
        # every data row should start with users.
        data = body[len(self.UTF8_BOM):].split(b"\n")[1:]
        data = [d for d in data if d.strip()]
        for row in data:
            assert row.startswith(b"users."), f"unexpected row in filtered export: {row!r}"

    def test_audit_export(self, session):
        r = self._download(session, "/audit-trail/export")
        assert r.status_code == 200
        assert "text/csv" in r.headers.get("Content-Type", "")
        body = r.content
        assert body.startswith(self.UTF8_BOM)
        first_line = body[len(self.UTF8_BOM):].split(b"\n")[0].decode("utf-8")
        for col in ["Waktu", "Pelaku", "Aksi", "Modul", "Level", "Alamat IP", "Metode", "Kode Status", "URL"]:
            assert col in first_line, f"missing {col} in {first_line}"


# ---------- USER IMPORT ----------
class TestUserImport:
    QA_USERNAMES = ["qa_impor1", "qa_impor2"]

    def _users_by_username(self, session, username):
        r = _get(session, f"/users?search={username}&per_page=50")
        for row in r.json()["props"]["users"]["data"]:
            if row["username"] == username:
                return row
        return None

    def _cleanup(self, session):
        for uname in self.QA_USERNAMES:
            u = self._users_by_username(session, uname)
            if u:
                _delete(session, f"/users/{u['id']}")

    def test_import_valid_csv(self, session):
        self._cleanup(session)
        csv_body = (
            "name,username,email,phone,role,password\n"
            "QA Impor Satu,qa_impor1,qa1@example.test,081200000001,Staf,secret123\n"
            "QA Impor Dua,qa_impor2,qa2@example.test,,Staf,\n"
            ",qa_bad,qa3@example.test,081200000003,Staf,secret123\n"  # invalid: empty name
        ).encode("utf-8")

        files = {"file": ("qa_impor.csv", csv_body, "text/csv")}
        r = session.post(
            f"{BASE_URL}/users/import",
            files=files,
            headers=_multipart_headers(session),
            timeout=60,
            allow_redirects=False,
        )
        assert r.status_code in (200, 302, 303), f"got {r.status_code}: {r.text[:400]}"

        # Verify both valid users exist, with role Staf and is_active
        u1 = self._users_by_username(session, "qa_impor1")
        u2 = self._users_by_username(session, "qa_impor2")
        assert u1, "qa_impor1 missing"
        assert u2, "qa_impor2 missing"
        assert u1["role"] == "Staf" and u1["is_active"] is True
        assert u2["role"] == "Staf" and u2["is_active"] is True
        # invalid row skipped
        assert self._users_by_username(session, "qa_bad") is None

        self._cleanup(session)

    def test_import_png_rejected(self, session):
        # A tiny fake PNG magic-bytes buffer
        png = b"\x89PNG\r\n\x1a\n" + b"\0" * 100
        files = {"file": ("bad.png", png, "image/png")}
        r = session.post(
            f"{BASE_URL}/users/import",
            files=files,
            headers=_multipart_headers(session),
            timeout=30,
            allow_redirects=False,
        )
        assert r.status_code == 422, f"expected 422, got {r.status_code}: {r.text[:200]}"


# ---------- FINAL CLEANUP for new tests ----------
@pytest.fixture(scope="session", autouse=True)
def _cleanup_new(session):
    yield
    # qa_projects.*
    r = _get(session, "/permissions?entity=qa_projects&per_page=50")
    if r.status_code == 200:
        for row in r.json()["props"]["permissions"]["data"]:
            if not row["locked"]:
                _delete(session, f"/permissions/{row['id']}")
    # qa_impor users
    for uname in ["qa_impor1", "qa_impor2"]:
        r = _get(session, f"/users?search={uname}&per_page=50")
        for row in r.json()["props"]["users"]["data"]:
            if row["username"] == uname:
                _delete(session, f"/users/{row['id']}")
