"""Exercise the real PHP app using a disposable SQLite database and HTTP server."""

import base64
import csv
import http.cookiejar
import io
import json
import os
from pathlib import Path
import re
import socket
import sqlite3
import subprocess
import tempfile
import time
import urllib.error
import urllib.parse
import urllib.request


ROOT = Path(__file__).resolve().parents[1]


def run():
    with tempfile.TemporaryDirectory(prefix="expo-offline-") as temporary:
        database = Path(temporary) / "test.db"
        with socket.socket() as listener:
            listener.bind(("127.0.0.1", 0))
            port = listener.getsockname()[1]
        base = f"http://127.0.0.1:{port}"
        environment = dict(os.environ, DB_DRIVER="sqlite", SQLITE_PATH=str(database))
        server = subprocess.Popen(
            ["php", "-S", f"127.0.0.1:{port}", "local-router.php"],
            cwd=ROOT, env=environment, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL,
        )
        client = urllib.request.build_opener(
            urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar())
        )
        banner_file = None

        def request(path, fields=None, upload=False):
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
                response = client.open(urllib.request.Request(base + path, data=data, headers=headers), timeout=10)
            except urllib.error.HTTPError as error:
                response = error
            return response.status, response.read().decode("utf-8-sig", errors="replace"), response.geturl()

        def token(page):
            return re.search(r'name="csrf_token" value="([^"]+)"', page).group(1)

        try:
            for _ in range(100):
                try:
                    with socket.create_connection(("127.0.0.1", port), timeout=0.1):
                        break
                except OSError:
                    if server.poll() is not None:
                        raise RuntimeError("PHP server did not start")
                    time.sleep(0.05)
            status, home, _ = request("/")
            assert status == 200 and "Loveword Expo 2026" in home, home
            assert 'href="admin' not in home and "localStorage" not in home
            assert database.exists(), "SQLite database was not created"
            for private in ["/storage/test.db", "/config/settings.php", "/.git/config", "/database_schema_sqlite.sql"]:
                assert request(private)[0] == 404, private
            assert request("/admin/export-feedback.php?program_id=1")[2].endswith("/admin/login.php")

            status, login, _ = request("/admin/")
            assert status == 200
            status, dashboard, _ = request("/admin/", {
                "csrf_token": token(login), "username": "admin", "password": "admin12345",
            })
            assert status == 200 and "Program manager" in dashboard, dashboard
            assert request("/")[0] == 200, "Admin cannot return to homepage"

            _, editor, _ = request("/admin/program-edit.php")
            fields = {
                "csrf_token": token(editor), "name": "Offline second program", "slug": "offline-second",
                "platform_title": "Delegate Experience Centre", "tagline": "Offline test",
                "description": "Second program description", "welcome_message": "Welcome offline",
                "primary_color": "#123c7a", "accent_color": "#e5ad36", "status": "published",
            }
            status, saved, _ = request("/admin/program-edit.php", fields, upload=True)
            assert status == 200 and "Offline second program" in saved, saved
            with sqlite3.connect(database) as connection:
                second_id, banner_path = connection.execute("SELECT id, banner_path FROM programs WHERE slug = 'offline-second'").fetchone()
                banner_file = ROOT / banner_path
                assert banner_file.exists(), "Banner upload was not persisted"
            assert request("/" + banner_path)[0] == 200
            _, second_home, _ = request("/program.php?slug=offline-second")
            assert banner_path in second_home
            _, editor, _ = request(f"/admin/program-edit.php?id={second_id}")
            fields["csrf_token"] = token(editor)
            fields["welcome_message"] = "Updated offline welcome"
            status, _, _ = request(f"/admin/program-edit.php?id={second_id}", fields)
            assert status == 200
            assert "Updated offline welcome" in request("/program.php?slug=offline-second")[1]

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
                stored = connection.execute("SELECT program_id, delegate_name, rating, comments FROM feedback").fetchall()
                assert stored == [(second_id, None, 5, "Helpful staff, thank you!")], stored
            status, review, _ = request(f"/admin/feedback.php?program_id={second_id}")
            assert status == 200 and "Helpful staff, thank you!" in review and "Anonymous" in review
            assert "Helpful staff, thank you!" not in request("/admin/feedback.php?program_id=1")[1]
            status, exported, _ = request(f"/admin/export-feedback.php?program_id={second_id}")
            rows = list(csv.reader(io.StringIO(exported)))
            assert status == 200 and len(rows) == 2 and rows[1][2] == "Anonymous", rows
            assert rows[1][5] == "'=1+1", rows
            assert "Private name" not in exported
            assert len(list(csv.reader(io.StringIO(request("/admin/export-feedback.php?program_id=1")[1])))) == 1
            request("/admin/logout.php", {"csrf_token": token(dashboard)})
            assert request(f"/admin/export-feedback.php?program_id={second_id}")[2].endswith("/admin/login.php")
            print("PASS: offline initialization, login, public homepage, program create/edit, banner upload, feedback persistence/validation, anonymity, program separation, CSV export, logout, and private-file protection.")
        finally:
            server.terminate()
            server.wait(timeout=10)
            if banner_file and banner_file.exists():
                banner_file.unlink()


if __name__ == "__main__":
    run()
