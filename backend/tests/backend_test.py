"""AdminKit (Laravel 12 + Inertia) backend tests — users schema/CRUD/archive changes.

Auth: session cookie + XSRF-TOKEN header. Only creates data prefixed 'TEST'/'testqa'.
"""

import html
import json
import os
import re
import urllib.parse
from pathlib import Path

import pytest
import requests
from dotenv import dotenv_values

env = dotenv_values("/app/adminkit/.env")
BASE_URL = (os.environ.get("APP_URL") or env.get("APP_URL")).rstrip("/")

CRED_FILE = Path("/app/memory/test_credentials.md")


def read_credentials():
    if not CRED_FILE.exists():
        pytest.skip("missing /app/memory/test_credentials.md")
    content = CRED_FILE.read_text(encoding="utf-8")
    email = re.search(r"`(sa@[^`]+)`", content)
    pwd = re.search(r"`(SA@[^`]+)`", content)
    if not email or not pwd:
        pytest.skip("credentials not found in test_credentials.md")
    return {"credential": email.group(1), "password": pwd.group(1)}


def xsrf(session):
    token = session.cookies.get("XSRF-TOKEN")
    return urllib.parse.unquote(token) if token else ""


def page_props(response):
    """Extract Inertia page props from an HTML response."""
    match = re.search(
        r'data-page="app" type="application/json">(.*?)</script>',
        response.text,
        re.S,
    )
    if not match:
        match = re.search(r'data-page="(\{.+?\})"\s*>', response.text, re.S)
        if not match:
            return None
        return json.loads(html.unescape(match.group(1)))["props"]
    return json.loads(match.group(1))["props"]


def post(session, path, data, **kw):
    return session.post(
        f"{BASE_URL}{path}",
        data=data,
        headers={"X-XSRF-TOKEN": xsrf(session), "Accept": "text/html"},
        allow_redirects=False,
        **kw,
    )


def put(session, path, data):
    return post(session, path, {**data, "_method": "PUT"})


def delete(session, path, data=None):
    return post(session, path, {**(data or {}), "_method": "DELETE"})


@pytest.fixture(scope="session")
def creds():
    return read_credentials()


@pytest.fixture(scope="session")
def admin(creds):
    s = requests.Session()
    s.get(f"{BASE_URL}/login")
    r = post(s, "/login", creds)
    if r.status_code != 302:
        pytest.fail(f"login failed: {r.status_code} {r.text[:400]}")
    return s


@pytest.fixture(scope="session")
def created_ids():
    return []


@pytest.fixture(scope="session", autouse=True)
def cleanup(admin, created_ids):
    yield
    for uid in created_ids:
        delete(admin, f"/users/{uid}")
        delete(admin, f"/users/{uid}/force")


def sheet_headers(content):
    import io

    import openpyxl

    ws = openpyxl.load_workbook(io.BytesIO(content)).active
    return [c.value for c in next(ws.iter_rows(min_row=1, max_row=1)) if c.value is not None]


def find_row(session, username, status="aktif"):
    r = session.get(f"{BASE_URL}/users", params={"search": username, "status": status})
    assert r.status_code == 200
    props = page_props(r)
    for row in props["users"]["data"]:
        if row["username"] == username:
            return row
    return None


DIGITS = {"0": "Nol", "1": "Satu", "2": "Dua", "3": "Tiga", "4": "Empat",
          "5": "Lima", "6": "Enam", "7": "Tujuh", "8": "Delapan", "9": "Sembilan"}


def num_to_words(suffix):
    """Nama hanya boleh huruf (aturan personName), jadi angka diubah ke kata."""
    return " ".join(DIGITS[c] for c in str(suffix))


def make_user(admin, created_ids, suffix, **overrides):
    payload = {
        "name": f"TEST QA {num_to_words(suffix)}",
        "username": f"testqa{suffix}".lower(),
        "email": f"testqa{suffix}@example.com".lower(),
        "phone": f"08120009{suffix}",
        "role": "Guest",
        "office": "Kantor Pusat",
        "alias": f"Q{suffix[-2:]}",
        "mso_code": f"M{suffix}",
        "collector_code": f"K{suffix[-2:]}",
        "password": "Rahasia#2026",
    }
    payload.update(overrides)
    r = post(admin, "/users", payload)
    assert r.status_code == 302, f"store failed: {r.status_code} {r.text[:300]}"
    row = find_row(admin, payload["username"], status="semua")
    assert row is not None, f"user {payload['username']} not visible after create"
    created_ids.append(row["id"])
    return payload, row


# --- Auth / dashboard ---------------------------------------------------------
class TestAuthAndDashboard:
    def test_login_and_dashboard_kpi_hint(self, admin):
        r = admin.get(f"{BASE_URL}/")
        assert r.status_code == 200
        blob = json.dumps(page_props(r))
        assert "aktif" in blob and "terarsip" in blob, "KPI hint 'N aktif · M terarsip' missing"


# --- Users list / schema -----------------------------------------------------
class TestUsersIndex:
    def test_index_returns_new_columns(self, admin):
        r = admin.get(f"{BASE_URL}/users")
        assert r.status_code == 200
        props = page_props(r)
        row = props["users"]["data"][0]
        for key in ["role", "office", "alias", "mso_code", "collector_code", "archived", "status_label"]:
            assert key in row, f"missing column {key}"
        assert "is_active" not in row

    def test_create_page_renders(self, admin):
        r = admin.get(f"{BASE_URL}/users/create")
        assert r.status_code == 200
        props = page_props(r)
        assert props["user"] is None and len(props["roleOptions"]) > 0

    def test_status_filter_values(self, admin):
        for status in ["aktif", "terarsip", "semua"]:
            r = admin.get(f"{BASE_URL}/users", params={"status": status})
            assert r.status_code == 200, f"status={status} -> {r.status_code}"
            assert page_props(r)["filters"]["status"] == status


# --- Store validation --------------------------------------------------------
class TestStoreValidation:
    def _errors(self, admin, payload):
        r = post(admin, "/users", payload)
        assert r.status_code == 302
        page = admin.get(f"{BASE_URL}/users/create")
        return page_props(page).get("errors", {})

    def test_short_alias_rejected(self, admin):
        errors = self._errors(admin, {
            "name": "TEST Alias Pendek", "username": "testaliaspendek",
            "email": "testaliaspendek@example.com", "role": "Guest",
            "alias": "AB", "password": "Rahasia#2026",
        })
        assert "alias" in errors, errors

    def test_non_alnum_code_rejected(self, admin):
        errors = self._errors(admin, {
            "name": "TEST Kode Aneh", "username": "testkodeaneh",
            "email": "testkodeaneh@example.com", "role": "Guest",
            "mso_code": "A-01", "password": "Rahasia#2026",
        })
        assert "mso_code" in errors, errors

    def test_short_password_rejected(self, admin):
        errors = self._errors(admin, {
            "name": "TEST Sandi Pendek", "username": "testsandipendek",
            "email": "testsandipendek@example.com", "role": "Guest", "password": "abc123",
        })
        assert "password" in errors, errors

    def test_duplicate_codes_rejected(self, admin, created_ids):
        payload, _ = make_user(admin, created_ids, "701")
        errors = self._errors(admin, {
            "name": "TEST Duplikat", "username": "testduplikat",
            "email": "testduplikat@example.com", "role": "Guest",
            "alias": payload["alias"], "mso_code": payload["mso_code"],
            "collector_code": payload["collector_code"], "password": "Rahasia#2026",
        })
        assert "alias" in errors and "mso_code" in errors and "collector_code" in errors, errors


# --- CRUD + archive lifecycle ------------------------------------------------
class TestUserLifecycle:
    def test_create_uppercases_codes(self, admin, created_ids):
        payload, row = make_user(admin, created_ids, "702", alias="q72", mso_code="m702", collector_code="k72")
        assert row["alias"] == "Q72"
        assert row["mso_code"] == "M702"
        assert row["collector_code"] == "K72"
        assert row["office"] == "Kantor Pusat"
        assert row["role"] == "Guest"
        assert row["status_label"] == "Aktif" and row["archived"] is False

    def test_update_persists_and_keeps_password(self, admin, created_ids):
        payload, row = make_user(admin, created_ids, "703")
        uid = row["id"]
        r = put(admin, f"/users/{uid}", {**payload, "office": "Kantor Kas", "mso_code": "z999", "password": ""})
        assert r.status_code == 302, r.text[:300]
        updated = find_row(admin, payload["username"])
        assert updated["office"] == "Kantor Kas"
        assert updated["mso_code"] == "Z999"
        s = requests.Session()
        s.get(f"{BASE_URL}/login")
        login = post(s, "/login", {"credential": payload["email"], "password": payload["password"]})
        assert login.status_code == 302 and "/login" not in login.headers.get("Location", ""), \
            "old password no longer works after update with empty password"

    def test_archive_blocks_login_then_restore(self, admin, created_ids):
        payload, row = make_user(admin, created_ids, "704")
        uid = row["id"]

        assert delete(admin, f"/users/{uid}").status_code == 302
        assert find_row(admin, payload["username"], status="aktif") is None
        archived = find_row(admin, payload["username"], status="terarsip")
        assert archived is not None and archived["archived"] is True
        assert archived["status_label"] == "Terarsip"

        s = requests.Session()
        s.get(f"{BASE_URL}/login")
        r = post(s, "/login", {"credential": payload["email"], "password": payload["password"]})
        assert r.status_code == 302
        assert "/login" in r.headers.get("Location", ""), "archived user was allowed to login"
        assert "tidak cocok" in s.get(f"{BASE_URL}/login").text

        assert post(admin, f"/users/{uid}/restore", {}).status_code == 302
        restored = find_row(admin, payload["username"], status="aktif")
        assert restored is not None and restored["archived"] is False

        s2 = requests.Session()
        s2.get(f"{BASE_URL}/login")
        r2 = post(s2, "/login", {"credential": payload["email"], "password": payload["password"]})
        assert r2.status_code == 302 and "/login" not in r2.headers.get("Location", ""), \
            "restored user cannot login"

    def test_force_delete_requires_archive_then_removes(self, admin, created_ids):
        payload, row = make_user(admin, created_ids, "705")
        uid = row["id"]
        delete(admin, f"/users/{uid}/force")
        assert find_row(admin, payload["username"], status="semua") is not None, \
            "active user was force deleted without archiving"

        assert delete(admin, f"/users/{uid}").status_code == 302
        assert delete(admin, f"/users/{uid}/force").status_code == 302
        assert find_row(admin, payload["username"], status="semua") is None

    def test_cannot_archive_self(self, admin):
        me = find_row(admin, "superadmin")
        assert me is not None
        r = delete(admin, f"/users/{me['id']}")
        assert r.status_code == 302
        assert find_row(admin, "superadmin", status="aktif") is not None, "self archive succeeded"


# --- Bulk actions ------------------------------------------------------------
class TestBulkActions:
    def test_bulk_archive_restore_force_delete_skips_self(self, admin, created_ids):
        p1, r1 = make_user(admin, created_ids, "806")
        p2, r2 = make_user(admin, created_ids, "807")
        me = find_row(admin, "superadmin")
        ids = [r1["id"], r2["id"], me["id"]]

        assert post(admin, "/users/bulk", {"action": "archive", "ids[]": ids}).status_code == 302
        assert find_row(admin, "superadmin", status="aktif") is not None, "self got archived by bulk"
        assert find_row(admin, p1["username"], status="terarsip") is not None
        assert find_row(admin, p2["username"], status="terarsip") is not None

        assert post(admin, "/users/bulk", {"action": "restore", "ids[]": [r1["id"], r2["id"]]}).status_code == 302
        assert find_row(admin, p1["username"], status="aktif") is not None

        assert post(admin, "/users/bulk", {"action": "archive", "ids[]": [r1["id"], r2["id"]]}).status_code == 302
        assert post(admin, "/users/bulk", {"action": "force-delete", "ids[]": [r1["id"], r2["id"]]}).status_code == 302
        assert find_row(admin, p1["username"], status="semua") is None
        assert find_row(admin, p2["username"], status="semua") is None

    def test_bulk_invalid_action_rejected(self, admin):
        r = post(admin, "/users/bulk", {"action": "nuke", "ids[]": [1]})
        assert r.status_code == 302
        errors = page_props(admin.get(f"{BASE_URL}/users")).get("errors", {})
        assert "action" in errors, errors


# --- Export / import ---------------------------------------------------------
class TestExportImport:
    def test_export_xlsx(self, admin):
        r = admin.get(f"{BASE_URL}/users/export", params={"status": "semua"})
        assert r.status_code == 200
        assert "spreadsheet" in r.headers.get("Content-Type", ""), r.headers.get("Content-Type")
        assert len(r.content) > 500
        headers = sheet_headers(r.content)
        assert headers == [
            "Nama Lengkap", "Nama Pengguna", "Alamat Email", "Nomor HP", "Peranan",
            "Kantor", "Alias", "Kode MSO", "Kode Kolektor", "Status", "Terakhir Login",
        ], headers

    def test_import_template(self, admin):
        r = admin.get(f"{BASE_URL}/users/import/template")
        assert r.status_code == 200
        assert "spreadsheet" in r.headers.get("Content-Type", "")
        headers = sheet_headers(r.content)
        assert headers == [
            "Nama Lengkap", "Nama Pengguna", "Alamat Email", "Nomor HP", "Peranan",
            "Kantor", "Alias", "Kode MSO", "Kode Kolektor", "Kata Sandi",
        ], headers

    def test_import_rejects_non_xlsx(self, admin):
        r = admin.post(
            f"{BASE_URL}/users/import",
            files={"file": ("bad.txt", b"hello", "text/plain")},
            headers={"X-XSRF-TOKEN": xsrf(admin)},
            allow_redirects=False,
        )
        assert r.status_code == 302
        errors = page_props(admin.get(f"{BASE_URL}/users")).get("errors", {})
        assert "file" in errors, errors


# --- Profile -----------------------------------------------------------------
class TestProfile:
    def test_profile_exposes_readonly_fields(self, admin):
        blob = json.dumps(page_props(admin.get(f"{BASE_URL}/profile")))
        for key in ["office", "alias", "mso_code", "collector_code"]:
            assert f'"{key}"' in blob, f"{key} missing from profile props"

    def test_profile_update_basic_fields(self, admin):
        props = page_props(admin.get(f"{BASE_URL}/profile"))
        user = props.get("user") or props.get("profile") or props.get("auth", {}).get("user")
        assert user, f"profile props keys: {list(props.keys())}"
        r = put(admin, "/profile", {
            "name": user["name"],
            "username": user["username"],
            "email": user["email"],
            "phone": user.get("phone") or "081200000001",
        })
        assert r.status_code == 302, r.text[:300]


# --- Regression: other pages -------------------------------------------------
class TestRegressionPages:
    @pytest.mark.parametrize("path", [
        "/permissions", "/roles", "/menus", "/audit-trail", "/appearance", "/object-storage",
    ])
    def test_page_loads(self, admin, path):
        r = admin.get(f"{BASE_URL}{path}")
        assert r.status_code == 200, f"{path} -> {r.status_code}"
        assert page_props(r) is not None
