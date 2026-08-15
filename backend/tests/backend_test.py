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
