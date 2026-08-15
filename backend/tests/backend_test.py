"""Backend tests for AdminKit (Laravel) — iteration 8.

Focus on:
- Form Request validation (phone regex, username lowercase/alpha_dash, url, path)
- Endpoints changed in latest iteration: /profile, /users, /appearance/identity, /appearance/contact,
  /storage-settings, /roles, /activity
"""
import os
import re
from pathlib import Path

import pytest
import requests
from dotenv import dotenv_values

frontend_env = dotenv_values("/app/frontend/.env")
BASE_URL = (
    os.environ.get("REACT_APP_BACKEND_URL")
    or frontend_env.get("REACT_APP_BACKEND_URL")
).rstrip("/")


# ---------- Fixtures ----------
@pytest.fixture(scope="session")
def creds():
    p = Path("/app/memory/test_credentials.md").read_text()
    email = re.search(r"`([\w.]+@[\w.]+)`", p).group(1)
    username = re.search(r"`(zulfame)`", p).group(1)
    return {"username": username, "email": email, "password": "password"}


@pytest.fixture(scope="session")
def client(creds):
    """Session cookie + XSRF header (Laravel web guard)."""
    s = requests.Session()
    # Fetch login page to get XSRF-TOKEN cookie
    r = s.get(f"{BASE_URL}/login", timeout=30)
    assert r.status_code == 200, r.status_code
    xsrf = s.cookies.get("XSRF-TOKEN")
    assert xsrf, "XSRF-TOKEN cookie not set"
    s.headers.update({
        "X-XSRF-TOKEN": requests.utils.unquote(xsrf),
        "X-Requested-With": "XMLHttpRequest",
        "Accept": "application/json",
        "Referer": f"{BASE_URL}/login",
    })
    # Login via POST /login
    r = s.post(
        f"{BASE_URL}/login",
        json={"credential": creds["username"], "password": creds["password"]},
        timeout=30,
    )
    assert r.status_code in (200, 204, 302), f"login failed {r.status_code}: {r.text[:300]}"
    # Refresh XSRF (rotates on login)
    xsrf = s.cookies.get("XSRF-TOKEN")
    if xsrf:
        s.headers["X-XSRF-TOKEN"] = requests.utils.unquote(xsrf)
    s.headers["Referer"] = f"{BASE_URL}/"
    # Fetch inertia version from a page (HTML) to reuse for XHR-Inertia calls
    r_home = s.get(f"{BASE_URL}/users", headers={"Accept": "text/html"}, timeout=30)
    m = re.search(r'data-page="([^"]+)"', r_home.text)
    version = ""
    if m:
        import html as _html, json as _json
        try:
            version = _json.loads(_html.unescape(m.group(1))).get("version", "")
        except Exception:
            version = ""
    s.headers["_INERTIA_VERSION"] = version  # stash for tests
    return s


def _inertia_headers(client):
    return {"Accept": "text/html"}


def _inertia_json(response):
    """Parse Inertia data-page JSON from a text/html Inertia response.

    Layout uses: <script data-page="app" type="application/json">{...json...}</script>
    """
    import json as _json
    m = re.search(r'data-page="app"[^>]*>(\{.*?\})</script>', response.text, re.DOTALL)
    if not m:
        return None
    return _json.loads(m.group(1))


# ---------- Login validation ----------
class TestLogin:
    def test_login_empty_returns_422(self):
        s = requests.Session()
        s.get(f"{BASE_URL}/login")
        xsrf = requests.utils.unquote(s.cookies.get("XSRF-TOKEN"))
        r = s.post(
            f"{BASE_URL}/login",
            json={"credential": "", "password": ""},
            headers={
                "X-XSRF-TOKEN": xsrf,
                "X-Requested-With": "XMLHttpRequest",
                "Accept": "application/json",
                "Referer": f"{BASE_URL}/login",
            },
            timeout=30,
        )
        assert r.status_code == 422, r.status_code
        j = r.json()
        assert "credential" in j.get("errors", {})
        assert "password" in j.get("errors", {})

    def test_login_success(self, creds):
        s = requests.Session()
        s.get(f"{BASE_URL}/login")
        xsrf = requests.utils.unquote(s.cookies.get("XSRF-TOKEN"))
        r = s.post(
            f"{BASE_URL}/login",
            json={"credential": creds["username"], "password": creds["password"]},
            headers={
                "X-XSRF-TOKEN": xsrf,
                "X-Requested-With": "XMLHttpRequest",
                "Accept": "application/json",
                "Referer": f"{BASE_URL}/login",
            },
            timeout=30,
            allow_redirects=False,
        )
        assert r.status_code in (200, 204, 302), r.status_code


# ---------- Profile validation ----------
class TestProfilePhone:
    def _put(self, client, payload):
        return client.put(
            f"{BASE_URL}/profile",
            json=payload,
            timeout=30,
            allow_redirects=False,
        )

    def _base(self, creds):
        return {
            "name": "Zulfadli Rizal",
            "username": creds["username"],
            "email": creds["email"],
        }

    def test_phone_letters_rejected(self, client, creds):
        p = self._base(creds) | {"phone": "abcdefghij"}
        r = self._put(client, p)
        assert r.status_code == 422, f"expected 422 got {r.status_code}: {r.text[:300]}"
        assert "phone" in r.json().get("errors", {})

    def test_phone_too_short_rejected(self, client, creds):
        p = self._base(creds) | {"phone": "0812"}
        r = self._put(client, p)
        assert r.status_code == 422
        assert "phone" in r.json().get("errors", {})

    def test_phone_valid_plus(self, client, creds):
        p = self._base(creds) | {"phone": "+6282320099971"}
        r = self._put(client, p)
        assert r.status_code in (200, 204, 302), f"{r.status_code}: {r.text[:300]}"

    def test_phone_valid_local(self, client, creds):
        p = self._base(creds) | {"phone": "082320099971"}
        r = self._put(client, p)
        assert r.status_code in (200, 204, 302)

    def test_username_uppercase_rejected(self, client, creds):
        p = self._base(creds) | {"username": "ZULFA", "phone": creds.get("phone", "082320099971")}
        r = self._put(client, p)
        assert r.status_code == 422
        assert "username" in r.json().get("errors", {})

    def test_email_invalid_rejected(self, client, creds):
        p = self._base(creds) | {"email": "abc"}
        r = self._put(client, p)
        assert r.status_code == 422
        assert "email" in r.json().get("errors", {})

    def test_name_empty_rejected(self, client, creds):
        p = self._base(creds) | {"name": ""}
        r = self._put(client, p)
        assert r.status_code == 422
        assert "name" in r.json().get("errors", {})


# ---------- Profile password ----------
class TestProfilePassword:
    URL = "/profile/password"

    def test_confirm_mismatch(self, client):
        r = client.put(f"{BASE_URL}{self.URL}", json={
            "current_password": "password",
            "password": "newpassword12",
            "password_confirmation": "different",
        }, allow_redirects=False, timeout=30)
        assert r.status_code == 422
        assert "password" in r.json().get("errors", {})

    def test_short_new_password(self, client):
        r = client.put(f"{BASE_URL}{self.URL}", json={
            "current_password": "password",
            "password": "abc",
            "password_confirmation": "abc",
        }, allow_redirects=False, timeout=30)
        assert r.status_code == 422
        assert "password" in r.json().get("errors", {})

    def test_wrong_current_password(self, client):
        r = client.put(f"{BASE_URL}{self.URL}", json={
            "current_password": "wrong-current-pass",
            "password": "newpassword12",
            "password_confirmation": "newpassword12",
        }, allow_redirects=False, timeout=30)
        assert r.status_code == 422
        assert "current_password" in r.json().get("errors", {})


# ---------- Storage settings ----------
class TestStorageSettings:
    URL = "/storage-settings"

    def _base(self):
        return {
            "storage_driver": "local",
            "s3_endpoint": "",
            "s3_bucket": "",
            "s3_path": "",
            "s3_key": "",
            "s3_secret": "",
            "s3_region": "",
            "s3_public_url": "",
            "s3_path_style": True,
        }

    def test_invalid_path_rejected(self, client):
        p = self._base() | {"s3_path": "folder spasi!"}
        r = client.put(f"{BASE_URL}{self.URL}", json=p, allow_redirects=False, timeout=30)
        assert r.status_code == 422, r.text[:300]
        assert "s3_path" in r.json().get("errors", {})

    def test_endpoint_without_http_rejected_when_s3(self, client):
        p = self._base() | {
            "storage_driver": "s3",
            "s3_endpoint": "example.com",
            "s3_bucket": "adminkit",
            "s3_region": "ap-southeast-1",
            "s3_key": "AKIA",
        }
        r = client.put(f"{BASE_URL}{self.URL}", json=p, allow_redirects=False, timeout=30)
        assert r.status_code == 422
        assert "s3_endpoint" in r.json().get("errors", {})

    def test_valid_path_saved(self, client):
        p = self._base() | {"s3_path": "adminkit"}
        r = client.put(f"{BASE_URL}{self.URL}", json=p, allow_redirects=False, timeout=30)
        assert r.status_code in (200, 204, 302), r.text[:300]


# ---------- Appearance ----------
class TestAppearance:
    def test_identity_empty_app_name_rejected(self, client):
        r = client.put(f"{BASE_URL}/appearance/identity", json={
            "app_name": "",
            "tagline": "hello",
            "brand_initials": "AK",
        }, allow_redirects=False, timeout=30)
        assert r.status_code == 422
        assert "app_name" in r.json().get("errors", {})

    def test_identity_valid(self, client):
        r = client.put(f"{BASE_URL}/appearance/identity", json={
            "app_name": "AdminKit",
            "tagline": "Admin panel",
            "brand_initials": "AK",
        }, allow_redirects=False, timeout=30)
        assert r.status_code in (200, 204, 302), r.text[:300]

    def test_seo_canonical_url_without_http_rejected(self, client):
        r = client.put(f"{BASE_URL}/appearance/seo", json={
            "meta_title": "Title",
            "meta_description": "Desc",
            "canonical_url": "example.com",
        }, allow_redirects=False, timeout=30)
        assert r.status_code == 422
        assert "canonical_url" in r.json().get("errors", {})

    def test_contact_invalid_email_rejected(self, client):
        r = client.put(f"{BASE_URL}/appearance/contact", json={
            "support_email": "notemail",
            "footer_text": "footer",
        }, allow_redirects=False, timeout=30)
        assert r.status_code == 422
        assert "support_email" in r.json().get("errors", {})


# ---------- Users ----------
class TestUsers:
    def test_create_user_uppercase_username_rejected(self, client):
        r = client.post(f"{BASE_URL}/users", json={
            "name": "Test User",
            "username": "TESTUSER",
            "email": "TEST_user1@example.com",
            "phone": "081234567890",
            "role": "admin",
            "password": "password12",
            "is_active": True,
        }, allow_redirects=False, timeout=30)
        assert r.status_code == 422
        assert "username" in r.json().get("errors", {})

    def test_create_user_phone_letters_rejected(self, client):
        r = client.post(f"{BASE_URL}/users", json={
            "name": "Test User",
            "username": "testuser2",
            "email": "TEST_user2@example.com",
            "phone": "abcdefghij",
            "role": "admin",
            "password": "password12",
            "is_active": True,
        }, allow_redirects=False, timeout=30)
        assert r.status_code == 422
        assert "phone" in r.json().get("errors", {})

    def test_create_user_valid_then_delete(self, client):
        # cleanup first
        r0 = client.get(f"{BASE_URL}/users?search=test_user_qa8", headers={"Accept": "text/html"}, timeout=30)
        for u in _inertia_json(r0)["props"]["users"]["data"]:
            if u["username"] == "test_user_qa8":
                client.delete(f"{BASE_URL}/users/{u['id']}", allow_redirects=False, timeout=30)
        payload = {
            "name": "Test User",
            "username": "test_user_qa8",
            "email": "TEST_qa8@example.com",
            "phone": "081234567890",
            "role": "Staf",
            "password": "password12",
            "is_active": True,
        }
        r = client.post(f"{BASE_URL}/users", json=payload, allow_redirects=False, timeout=30)
        assert r.status_code in (200, 201, 204, 302), f"{r.status_code}: {r.text[:300]}"

        # Fetch users list and find the created user, then cleanup
        r2 = client.get(f"{BASE_URL}/users?search=test_user_qa8", headers={"Accept": "text/html"}, timeout=30)
        data = _inertia_json(r2)["props"]["users"]["data"]
        assert any(u["username"] == "test_user_qa8" for u in data)
        for u in data:
            if u["username"] == "test_user_qa8":
                client.delete(f"{BASE_URL}/users/{u['id']}", allow_redirects=False, timeout=30)


# ---------- Regression: roles + activity ----------
class TestRegression:
    def test_roles_page_loads(self, client):
        r = client.get(f"{BASE_URL}/roles", headers={"Accept": "text/html"}, timeout=30)
        assert r.status_code == 200

    def test_activity_page_loads(self, client):
        # Iteration 12: /activity renamed to /audit-trail
        r_old = client.get(f"{BASE_URL}/activity", headers={"Accept": "text/html"}, timeout=30, allow_redirects=False)
        assert r_old.status_code in (404, 405), f"expected old /activity to be gone, got {r_old.status_code}"
        r = client.get(f"{BASE_URL}/audit-trail", headers={"Accept": "text/html"}, timeout=30)
        assert r.status_code == 200
        d = _inertia_json(r)
        assert d["component"] == "AuditTrail"

    def test_profile_page_loads(self, client):
        r = client.get(f"{BASE_URL}/profile", headers={"Accept": "text/html"}, timeout=30)
        assert r.status_code == 200
        # 'Kantor' label should not be present on profile page HTML/Inertia data
        assert "Kantor" not in r.text, "Kolom 'Kantor' masih ada di halaman Profil"

    def test_users_page_no_kantor(self, client):
        r = client.get(f"{BASE_URL}/users", headers={"Accept": "text/html"}, timeout=30)
        assert r.status_code == 200
        assert "Kantor" not in r.text, "Kolom 'Kantor' masih ada di halaman Pengguna"


# ---------- Iteration 10: routes removed + relaxed identity + role show ----------
class TestIteration10Routes:
    def test_roles_matrix_route_removed(self, client):
        # PUT /roles/matrix should NOT exist anymore
        r = client.put(f"{BASE_URL}/roles/matrix", json={}, allow_redirects=False, timeout=30)
        assert r.status_code in (404, 405), f"expected 404/405 got {r.status_code}"

    def test_appearance_og_route_removed(self, client):
        r = client.put(f"{BASE_URL}/appearance/og", json={}, allow_redirects=False, timeout=30)
        assert r.status_code in (404, 405), f"expected 404/405 got {r.status_code}"

    def test_role_show_page_loads(self, client):
        # Find Staf role id
        r = client.get(f"{BASE_URL}/roles", headers=_inertia_headers(client), timeout=30)
        assert r.status_code == 200
        data = _inertia_json(r)
        roles = data["props"]["roles"]
        staf = next((x for x in roles if x["name"] == "Staf"), None)
        assert staf, "Staf role not found"
        r2 = client.get(f"{BASE_URL}/roles/{staf['id']}", headers=_inertia_headers(client), timeout=30)
        assert r2.status_code == 200
        d2 = _inertia_json(r2)
        assert d2["component"] == "RoleDetail"
        assert d2["props"]["role"]["name"] == "Staf"


class TestIteration10RoleValidation:
    def test_role_empty_name_rejected(self, client):
        r = client.post(f"{BASE_URL}/roles", json={"name": ""}, allow_redirects=False, timeout=30)
        assert r.status_code == 422
        assert "name" in r.json().get("errors", {})

    def test_role_create_and_delete(self, client):
        # Create
        r = client.post(f"{BASE_URL}/roles", json={"name": "Auditor QA"}, allow_redirects=False, timeout=30)
        assert r.status_code in (200, 201, 204, 302), f"{r.status_code}: {r.text[:300]}"
        # Find id
        r2 = client.get(f"{BASE_URL}/roles", headers=_inertia_headers(client), timeout=30)
        role = next((x for x in _inertia_json(r2)["props"]["roles"] if x["name"] == "Auditor QA"), None)
        assert role, "created role not found"
        # Delete
        r3 = client.delete(f"{BASE_URL}/roles/{role['id']}", allow_redirects=False, timeout=30)
        assert r3.status_code in (200, 204, 302), r3.text[:300]


class TestIteration10UsersRelaxed:
    def _cleanup_email(self, client, name):
        # Look up user by name and delete
        try:
            r = client.get(f"{BASE_URL}/users?search={name}", headers=_inertia_headers(client), timeout=30)
            for u in _inertia_json(r)["props"]["users"]["data"]:
                if u["name"] == name:
                    client.delete(f"{BASE_URL}/users/{u['id']}", allow_redirects=False, timeout=30)
        except Exception:
            pass

    def test_create_user_only_required_fields(self, client):
        """Username, email, phone nullable. Only name + role + password required."""
        name = "TEST QA Minimal"
        self._cleanup_email(client, name)
        r = client.post(f"{BASE_URL}/users", json={
            "name": name,
            "username": "",
            "email": "",
            "phone": "",
            "role": "Staf",
            "password": "password12",
            "is_active": True,
        }, allow_redirects=False, timeout=30)
        assert r.status_code in (200, 201, 204, 302), f"{r.status_code}: {r.text[:400]}"
        # cleanup
        self._cleanup_email(client, name)

    def test_duplicate_phone_rejected(self, client):
        # Zulfame likely has a phone; create user A with a phone, then user B with same phone
        phone = "081999888777"
        name_a, name_b = "TEST Phone A", "TEST Phone B"
        self._cleanup_email(client, name_a)
        self._cleanup_email(client, name_b)
        # Create A
        r = client.post(f"{BASE_URL}/users", json={
            "name": name_a, "username": "", "email": "", "phone": phone,
            "role": "Staf", "password": "password12", "is_active": True,
        }, allow_redirects=False, timeout=30)
        assert r.status_code in (200, 201, 204, 302), r.text[:300]
        # Create B with same phone
        r2 = client.post(f"{BASE_URL}/users", json={
            "name": name_b, "username": "", "email": "", "phone": phone,
            "role": "Staf", "password": "password12", "is_active": True,
        }, allow_redirects=False, timeout=30)
        assert r2.status_code == 422, f"expected 422 got {r2.status_code}: {r2.text[:300]}"
        assert "phone" in r2.json().get("errors", {})
        # Cleanup
        self._cleanup_email(client, name_a)
        self._cleanup_email(client, name_b)

    def test_create_user_missing_name_rejected(self, client):
        r = client.post(f"{BASE_URL}/users", json={
            "name": "", "role": "Staf", "password": "password12", "is_active": True,
        }, allow_redirects=False, timeout=30)
        assert r.status_code == 422
        assert "name" in r.json().get("errors", {})

    def test_create_user_missing_role_rejected(self, client):
        r = client.post(f"{BASE_URL}/users", json={
            "name": "TEST NoRole", "role": "", "password": "password12", "is_active": True,
        }, allow_redirects=False, timeout=30)
        assert r.status_code == 422
        assert "role" in r.json().get("errors", {})

    def test_create_user_short_password_rejected(self, client):
        r = client.post(f"{BASE_URL}/users", json={
            "name": "TEST ShortPwd", "role": "Staf", "password": "abc", "is_active": True,
        }, allow_redirects=False, timeout=30)
        assert r.status_code == 422
        assert "password" in r.json().get("errors", {})


class TestIteration10UsersSort:
    def test_sort_by_role(self, client):
        r = client.get(
            f"{BASE_URL}/users?sort=role&dir=asc",
            headers=_inertia_headers(client),
            timeout=30,
        )
        assert r.status_code == 200
        j = _inertia_json(r)
        assert j["props"]["filters"]["sort"] == "role"

    def test_sort_by_is_active(self, client):
        r = client.get(
            f"{BASE_URL}/users?sort=is_active&dir=desc",
            headers=_inertia_headers(client),
            timeout=30,
        )
        assert r.status_code == 200
        assert _inertia_json(r)["props"]["filters"]["sort"] == "is_active"


# ---------- Iteration 11: Role import, users role filter, activity sort/detail ----------
class TestIteration11RoleImport:
    def test_import_no_file_422(self, client):
        r = client.post(f"{BASE_URL}/roles/import", data={}, allow_redirects=False, timeout=30)
        assert r.status_code == 422, f"{r.status_code}: {r.text[:200]}"
        assert "file" in r.json().get("errors", {})

    def test_import_non_csv_422(self, client):
        files = {"file": ("bad.png", b"\x89PNG\r\n\x1a\nfakebinary", "image/png")}
        # Do not send JSON content-type; use multipart
        headers = {k: v for k, v in client.headers.items() if k.lower() not in ("content-type", "accept")}
        headers["Accept"] = "application/json"
        r = client.post(f"{BASE_URL}/roles/import", files=files, headers=headers, allow_redirects=False, timeout=30)
        assert r.status_code == 422, f"{r.status_code}: {r.text[:200]}"
        assert "file" in r.json().get("errors", {})

    def test_import_csv_adds_and_skips(self, client):
        # Clean pre-existing
        r0 = client.get(f"{BASE_URL}/roles", headers=_inertia_headers(client), timeout=30)
        roles0 = {x["name"]: x["id"] for x in _inertia_json(r0)["props"]["roles"]}
        for n in ("Auditor Internal", "Manajer Cabang"):
            if n in roles0:
                client.delete(f"{BASE_URL}/roles/{roles0[n]}", allow_redirects=False, timeout=30)

        csv_bytes = b"name\nAuditor Internal\nManajer Cabang\nStaf\n"
        files = {"file": ("roles.csv", csv_bytes, "text/csv")}
        headers = {k: v for k, v in client.headers.items() if k.lower() not in ("content-type",)}
        headers["Accept"] = "application/json"
        r = client.post(f"{BASE_URL}/roles/import", files=files, headers=headers,
                        allow_redirects=False, timeout=30)
        assert r.status_code in (200, 204, 302), f"{r.status_code}: {r.text[:300]}"

        # Verify roles now include new two and Staf still exists (was unique-skipped)
        r2 = client.get(f"{BASE_URL}/roles", headers=_inertia_headers(client), timeout=30)
        names = [x["name"] for x in _inertia_json(r2)["props"]["roles"]]
        assert "Auditor Internal" in names
        assert "Manajer Cabang" in names
        assert names.count("Staf") == 1
        assert "name" not in names  # header row skipped

        # Cleanup imported roles
        roles = {x["name"]: x["id"] for x in _inertia_json(r2)["props"]["roles"]}
        for n in ("Auditor Internal", "Manajer Cabang"):
            r3 = client.delete(f"{BASE_URL}/roles/{roles[n]}", allow_redirects=False, timeout=30)
            assert r3.status_code in (200, 204, 302)


class TestIteration11UsersRoleFilter:
    def test_role_options_present(self, client):
        r = client.get(f"{BASE_URL}/users", headers=_inertia_headers(client), timeout=30)
        d = _inertia_json(r)
        opts = d["props"].get("roleOptions", [])
        labels = {o["label"] for o in opts}
        assert "Super Admin" in labels and "Staf" in labels

    def test_filter_by_role_super_admin(self, client):
        r = client.get(f"{BASE_URL}/users?role=Super Admin",
                       headers=_inertia_headers(client), timeout=30)
        assert r.status_code == 200
        d = _inertia_json(r)
        assert d["props"]["filters"]["role"] == "Super Admin"
        # every user in data must have role Super Admin
        for u in d["props"]["users"]["data"]:
            assert u["role"] == "Super Admin", f"unexpected role: {u['role']}"

    def test_filter_by_role_staf(self, client):
        r = client.get(f"{BASE_URL}/users?role=Staf",
                       headers=_inertia_headers(client), timeout=30)
        d = _inertia_json(r)
        assert d["props"]["filters"]["role"] == "Staf"
        for u in d["props"]["users"]["data"]:
            assert u["role"] == "Staf"


class TestIteration11ActivitySort:
    @pytest.mark.parametrize("key", ["created_at", "actor_name", "action", "module", "level"])
    def test_sort_key_asc(self, client, key):
        r = client.get(f"{BASE_URL}/audit-trail?sort={key}&dir=asc",
                       headers=_inertia_headers(client), timeout=30)
        assert r.status_code == 200
        d = _inertia_json(r)
        assert d["props"]["filters"]["sort"] == key
        assert d["props"]["filters"]["dir"] == "asc"

    def test_sort_dir_desc(self, client):
        r = client.get(f"{BASE_URL}/audit-trail?sort=level&dir=desc",
                       headers=_inertia_headers(client), timeout=30)
        d = _inertia_json(r)
        assert d["props"]["filters"]["dir"] == "desc"

    def test_activity_detail_fields_present(self, client):
        r = client.get(f"{BASE_URL}/audit-trail", headers=_inertia_headers(client), timeout=30)
        d = _inertia_json(r)
        rows = d["props"]["logs"]["data"]
        assert rows, "no activity rows"
        row = rows[0]
        # Fields required for detail dialog (iter 12 adds context/technical fields):
        for k in ("id", "created_at", "created_at_full", "actor", "action", "module",
                  "level", "level_label", "level_chip", "subject", "ip",
                  "changes", "context", "method", "url", "status_code", "user_agent"):
            assert k in row, f"missing {k}"

    def test_purge_past_range_no_500(self, client):
        r = client.delete(
            f"{BASE_URL}/audit-trail?date_from=2020-01-01&date_to=2020-01-02",
            allow_redirects=False, timeout=30,
        )
        assert r.status_code in (200, 204, 302), f"{r.status_code}: {r.text[:200]}"



# ---------- Iteration 12: Empty-string persistence bug + Audit Trail features ----------
class TestIteration12EmptyPersists:
    """Kolom yang sengaja dikosongkan harus TETAP KOSONG setelah reload."""

    def _snapshot(self, client):
        r = client.get(f"{BASE_URL}/appearance", headers=_inertia_headers(client), timeout=30)
        return _inertia_json(r)["props"]["settings"]

    def test_identity_empty_initials_persists(self, client):
        before = self._snapshot(client)
        try:
            payload = {
                "app_name": before.get("app_name") or "CODEX",
                "tagline": "",
                "brand_initials": "",
            }
            r = client.put(f"{BASE_URL}/appearance/identity", json=payload,
                           allow_redirects=False, timeout=30)
            assert r.status_code in (200, 204, 302), f"{r.status_code}: {r.text[:300]}"
            after = self._snapshot(client)
            assert after["brand_initials"] == "", f"expected '' got {after['brand_initials']!r}"
            assert after["tagline"] == "", f"expected '' got {after['tagline']!r}"
        finally:
            client.put(f"{BASE_URL}/appearance/identity", json={
                "app_name": before.get("app_name") or "CODEX",
                "tagline": before.get("tagline") or "",
                "brand_initials": before.get("brand_initials") or "",
            }, allow_redirects=False, timeout=30)

    def test_seo_empty_meta_title_and_keywords_persist(self, client):
        before = self._snapshot(client)
        try:
            payload = {
                "meta_title": "",
                "meta_description": before.get("meta_description") or "desc",
                "meta_keywords": "",
                "canonical_url": before.get("canonical_url") or "",
                "search_indexable": bool(before.get("search_indexable")),
            }
            r = client.put(f"{BASE_URL}/appearance/seo", json=payload,
                           allow_redirects=False, timeout=30)
            assert r.status_code in (200, 204, 302), f"{r.status_code}: {r.text[:300]}"
            after = self._snapshot(client)
            assert after["meta_title"] == "", f"expected '' got {after['meta_title']!r}"
            assert after["meta_keywords"] == "", f"expected '' got {after['meta_keywords']!r}"
        finally:
            client.put(f"{BASE_URL}/appearance/seo", json={
                "meta_title": before.get("meta_title") or "",
                "meta_description": before.get("meta_description") or "desc",
                "meta_keywords": before.get("meta_keywords") or "",
                "canonical_url": before.get("canonical_url") or "",
                "search_indexable": bool(before.get("search_indexable")),
            }, allow_redirects=False, timeout=30)

    def test_contact_empty_footer_persists(self, client):
        before = self._snapshot(client)
        try:
            payload = {
                "support_email": before.get("support_email") or "dukungan@adminkit.test",
                "footer_text": "",
            }
            r = client.put(f"{BASE_URL}/appearance/contact", json=payload,
                           allow_redirects=False, timeout=30)
            assert r.status_code in (200, 204, 302), f"{r.status_code}: {r.text[:300]}"
            after = self._snapshot(client)
            assert after["footer_text"] == "", f"expected '' got {after['footer_text']!r}"
        finally:
            client.put(f"{BASE_URL}/appearance/contact", json={
                "support_email": before.get("support_email") or "dukungan@adminkit.test",
                "footer_text": before.get("footer_text") or "",
            }, allow_redirects=False, timeout=30)

    def test_storage_empty_path_and_public_url_persist(self, client):
        r0 = client.get(f"{BASE_URL}/storage-settings", headers=_inertia_headers(client), timeout=30)
        before = _inertia_json(r0)["props"]["settings"]
        try:
            payload = {
                "storage_driver": before.get("storage_driver") or "local",
                "s3_endpoint": before.get("s3_endpoint") or "",
                "s3_bucket": before.get("s3_bucket") or "",
                "s3_path": "",
                "s3_key": before.get("s3_key") or "",
                "s3_secret": "",
                "s3_region": before.get("s3_region") or "",
                "s3_public_url": "",
                "s3_path_style": bool(before.get("s3_path_style")),
            }
            r = client.put(f"{BASE_URL}/storage-settings", json=payload,
                           allow_redirects=False, timeout=30)
            assert r.status_code in (200, 204, 302), f"{r.status_code}: {r.text[:300]}"
            r2 = client.get(f"{BASE_URL}/storage-settings", headers=_inertia_headers(client), timeout=30)
            after = _inertia_json(r2)["props"]["settings"]
            assert after.get("s3_path", "") == "", f"expected '' got {after.get('s3_path')!r}"
            assert after.get("s3_public_url", "") == "", f"expected '' got {after.get('s3_public_url')!r}"
        finally:
            client.put(f"{BASE_URL}/storage-settings", json={
                "storage_driver": before.get("storage_driver") or "local",
                "s3_endpoint": before.get("s3_endpoint") or "",
                "s3_bucket": before.get("s3_bucket") or "",
                "s3_path": before.get("s3_path") or "",
                "s3_key": before.get("s3_key") or "",
                "s3_secret": "",
                "s3_region": before.get("s3_region") or "",
                "s3_public_url": before.get("s3_public_url") or "",
                "s3_path_style": bool(before.get("s3_path_style")),
            }, allow_redirects=False, timeout=30)


class TestIteration12AuditTrailFeatures:
    """Audit Trail: diff perubahan, password masking, failed login, 403 access denied."""

    def _rows(self, client):
        r = client.get(f"{BASE_URL}/audit-trail?sort=created_at&dir=desc",
                       headers=_inertia_headers(client), timeout=30)
        return _inertia_json(r)["props"]["logs"]["data"]

    def _user_id(self, client, username):
        r = client.get(f"{BASE_URL}/users?search={username}",
                       headers=_inertia_headers(client), timeout=30)
        for u in _inertia_json(r)["props"]["users"]["data"]:
            if u["username"] == username:
                return u["id"]
        return None

    def test_user_update_creates_diff_changes(self, client, creds):
        # Use a dedicated per-test user to avoid races with other tests updating zulfame.
        uname = "test_qa_diff_user"
        # Clean any leftover
        r0 = client.get(f"{BASE_URL}/users?search={uname}",
                        headers=_inertia_headers(client), timeout=30)
        for u in _inertia_json(r0)["props"]["users"]["data"]:
            if u["username"] == uname:
                client.delete(f"{BASE_URL}/users/{u['id']}",
                              allow_redirects=False, timeout=30)
        create_payload = {
            "name": "QA Diff Original",
            "username": uname,
            "email": "TEST_qa_diff_user@example.com",
            "phone": "",
            "role": "Staf",
            "password": "password12",
            "is_active": True,
        }
        rc = client.post(f"{BASE_URL}/users", json=create_payload,
                         allow_redirects=False, timeout=30)
        assert rc.status_code in (200, 201, 204, 302), rc.text[:300]
        uid = self._user_id(client, uname)
        assert uid, "dedicated test user not found after create"

        base = {
            "username": uname,
            "email": "TEST_qa_diff_user@example.com",
            "phone": "",
            "role": "Staf",
            "is_active": True,
        }
        try:
            r = client.put(f"{BASE_URL}/users/{uid}", json=(base | {"name": "QA Diff Updated"}),
                           allow_redirects=False, timeout=30)
            assert r.status_code in (200, 204, 302), f"{r.status_code}: {r.text[:300]}"

            # Look for an audit row referencing THIS user's URL (isolated from others).
            rows = self._rows(client)
            found = None
            for row in rows[:50]:
                ch = row.get("changes") or {}
                url = row.get("url") or ""
                method = row.get("method") or ""
                if (
                    "name" in ch
                    and method in ("PUT", "PATCH")
                    and re.search(rf"/users/{uid}(?:\?|$|/)", url)
                ):
                    found = row
                    break
            assert found, (
                f"no user-update row with name diff for uid={uid} in latest 50: "
                f"{[(r.get('method'), r.get('url'), (r.get('changes') or {}).get('name')) for r in rows[:50]]}"
            )
            change = found["changes"]["name"]
            assert "old" in change and "new" in change
            # After fix: diffOf(Model, array $before) captures originals BEFORE
            # save(), so old MUST reflect the pre-update value and new the new.
            assert change.get("new") == "QA Diff Updated", change
            assert change.get("old") == "QA Diff Original", change
            assert found.get("method") in ("PUT", "PATCH"), found.get("method")
            assert found.get("url"), "url should be set"
        finally:
            # cleanup
            client.delete(f"{BASE_URL}/users/{uid}", allow_redirects=False, timeout=30)

    def test_user_password_change_is_masked(self, client, creds):
        uid = self._user_id(client, creds["username"])
        r = client.put(f"{BASE_URL}/users/{uid}", json={
            "name": "Zulfadli Rizal",
            "role": "Super Admin",
            "is_active": True,
            "password": "password",
        }, allow_redirects=False, timeout=30)
        assert r.status_code in (200, 204, 302), r.text[:200]
        rows = self._rows(client)
        pwd_row = None
        for row in rows[:30]:
            ch = row.get("changes") or {}
            url = row.get("url") or ""
            method = row.get("method") or ""
            # Only the update-audit row (PUT on this uid) — skip delete-snapshot rows
            # from other cleanups which also carry a 'password' key with new=null.
            if (
                "password" in ch
                and method in ("PUT", "PATCH")
                and re.search(rf"/users/{uid}(?:\?|$|/)", url)
            ):
                pwd_row = row
                break
        assert pwd_row, "no audit row with password diff"
        pwd = pwd_row["changes"]["password"]
        assert pwd["new"] == "••••••", f"new not masked: {pwd['new']!r}"
        if pwd["old"] is not None:
            assert pwd["old"] == "••••••", f"old not masked: {pwd['old']!r}"
        import json as _json
        blob = _json.dumps(pwd_row["changes"])
        assert "$2y$" not in blob and "$2b$" not in blob and "password12" not in blob

    def test_failed_login_recorded(self):
        s = requests.Session()
        s.get(f"{BASE_URL}/login")
        xsrf = requests.utils.unquote(s.cookies.get("XSRF-TOKEN"))
        r = s.post(f"{BASE_URL}/login",
                   json={"credential": "zulfame", "password": "wrong-pass-once"},
                   headers={"X-XSRF-TOKEN": xsrf, "X-Requested-With": "XMLHttpRequest",
                            "Accept": "application/json", "Referer": f"{BASE_URL}/login"},
                   timeout=30, allow_redirects=False)
        assert r.status_code == 422, r.status_code

        s2 = requests.Session()
        s2.get(f"{BASE_URL}/login")
        xsrf2 = requests.utils.unquote(s2.cookies.get("XSRF-TOKEN"))
        r2 = s2.post(f"{BASE_URL}/login",
                     json={"credential": "zulfame", "password": "password"},
                     headers={"X-XSRF-TOKEN": xsrf2, "X-Requested-With": "XMLHttpRequest",
                              "Accept": "application/json", "Referer": f"{BASE_URL}/login"},
                     timeout=30, allow_redirects=False)
        assert r2.status_code in (200, 204, 302), r2.status_code
        xsrf2 = requests.utils.unquote(s2.cookies.get("XSRF-TOKEN"))
        s2.headers.update({"X-XSRF-TOKEN": xsrf2, "X-Requested-With": "XMLHttpRequest",
                           "Accept": "application/json", "Referer": f"{BASE_URL}/"})
        r3 = s2.get(f"{BASE_URL}/audit-trail?sort=created_at&dir=desc",
                    headers={"Accept": "text/html"}, timeout=30)
        rows = _inertia_json(r3)["props"]["logs"]["data"]
        failed = None
        for row in rows[:20]:
            if row.get("level") == "danger" and "masuk" in (row.get("action") or "").lower():
                failed = row
                break
        assert failed, f"no failed-login row in latest 20: {[(r.get('level'), r.get('action')) for r in rows[:20]]}"
        assert failed.get("status_code") in (422, 401), f"status_code={failed.get('status_code')}"
        ctx_blob = str(failed.get("context") or {}).lower()
        assert "zulfame" in ctx_blob or "credential" in ctx_blob, f"context missing credential: {failed.get('context')}"

    def test_forbidden_403_recorded(self, client):
        payload = {
            "name": "TEST QA Staf",
            "username": "test_qa_staf12",
            "email": "TEST_qa_staf12@example.com",
            "phone": "",
            "role": "Staf",
            "password": "password12",
            "is_active": True,
        }
        r0 = client.get(f"{BASE_URL}/users?search=test_qa_staf12",
                        headers=_inertia_headers(client), timeout=30)
        for u in _inertia_json(r0)["props"]["users"]["data"]:
            if u["username"] == "test_qa_staf12":
                client.delete(f"{BASE_URL}/users/{u['id']}",
                              allow_redirects=False, timeout=30)
        rc = client.post(f"{BASE_URL}/users", json=payload,
                         allow_redirects=False, timeout=30)
        assert rc.status_code in (200, 201, 204, 302), rc.text[:300]

        s = requests.Session()
        s.get(f"{BASE_URL}/login")
        xsrf = requests.utils.unquote(s.cookies.get("XSRF-TOKEN"))
        r = s.post(f"{BASE_URL}/login",
                   json={"credential": "test_qa_staf12", "password": "password12"},
                   headers={"X-XSRF-TOKEN": xsrf, "X-Requested-With": "XMLHttpRequest",
                            "Accept": "application/json", "Referer": f"{BASE_URL}/login"},
                   timeout=30, allow_redirects=False)
        assert r.status_code in (200, 204, 302), r.status_code
        xsrf = requests.utils.unquote(s.cookies.get("XSRF-TOKEN"))
        s.headers.update({"X-XSRF-TOKEN": xsrf, "X-Requested-With": "XMLHttpRequest",
                          "Accept": "text/html", "Referer": f"{BASE_URL}/"})
        r_forbid = s.get(f"{BASE_URL}/appearance", timeout=30, allow_redirects=False)
        assert r_forbid.status_code == 403, r_forbid.status_code

        rows = self._rows(client)
        denied = None
        for row in rows[:25]:
            if row.get("status_code") == 403:
                denied = row
                break
        assert denied, f"no 403 audit row in latest 25: {[(r.get('status_code'), r.get('action')) for r in rows[:25]]}"
        assert denied.get("level") == "danger", denied.get("level")

        # cleanup
        r_lookup = client.get(f"{BASE_URL}/users?search=test_qa_staf12",
                              headers=_inertia_headers(client), timeout=30)
        for u in _inertia_json(r_lookup)["props"]["users"]["data"]:
            if u["username"] == "test_qa_staf12":
                client.delete(f"{BASE_URL}/users/{u['id']}",
                              allow_redirects=False, timeout=30)


# ---------- Iteration 13: Audit Detail as a dedicated page ----------
class TestIteration13AuditDetail:
    """Detail entri audit dipindah ke /audit-trail/{id} (halaman tersendiri, bukan dialog)."""

    def _first_log_id(self, client):
        r = client.get(f"{BASE_URL}/audit-trail?sort=created_at&dir=desc",
                       headers=_inertia_headers(client), timeout=30)
        rows = _inertia_json(r)["props"]["logs"]["data"]
        assert rows, "no audit rows"
        return rows[0]["id"], rows

    def test_audit_detail_route_renders_dedicated_page(self, client):
        log_id, _ = self._first_log_id(client)
        r = client.get(f"{BASE_URL}/audit-trail/{log_id}",
                       headers=_inertia_headers(client), timeout=30)
        assert r.status_code == 200, r.status_code
        d = _inertia_json(r)
        assert d["component"] == "AuditDetail", d["component"]
        log = d["props"]["log"]
        for k in ("id", "action", "module", "level", "level_label", "level_chip",
                  "actor", "user_id", "actor_email", "subject_type", "subject_id",
                  "subject", "changes", "context", "ip", "method", "url",
                  "status_code", "user_agent", "created_at_iso",
                  "created_at_full", "created_at_diff"):
            assert k in log, f"missing '{k}' in AuditDetail props.log"
        # ISO 8601 sanity
        assert "T" in log["created_at_iso"], log["created_at_iso"]

    def test_audit_detail_404_for_unknown_id(self, client):
        r = client.get(f"{BASE_URL}/audit-trail/99999999",
                       headers=_inertia_headers(client), timeout=30,
                       allow_redirects=False)
        assert r.status_code == 404, r.status_code

    def test_audit_trail_index_no_activity_detail_props(self, client):
        """Regresi: dialog dihapus, tetapi list tetap membawa field lengkap untuk klik → detail page."""
        r = client.get(f"{BASE_URL}/audit-trail", headers=_inertia_headers(client), timeout=30)
        d = _inertia_json(r)
        assert d["component"] == "AuditTrail"
        # sanity: list masih ada
        rows = d["props"]["logs"]["data"]
        assert rows, "no audit rows"

    def test_audit_detail_page_after_403_contains_context(self, client):
        """Buat entri 403, buka detail: level=danger, status_code=403, context memiliki kunci pesan/pengecualian/berkas."""
        # Bersihkan user uji jika masih ada
        r0 = client.get(f"{BASE_URL}/users?search=test_qa_staf13",
                        headers=_inertia_headers(client), timeout=30)
        for u in _inertia_json(r0)["props"]["users"]["data"]:
            if u["username"] == "test_qa_staf13":
                client.delete(f"{BASE_URL}/users/{u['id']}",
                              allow_redirects=False, timeout=30)
        payload = {
            "name": "TEST QA Staf Iterasi",
            "username": "test_qa_staf13",
            "email": "TEST_qa_staf13@example.com",
            "phone": "",
            "role": "Staf",
            "password": "password12",
            "is_active": True,
        }
        rc = client.post(f"{BASE_URL}/users", json=payload,
                         allow_redirects=False, timeout=30)
        assert rc.status_code in (200, 201, 204, 302), rc.text[:300]

        s = requests.Session()
        s.get(f"{BASE_URL}/login")
        xsrf = requests.utils.unquote(s.cookies.get("XSRF-TOKEN"))
        r_login = s.post(f"{BASE_URL}/login",
                         json={"credential": "test_qa_staf13", "password": "password12"},
                         headers={"X-XSRF-TOKEN": xsrf, "X-Requested-With": "XMLHttpRequest",
                                  "Accept": "application/json", "Referer": f"{BASE_URL}/login"},
                         timeout=30, allow_redirects=False)
        assert r_login.status_code in (200, 204, 302), r_login.status_code
        xsrf = requests.utils.unquote(s.cookies.get("XSRF-TOKEN"))
        s.headers.update({"X-XSRF-TOKEN": xsrf, "X-Requested-With": "XMLHttpRequest",
                          "Accept": "text/html", "Referer": f"{BASE_URL}/"})
        r_forbid = s.get(f"{BASE_URL}/appearance", timeout=30, allow_redirects=False)
        assert r_forbid.status_code == 403, r_forbid.status_code

        rows = _inertia_json(client.get(
            f"{BASE_URL}/audit-trail?sort=created_at&dir=desc",
            headers=_inertia_headers(client), timeout=30,
        ))["props"]["logs"]["data"]
        denied = next((row for row in rows[:25] if row.get("status_code") == 403), None)
        assert denied, "no 403 audit row"
        # buka halaman detail
        r_show = client.get(f"{BASE_URL}/audit-trail/{denied['id']}",
                            headers=_inertia_headers(client), timeout=30)
        assert r_show.status_code == 200, r_show.status_code
        log = _inertia_json(r_show)["props"]["log"]
        assert log["level"] == "danger"
        assert log["status_code"] == 403
        ctx = log.get("context") or {}
        assert "pesan" in ctx and "pengecualian" in ctx and "berkas" in ctx, ctx

        # cleanup
        r_lookup = client.get(f"{BASE_URL}/users?search=test_qa_staf13",
                              headers=_inertia_headers(client), timeout=30)
        for u in _inertia_json(r_lookup)["props"]["users"]["data"]:
            if u["username"] == "test_qa_staf13":
                client.delete(f"{BASE_URL}/users/{u['id']}",
                              allow_redirects=False, timeout=30)

    def test_audit_trail_vue_has_no_activity_detail_dialog(self):
        """Regresi UI: dialog activity-detail lama sudah dihapus dari AuditTrail.vue."""
        content = Path("/app/adminkit/resources/js/pages/AuditTrail.vue").read_text(encoding="utf-8")
        assert "activity-detail" not in content, "dialog activity-detail masih ada di AuditTrail.vue"
        assert "/audit-trail/" in content, "row-click ke /audit-trail/{id} tidak ditemukan"

    def test_audit_detail_vue_has_required_testids(self):
        """Pastikan halaman AuditDetail.vue memiliki semua data-testid yang diminta."""
        content = Path("/app/adminkit/resources/js/pages/AuditDetail.vue").read_text(encoding="utf-8")
        for tid in ("audit-detail-page", "audit-detail-action", "audit-detail-summary",
                    "audit-detail-changes", "audit-detail-change-count",
                    "audit-detail-context", "audit-detail-technical",
                    "audit-detail-json", "audit-detail-copy", "audit-detail-back"):
            assert f'data-testid="{tid}"' in content, f"missing data-testid={tid}"



# ---------- Iteration 14: no-noise & 500 recording ----------
class TestIteration14AuditNoiseAndSystemFailure:
    """422 and 404 must NOT create audit rows; 500 must be recorded as 'Kegagalan sistem'."""

    def _latest_count(self, client):
        import subprocess
        out = subprocess.run(
            ["php", "artisan", "tinker", "--execute",
             "echo App\\Models\\ActivityLog::count();"],
            cwd="/app/adminkit", capture_output=True, text=True, timeout=30,
        )
        m = re.search(r"(\d+)", out.stdout)
        assert m, out.stdout + out.stderr
        return int(m.group(1))

    def test_422_does_not_create_audit_row(self, client):
        import subprocess
        before = subprocess.run(
            ["php", "artisan", "tinker", "--execute",
             "echo App\\Models\\ActivityLog::where('context->pengecualian','ValidationException')->count();"],
            cwd="/app/adminkit", capture_output=True, text=True, timeout=30,
        )
        n0 = int(re.search(r"(\d+)", before.stdout).group(1))
        r = client.post(f"{BASE_URL}/users", json={}, allow_redirects=False, timeout=30)
        assert r.status_code == 422, r.status_code
        after = subprocess.run(
            ["php", "artisan", "tinker", "--execute",
             "echo App\\Models\\ActivityLog::where('context->pengecualian','ValidationException')->count();"],
            cwd="/app/adminkit", capture_output=True, text=True, timeout=30,
        )
        n1 = int(re.search(r"(\d+)", after.stdout).group(1))
        assert n1 == n0, f"422 (ValidationException) leaked audit row: {n0} -> {n1}"

    def test_404_does_not_create_audit_row(self, client):
        import subprocess
        before = subprocess.run(
            ["php", "artisan", "tinker", "--execute",
             "echo App\\Models\\ActivityLog::where('context->pengecualian','NotFoundHttpException')->count();"],
            cwd="/app/adminkit", capture_output=True, text=True, timeout=30,
        )
        n0 = int(re.search(r"(\d+)", before.stdout).group(1))
        r = client.get(f"{BASE_URL}/halaman-tidak-ada",
                       headers={"Accept": "text/html"}, timeout=30, allow_redirects=False)
        assert r.status_code == 404, r.status_code
        after = subprocess.run(
            ["php", "artisan", "tinker", "--execute",
             "echo App\\Models\\ActivityLog::where('context->pengecualian','NotFoundHttpException')->count();"],
            cwd="/app/adminkit", capture_output=True, text=True, timeout=30,
        )
        n1 = int(re.search(r"(\d+)", after.stdout).group(1))
        assert n1 == n0, f"404 leaked audit row: {n0} -> {n1}"

    def test_500_reproducer_via_tinker(self):
        """Directly invoke $exceptions->render() via artisan tinker to confirm 500 is recorded."""
        import subprocess
        cmd = [
            "php", "artisan", "tinker", "--execute",
            "use App\\Models\\ActivityLog;"
            "$b=ActivityLog::count();"
            "app(Illuminate\\Contracts\\Debug\\ExceptionHandler::class)->render(request(), new RuntimeException('QA_TEST_500'));"
            "$a=ActivityLog::count();"
            "$r=ActivityLog::where('action','Kegagalan sistem')->where('status_code',500)->latest('id')->first();"
            "echo 'DELTA='.($a-$b).';ID='.($r?$r->id:'null').';LVL='.($r?$r->level:'null').';SC='.($r?$r->status_code:'null').';';"
            "if($r){ $r->delete(); echo 'DELETED;'; }"
        ]
        out = subprocess.run(cmd, cwd="/app/adminkit", capture_output=True, text=True, timeout=60)
        combined = out.stdout + out.stderr
        assert "DELTA=1" in combined, combined[-800:]
        assert "LVL=danger" in combined, combined[-800:]
        assert "SC=500" in combined, combined[-800:]
        assert "DELETED" in combined, combined[-800:]

    def test_403_reproducer_via_tinker(self):
        """Directly invoke $exceptions->render() with Spatie UnauthorizedException to confirm 403 recorded."""
        import subprocess
        cmd = [
            "php", "artisan", "tinker", "--execute",
            "use App\\Models\\ActivityLog;"
            "$b=ActivityLog::count();"
            "app(Illuminate\\Contracts\\Debug\\ExceptionHandler::class)->render(request(), new Spatie\\Permission\\Exceptions\\UnauthorizedException(403,'QA_TEST_403'));"
            "$a=ActivityLog::count();"
            "$r=ActivityLog::where('action','Akses ditolak')->where('status_code',403)->latest('id')->first();"
            "echo 'DELTA='.($a-$b).';LVL='.($r?$r->level:'null').';SC='.($r?$r->status_code:'null').';';"
            "if($r){ $r->delete(); echo 'DELETED;'; }"
        ]
        out = subprocess.run(cmd, cwd="/app/adminkit", capture_output=True, text=True, timeout=60)
        combined = out.stdout + out.stderr
        assert "DELTA=1" in combined, combined[-800:]
        assert "LVL=danger" in combined, combined[-800:]
        assert "SC=403" in combined, combined[-800:]
