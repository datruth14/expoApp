"""Exercise the real PHP app using a disposable SQLite database and HTTP server."""

import base64
import csv
import http.cookiejar
from http.server import BaseHTTPRequestHandler, HTTPServer
import io
import json
import os
from pathlib import Path
import re
import socket
import sqlite3
import subprocess
import tempfile
import threading
import time
import urllib.error
import urllib.parse
import urllib.request


ROOT = Path(__file__).resolve().parents[1]


class KingsChatMock(BaseHTTPRequestHandler):
    calls = []

    def log_message(self, *args):
        pass

    def reply(self, status, payload):
        self.send_response(status)
        self.send_header('Content-Type', 'application/json')
        self.end_headers()
        self.wfile.write(json.dumps(payload).encode())

    def do_POST(self):
        self.calls.append(('POST', self.path))
        payload = json.loads(self.rfile.read(int(self.headers.get('Content-Length', 0))))
        if self.path != '/developer/api/oauth2/token' or payload.get('grant_type') != 'code' or payload.get('client_id') != 'test-client':
            self.reply(400, {'error': 'invalid_request'})
        elif payload.get('code') not in ['valid-code', 'bad-profile-code', 'short-code']:
            self.reply(400, {'error': 'invalid_code'})
        else:
            self.reply(200, {'access_token': 'profile-invalid' if payload['code'] == 'bad-profile-code' else 'verified-token',
                             'refresh_token': 'never-store-this-token',
                             'expires_in_millis': 1000 if payload['code'] == 'short-code' else 3600000})

    def do_GET(self):
        self.calls.append(('GET', self.path))
        if self.path != '/developer/api/user/profile' or self.headers.get('api-key') != 'test-api-key':
            self.reply(403, {'error': 'forbidden'})
        elif self.headers.get('Authorization') != 'Bearer verified-token':
            self.reply(401, {'error': 'unauthorized'})
        else:
            self.reply(200, {'profile': {'id': 'kc-test-user-1', 'username': 'verified.delegate', 'name': 'Verified Delegate'}})


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args):
        return None


def run():
    with tempfile.TemporaryDirectory(prefix="expo-offline-") as temporary:
        database = Path(temporary) / "test.db"
        with socket.socket() as listener:
            listener.bind(("127.0.0.1", 0))
            port = listener.getsockname()[1]
        base = f"http://127.0.0.1:{port}"
        provider = HTTPServer(('127.0.0.1', 0), KingsChatMock)
        provider_thread = threading.Thread(target=provider.serve_forever, daemon=True)
        provider_thread.start()
        environment = dict(os.environ, DB_DRIVER="sqlite", SQLITE_PATH=str(database), APP_ENV='test',
                           KINGSCHAT_CLIENT_ID='test-client', KINGSCHAT_API_KEY='test-api-key',
                           KINGSCHAT_API_BASE_URL=f'http://127.0.0.1:{provider.server_port}')
        php_options = ['-d', 'disable_classes=finfo'] if os.environ.get('TEST_WITHOUT_FILEINFO') == '1' else []
        def launch(config):
            return subprocess.Popen(
                ["php", *php_options, "-S", f"127.0.0.1:{port}", "local-router.php"],
                cwd=ROOT, env=config, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL,
            )

        server = launch(environment)

        def wait_for_server():
            for _ in range(100):
                try:
                    with socket.create_connection(("127.0.0.1", port), timeout=0.1):
                        return
                except OSError:
                    if server.poll() is not None:
                        raise RuntimeError("PHP server did not start")
                    time.sleep(0.05)
            raise RuntimeError('PHP server did not become ready')

        def restart(config):
            nonlocal server
            server.terminate()
            server.wait(timeout=10)
            server = launch(config)
            wait_for_server()
        cookies = http.cookiejar.CookieJar()
        client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cookies))
        no_redirect_client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cookies), NoRedirect())
        banner_file = None

        def request(path, fields=None, upload=False, follow=True):
            headers = {}
            data = None
            if fields is not None:
                if upload:
                    boundary = "expo-test-boundary"
                    pieces = []
                    for key, value in fields.items():
                        pieces.append(
                            f'--{boundary}\r\nContent-Disposition: form-data; name="{key}"\r\n\r\n{value}\r\n'.encode()
                        )
                    png = base64.b64decode("iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/l9sAAAAASUVORK5CYII=")
                    pieces.append(
                        f'--{boundary}\r\nContent-Disposition: form-data; name="banner"; filename="test.png"\r\nContent-Type: image/png\r\n\r\n'.encode()
                        + png + f"\r\n--{boundary}--\r\n".encode()
                    )
                    data = b"".join(pieces)
                    headers["Content-Type"] = f"multipart/form-data; boundary={boundary}"
                else:
                    data = urllib.parse.urlencode(fields).encode()
            try:
                response = (client if follow else no_redirect_client).open(urllib.request.Request(base + path, data=data, headers=headers), timeout=30)
            except urllib.error.HTTPError as error:
                response = error
            return response.status, response.read().decode("utf-8-sig", errors="replace"), response.geturl() if follow else response.headers.get('Location', response.geturl())

        def token(page):
            return re.search(r'name="csrf_token" value="([^"]+)"', page).group(1)

        def begin_sign_in():
            status, _, location = request('/delegate-login.php?program=offline-second&area=Food%20Court%20Experience', follow=False)
            assert status == 302 and location.startswith('https://accounts.kingschat.online/log-in?'), location
            query = urllib.parse.parse_qs(urllib.parse.urlsplit(location).query)
            assert query['clientId'] == ['test-client']
            return query['origin'][0]

        def complete_sign_in(state, code='valid-code'):
            return request('/delegate-callback.php?' + urllib.parse.urlencode({'origin': state, 'code': code}))

        try:
            wait_for_server()
            status, home, _ = request("/")
            assert status == 200 and "Loveword Expo 2026" in home, home
            assert 'href="admin' not in home and "localStorage" not in home
            assert 'assets/loading.js' in home and 'assets/loading.css' in home
            for asset in ['/assets/loading.js', '/assets/loading.css']:
                assert request(asset)[0] == 200, asset
            assert database.exists(), "SQLite database was not created"
            for private in ["/storage/test.db", "/config/settings.php", "/.git/config", "/database_schema_sqlite.sql"]:
                assert request(private)[0] == 404, private
            assert request("/admin/export-feedback.php?program_id=1")[2].endswith("/admin/login.php")
            assert request('/submit-feedback.php', {'csrf_token': token(home)})[0] == 401
            assert 'class="panel hidden" id="feedbackForm"' in home
            assert request('/delegate-callback.php', {'code': 'forged', 'origin': 'forged'})[0] == 405
            assert request('/delegate-login.php?program=loveword-expo-2026&area=invalid')[0] == 422
            unavailable_environment = dict(environment, KINGSCHAT_CLIENT_ID='', KINGSCHAT_API_KEY='')
            unavailable = subprocess.run(['php', '-r',
                '$_SERVER["REQUEST_METHOD"]="GET"; $_GET=["program"=>"loveword-expo-2026", "area"=>"Overall event experience"]; register_shutdown_function(static function () { fwrite(STDERR, "HTTP=" . http_response_code()); }); require "delegate-login.php";'],
                cwd=ROOT, env=unavailable_environment, text=True, capture_output=True, check=True)
            assert 'name="phone"' in unavailable.stdout and 'Temporary access uses the details you provide' in unavailable.stdout
            assert 'HTTP=503' not in unavailable.stderr
            blocked_mock = subprocess.run(['php', '-r',
                'require "includes/bootstrap.php"; try { kingschat_api_request("/developer/api/user/profile"); exit(1); } catch (RuntimeException $error) { echo $error->getMessage(); }'],
                cwd=ROOT, env=dict(environment, APP_ENV='production'), text=True, capture_output=True, check=True)
            assert 'official HTTPS endpoint' in blocked_mock.stdout, 'Production must reject the test provider URL'

            status, login, login_url = request("/admin")
            assert status == 200 and login_url.endswith('/admin/'), login_url
            assert '../assets/loading.js' in login
            status, dashboard, _ = request("/admin/", {
                "csrf_token": token(login), "username": "admin", "password": "admin12345",
            })
            assert status == 200 and "Program manager" in dashboard, dashboard
            assert request("/admin")[2].endswith("/admin/programs.php")
            assert request("/")[0] == 200, "Admin cannot return to homepage"
            assert request('/submit-feedback.php', {'csrf_token': token(dashboard)})[0] == 401, 'Admin login must not grant delegate access'

            _, editor, _ = request("/admin/program-edit.php")
            fields = {
                "csrf_token": token(editor), "name": "Offline second program", "slug": "offline-second",
                "platform_title": "Delegate Experience Centre", "tagline": "Offline test",
                "description": "Second program description", "welcome_message": "Welcome offline",
                "primary_color": "#123c7a", "accent_color": "#e5ad36", "status": "published",
            }
            with sqlite3.connect(database) as connection:
                connection.execute("CREATE TRIGGER reject_test_save BEFORE INSERT ON programs BEGIN SELECT RAISE(ABORT, 'Simulated database save failure'); END")
            banners_before = set((ROOT / 'uploads/banners').glob('*'))
            status, failed_save, _ = request('/admin/program-edit.php', fields, upload=True)
            assert status == 200 and 'Unable to save the program.' in failed_save, failed_save
            assert 'Simulated database save failure' in failed_save
            assert 'value="Offline second program"' in failed_save, 'Entered values must be preserved'
            assert set((ROOT / 'uploads/banners').glob('*')) == banners_before, 'Failed save left an orphan banner'
            with sqlite3.connect(database) as connection:
                connection.execute('DROP TRIGGER reject_test_save')
            status, saved, _ = request("/admin/program-edit.php", fields, upload=True)
            assert status == 200 and "Offline second program" in saved, saved
            with sqlite3.connect(database) as connection:
                second_id, banner_path = connection.execute("SELECT id, banner_path FROM programs WHERE slug = 'offline-second'").fetchone()
                banner_file = ROOT / banner_path
                assert banner_file.exists(), "Banner upload was not persisted"
                services = connection.execute('SELECT services_json FROM programs WHERE id = ?', (second_id,)).fetchone()[0]
                assert services.isascii() and json.loads(services)[0][2] == '🎪'
            assert request("/" + banner_path)[0] == 200
            _, second_home, _ = request("/program.php?slug=offline-second")
            assert banner_path in second_home
            # Upgrade an existing offline database without losing user-created programs.
            with sqlite3.connect(database) as connection:
                connection.execute('DROP INDEX feedback_delegate_idx')
                connection.execute('ALTER TABLE feedback DROP COLUMN delegate_id')
                connection.execute('DROP TABLE delegates')
                connection.execute("INSERT INTO feedback (program_id, service_area, rating, is_anonymous, went_well, comments) VALUES (1, 'Venue Experience', 4, 1, 'Legacy feedback', '')")
                connection.execute('DROP TABLE platform_settings')
                connection.execute('PRAGMA user_version = 1')
            assert 'Loveword Expo 2026' in request('/')[1]
            with sqlite3.connect(database) as connection:
                assert connection.execute('SELECT COUNT(*) FROM programs').fetchone()[0] == 2
                assert connection.execute('PRAGMA user_version').fetchone()[0] == 4
                assert connection.execute('SELECT went_well, delegate_id FROM feedback').fetchall() == [('Legacy feedback', None)]
                connection.execute('DELETE FROM feedback')
            assert request('/admin/programs.php', {'csrf_token': 'invalid', 'home_program_id': second_id})[0] == 400
            status, selected, _ = request('/admin/programs.php', {
                'csrf_token': token(dashboard), 'home_program_id': second_id,
            })
            assert status == 200 and 'The homepage program has been updated.' in selected, selected
            assert 'Offline second program' in request('/')[1]
            assert 'Loveword Expo 2026' in request('/program.php?slug=loveword-expo-2026')[1]
            _, editor, _ = request(f"/admin/program-edit.php?id={second_id}")
            fields["csrf_token"] = token(editor)
            fields["welcome_message"] = "Updated offline welcome"
            status, _, _ = request(f"/admin/program-edit.php?id={second_id}", fields)
            assert status == 200
            assert "Updated offline welcome" in request("/program.php?slug=offline-second")[1]

            assert request('/delegate-callback.php?code=valid-code&origin=forged')[0] == 400
            state = begin_sign_in()
            calls = len(KingsChatMock.calls)
            assert complete_sign_in('wrong-state')[0] == 400
            assert len(KingsChatMock.calls) == calls, 'Invalid state must not reach provider APIs'
            assert complete_sign_in(state, 'invalid-code')[0] == 502
            assert complete_sign_in(state)[0] == 400, 'Callback state must be one-time'
            assert complete_sign_in(begin_sign_in(), 'bad-profile-code')[0] == 502
            assert request('/submit-feedback.php', {'csrf_token': token(dashboard)})[0] == 401
            state = begin_sign_in()
            status, signed_in_page, callback_url = complete_sign_in(state)
            assert status == 200 and '@verified.delegate' in signed_in_page, signed_in_page
            assert 'area=Food%20Court%20Experience' in callback_url and '"initialArea":"Food Court Experience"' in signed_in_page
            assert all(secret not in signed_in_page for secret in ['verified-token', 'never-store-this-token', 'test-api-key'])
            assert complete_sign_in(state)[0] == 400
            request('/delegate-logout.php', {'csrf_token': token(signed_in_page)})
            assert 'Program manager' in request('/admin/programs.php')[1], 'Delegate logout must not log out admin'
            assert request('/submit-feedback.php', {'csrf_token': token(dashboard)})[0] == 401
            status, second_home, _ = complete_sign_in(begin_sign_in())
            with sqlite3.connect(database) as connection:
                assert connection.execute('SELECT COUNT(*) FROM delegates').fetchone()[0] == 1, 'Repeat login must reuse delegate'

            feedback = {
                "csrf_token": token(second_home), "program_slug": "offline-second",
                "area": "Food Court Experience", "rating": 5, "name": "Private name",
                "anonymous": "on", "positive": "=1+1", "comments": "Helpful staff, thank you!",
            }
            status, result, _ = request("/submit-feedback.php", feedback)
            assert status == 201 and json.loads(result)["ok"], result
            status, _, _ = request("/submit-feedback.php", dict(feedback, csrf_token="invalid"))
            assert status == 400
            status, _, _ = request("/submit-feedback.php", dict(feedback, rating=6))
            assert status == 422
            status, _, _ = request("/submit-feedback.php", dict(feedback, area="Unknown area"))
            assert status == 422
            with sqlite3.connect(database) as connection:
                stored = connection.execute("SELECT program_id, delegate_name, rating, comments, delegate_id FROM feedback").fetchall()
                delegate_id = connection.execute('SELECT id FROM delegates').fetchone()[0]
                assert stored == [(second_id, None, 5, "Helpful staff, thank you!", delegate_id)], stored
            status, review, _ = request(f"/admin/feedback.php?program_id={second_id}")
            assert status == 200 and "Helpful staff, thank you!" in review and "Anonymous" in review
            assert "Helpful staff, thank you!" not in request("/admin/feedback.php?program_id=1")[1]
            status, exported, _ = request(f"/admin/export-feedback.php?program_id={second_id}")
            rows = list(csv.reader(io.StringIO(exported)))
            assert status == 200 and len(rows) == 2 and rows[1][2] == "Anonymous", rows
            assert rows[1][5] == "'=1+1", rows
            assert "Private name" not in exported
            assert 'verified.delegate' not in exported, 'Anonymous reports must hide KingsChat handles'
            assert 'verified.delegate' not in review
            identified = dict(feedback, name='Forged name', delegate_id=999999, comments='Identified feedback')
            identified.pop('anonymous')
            assert request('/submit-feedback.php', identified)[0] == 201
            identified_csv = list(csv.reader(io.StringIO(request(f'/admin/export-feedback.php?program_id={second_id}')[1])))
            assert any(row[2] == 'Verified Delegate' and row[-2] == 'verified.delegate' and row[-1] == 'KingsChat verified' for row in identified_csv[1:])
            assert 'Forged name' not in str(identified_csv)
            assert len(list(csv.reader(io.StringIO(request("/admin/export-feedback.php?program_id=1")[1])))) == 1
            fields['status'] = 'draft'
            request(f'/admin/program-edit.php?id={second_id}', fields)
            assert 'Loveword Expo 2026' in request('/')[1], 'Homepage must not display a draft'
            assert request('/admin/programs.php', {
                'csrf_token': token(dashboard), 'home_program_id': second_id,
            })[0] == 422
            fields['status'] = 'published'
            request(f'/admin/program-edit.php?id={second_id}', fields)
            request("/admin/logout.php", {"csrf_token": token(dashboard)})
            assert request(f"/admin/export-feedback.php?program_id={second_id}")[2].endswith("/admin/login.php")
            assert 'Offline second program' in request('/')[1], 'Public visitors must see the selected homepage'
            assert '@verified.delegate' in request('/program.php?slug=offline-second&area=Food%20Court%20Experience')[1], 'Admin logout must not log out delegate'
            assert request('/admin/programs.php', {'home_program_id': 1})[2].endswith('/admin/login.php')
            request('/delegate-logout.php', {'csrf_token': token(second_home)})
            complete_sign_in(begin_sign_in(), 'short-code')
            time.sleep(1.1)
            assert request('/submit-feedback.php', feedback)[0] == 401, 'Expired delegate session must be rejected'
            assert request('/')[0] == 200, 'Expired sessions must still be able to browse'

            # Simulate the previous delegate schema with real linked feedback.
            # The v4 SQLite rebuild must preserve provider IDs and associations.
            with sqlite3.connect(database) as connection:
                old_links = connection.execute('SELECT id, delegate_id FROM feedback ORDER BY id').fetchall()
                connection.executescript('''
                    CREATE TABLE delegates_previous (
                      id INTEGER PRIMARY KEY AUTOINCREMENT, kingschat_user_id TEXT NOT NULL UNIQUE,
                      handle TEXT NOT NULL, display_name TEXT NOT NULL,
                      created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                      updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
                    );
                    INSERT INTO delegates_previous (id, kingschat_user_id, handle, display_name, created_at, updated_at)
                    SELECT id, kingschat_user_id, handle, display_name, created_at, updated_at FROM delegates;
                    DROP TABLE delegates;
                    ALTER TABLE delegates_previous RENAME TO delegates;
                    PRAGMA user_version = 3;
                ''')
            restart(unavailable_environment)
            _, fallback_home, _ = request('/program.php?slug=offline-second&area=Food%20Court%20Experience')
            assert 'Enter username and phone' in fallback_home
            with sqlite3.connect(database) as connection:
                assert connection.execute('PRAGMA user_version').fetchone()[0] == 4
                assert connection.execute('PRAGMA foreign_key_check').fetchall() == []
                assert connection.execute('SELECT id, delegate_id FROM feedback ORDER BY id').fetchall() == old_links
                assert connection.execute('SELECT kingschat_user_id, auth_method FROM delegates').fetchall() == [('kc-test-user-1', 'kingschat')]

            fallback_path = '/delegate-login.php?program=offline-second&area=Food%20Court%20Experience'
            status, manual_form, _ = request(fallback_path)
            assert status == 200 and 'name="username"' in manual_form and 'name="phone"' in manual_form
            manual_fields = {'csrf_token': token(manual_form), 'program': 'offline-second', 'area': 'Food Court Experience',
                             'username': '@Verified.Delegate', 'phone': '+234 (801) 234-5678'}
            assert request('/delegate-login.php', dict(manual_fields, csrf_token='bad'))[0] == 400
            assert request('/delegate-login.php', dict(manual_fields, phone='not-a-phone'))[0] == 422
            assert request('/delegate-login.php', dict(manual_fields, username='<script>'))[0] == 422
            provider_calls_before = len(KingsChatMock.calls)
            status, manual_page, destination = request('/delegate-login.php', manual_fields)
            assert status == 200 and 'Temporary access as' in manual_page and '(unverified)' in manual_page
            assert 'area=Food%20Court%20Experience' in destination
            assert '+2348012345678' not in manual_page
            with sqlite3.connect(database) as connection:
                manual_id, provider_id, phone = connection.execute("SELECT id, kingschat_user_id, phone FROM delegates WHERE auth_method = 'manual'").fetchone()
                assert provider_id is None and phone == '+2348012345678'
                assert manual_id != delegate_id, 'A matching typed username must not overwrite a verified account'
            manual_feedback = dict(identified, csrf_token=token(manual_page), comments='Temporary delegate feedback')
            assert request('/submit-feedback.php', manual_feedback)[0] == 201
            _, admin_login, _ = request('/admin/')
            _, manual_dashboard, _ = request('/admin/', {'csrf_token': token(admin_login), 'username': 'admin', 'password': 'admin12345'})
            manual_csv = request(f'/admin/export-feedback.php?program_id={second_id}')[1]
            assert any(row[-1] == 'Manual (unverified)' and row[-2] == 'verified.delegate' for row in csv.reader(io.StringIO(manual_csv)))
            assert '+2348012345678' not in manual_csv
            assert 'Manual (unverified)' in request(f'/admin/feedback.php?program_id={second_id}')[1]
            request('/delegate-logout.php', {'csrf_token': token(manual_dashboard)})
            request('/delegate-login.php', manual_fields)
            with sqlite3.connect(database) as connection:
                assert connection.execute("SELECT id FROM delegates WHERE auth_method = 'manual'").fetchall() == [(manual_id,)], 'Repeated normalized details should reuse the manual record'
            assert len(KingsChatMock.calls) == provider_calls_before, 'Manual access must not contact KingsChat'

            # A Client ID alone is insufficient; complete credentials enable KC.
            restart(dict(unavailable_environment, KINGSCHAT_CLIENT_ID='test-client'))
            assert 'Temporary access as' in request('/program.php?slug=offline-second&area=Food%20Court%20Experience')[1]
            restart(environment)
            assert request('/submit-feedback.php', manual_feedback)[0] == 401, 'Activation must reject existing manual sessions'
            status, _, next_login = request('/delegate-login.php', manual_fields, follow=False)
            assert status == 303 and next_login.startswith('delegate-login.php?'), next_login
            assert 'Continue with KingsChat' in request('/program.php?slug=offline-second&area=Food%20Court%20Experience')[1]
            assert complete_sign_in(begin_sign_in())[0] == 200
            with sqlite3.connect(database) as connection:
                assert connection.execute('SELECT COUNT(*) FROM delegates').fetchone()[0] == 2
                assert connection.execute('SELECT delegate_id FROM feedback WHERE comments = ?', ('Temporary delegate feedback',)).fetchone()[0] == manual_id
            print("PASS: public browsing, KingsChat verification, unverified username/phone fallback, automatic activation and stale-session rejection, legacy migrations, identity separation, feedback/privacy/CSV, program/banner management, and private-file protection.")
        finally:
            server.terminate()
            server.wait(timeout=10)
            provider.shutdown()
            provider.server_close()
            provider_thread.join(timeout=5)
            if banner_file and banner_file.exists():
                banner_file.unlink()


if __name__ == "__main__":
    run()
