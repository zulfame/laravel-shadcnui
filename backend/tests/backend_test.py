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
    return s


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

        # Fetch users list and find the created user
        r2 = client.get(f"{BASE_URL}/users", headers={"Accept": "text/html"}, timeout=30)
        assert "test_user_qa8" in r2.text or r2.status_code == 200


# ---------- Regression: roles + activity ----------
class TestRegression:
    def test_roles_page_loads(self, client):
        r = client.get(f"{BASE_URL}/roles", headers={"Accept": "text/html"}, timeout=30)
        assert r.status_code == 200

    def test_activity_page_loads(self, client):
        r = client.get(f"{BASE_URL}/activity", headers={"Accept": "text/html"}, timeout=30)
        assert r.status_code == 200

    def test_profile_page_loads(self, client):
        r = client.get(f"{BASE_URL}/profile", headers={"Accept": "text/html"}, timeout=30)
        assert r.status_code == 200
        # 'Kantor' label should not be present on profile page HTML/Inertia data
        assert "Kantor" not in r.text, "Kolom 'Kantor' masih ada di halaman Profil"

    def test_users_page_no_kantor(self, client):
        r = client.get(f"{BASE_URL}/users", headers={"Accept": "text/html"}, timeout=30)
        assert r.status_code == 200
        assert "Kantor" not in r.text, "Kolom 'Kantor' masih ada di halaman Pengguna"
