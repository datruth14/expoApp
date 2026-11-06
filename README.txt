DELEGATE EXPERIENCE CENTRE – PHP / MYSQLI + OFFLINE SQLITE

RUN OFFLINE (NO CPANEL OR MYSQL REQUIRED)
1. Install PHP 8.1+ with pdo_sqlite, mbstring, Fileinfo, and sessions enabled.
2. From the project directory, run:
   DB_DRIVER=sqlite php -S 127.0.0.1:8000 local-router.php
3. Open http://127.0.0.1:8000/ for the homepage or http://127.0.0.1:8000/admin for admin login.
4. Log in using admin / admin12345. Programs and feedback persist in storage/experience.db inside this project. The first request creates both tables and seeds Loveword Expo 2026 automatically. No SQL import is needed offline.
5. Uploaded banner files stay in uploads/banners. Restarting the PHP server preserves the database and banner files.

Optional: copy config/offline.example.php to config/secrets.php to select SQLite without an environment variable, then run php -S 127.0.0.1:8000 local-router.php. An existing config/secrets.php overrides environment settings; set its db_driver to sqlite if it already exists. Use the router so the local server blocks downloads of the .db and config files.

MOVING TO LIVE CPANEL
- Set db_driver to mysql in config/secrets.php using config/secrets.example.php as a guide, enter the cPanel database credentials, and import the MySQL schema as described below.
- SQLite offline data and cPanel MySQL data are separate. Switching drivers does not transfer offline records. Back up storage/experience.db if you want to keep your offline data.
- Keep the SQLite .db file private and out of Git. It is ignored; the SQL schema and automatic initialization code are committed instead.

PROJECT CONTENTS
- index.php: displays the admin-selected homepage program for visitors and signed-in admins. The root .htaccess selects this entry point; index.html remains a fallback redirect.
- program.php and templates/program.php: the public Delegate Experience Centre UI used by every program.
- admin/: admin login, program manager, per-program feedback viewer, and CSV export.
- submit-feedback.php: validates and saves public feedback to MySQLi.
- uploads/banners/: admin-uploaded program banners (the directory must be writable by PHP).
- config/: server-side MySQLi settings and deployment example. Keep real credentials private.
- database_schema_mysql.sql: full schema and initial Loveword Expo 2026 program seed for a new database.
- database_schema_sqlite.sql: SQLite schema used automatically in offline mode.
- includes/database.php: shared prepared-query interface for MySQLi and SQLite.
- storage/experience.db: automatically created offline SQLite database (not committed).
- local-router.php: offline PHP-server router that blocks access to private files.
- database_upgrade_v2.sql: one-time migration for databases made from the earlier starter schema.
- database_upgrade_v3.sql: adds persistent homepage selection to an existing MySQL database.
- database_schema_postgresql.sql: original PostgreSQL proposal, retained for reference only.
- agent.md: project conventions for future work.

CPANEL SETUP
1. Confirm PHP 8.1+ and the MySQLi, Fileinfo, mbstring, and session extensions are enabled.
2. Upload the project files to your cPanel account, normally under public_html (or the document root assigned to your domain). Keep the directory structure intact.
3. In cPanel > MySQL Databases, create a database and database user, assign the user to the database with the required privileges, and note the full prefixed names and database host.
4. For a new database, import database_schema_mysql.sql through phpMyAdmin. It creates the tables and the first published program, “Loveword Expo 2026”.
5. For an existing database, take a backup. Run database_upgrade_v2.sql once if it still uses the earlier starter's schema, then import database_upgrade_v3.sql to enable homepage selection. A new database created from the current full schema needs neither upgrade. Existing SQLite databases upgrade automatically on the next request.
6. Copy config/secrets.example.php to config/secrets.php on the cPanel account and enter the cPanel database host, full database name, full database username, and database user's password. Keep secrets.php private.
7. Ensure uploads/banners is writable by PHP and serve the site over HTTPS.
8. Open /admin to sign in.

INITIAL ADMIN LOGIN
- Username: admin
- Password: admin12345
- Change this before public deployment. Generate a new password hash using:
  php -r 'echo password_hash("your-new-password", PASSWORD_DEFAULT), PHP_EOL;'
  Set it as ADMIN_PASSWORD_HASH in the hosting environment or as admin_password_hash in config/secrets.php.

PROGRAMS AND FEEDBACK
- In /admin, open the program manager and click “Set as homepage” beside a published program. That program immediately appears at the site root (experience.ministryprograms.org) for visitors. The selection is saved in the database and marked “Homepage” in the manager.
- The initial homepage is Loveword Expo 2026. If no homepage is selected, or the selected program becomes a draft, the oldest published program is shown instead. Draft programs cannot be selected as the homepage. Other published programs remain available at their own program.php?slug= URLs.
- Sign in to create additional programs. Each gets the same Delegate Experience Centre UI, its own URL, banner, colors, and separate feedback records.
- Edit a program to upload or remove its JPG, PNG, or WebP banner (maximum 5 MB). Banner files are stored on the cPanel account in uploads/banners; program settings and feedback records are stored in the cPanel MySQL database.
- Public feedback is saved to the selected program. Admins can open a program's “Feedback & CSV” page to filter and review submissions or download its CSV export.
- The current PHP app stores no application data in the browser. Offline structured data stays in storage/experience.db. In live MySQL mode, all application files and uploaded banners stay on the cPanel account and program/feedback data stays in its MySQL database.

SECURITY NOTES
- Never commit or place config/secrets.php in browser-accessible source. It is excluded by .gitignore and the config directory is denied by .htaccess.
- Use HTTPS. Replace the requested initial admin password before making the site public.
- Feedback management is admin-only; submissions are program-scoped, queries are parameterized, forms use CSRF protection, and exported CSV cells are guarded against spreadsheet formulas.

HOMEPAGE / DATABASE TROUBLESHOOTING
- If the homepage is unavailable, open /admin and check the program manager's database diagnostic. Confirm MySQLi (mysqli or nd_mysqli) is enabled for this domain's PHP version.
- MySQL 1044/1045: verify the full cPanel database/user names, password, and the user's database assignment/privileges. MySQL 2002/2003/2005: verify the host/port with the hosting provider and check the MySQL service.
- MySQL 1146/1054: import the schema or the appropriate upgrade as described above.
- Public failures are logged with a support reference in the cPanel PHP error log and return HTTP 503 without exposing database details to visitors. A Git push alone does not configure the hosting database or run SQL imports.
- If saving a program fails, the authenticated edit form displays the error and support reference while preserving your text. Check the database user's INSERT/UPDATE permissions, column sizes/schema, and character set. New program service JSON uses Unicode escapes for older MySQL character sets; banners can also be validated when Fileinfo is unavailable.

OFFLINE VERIFICATION
Run python3 tests/offline_smoke.py to test the actual PHP HTTP routes with a disposable SQLite database: login, program creation/editing, banner upload, feedback validation/storage/privacy, program separation, CSV export, and private-file protection. Requires PHP and Python 3.
Run TEST_WITHOUT_FILEINFO=1 python3 tests/offline_smoke.py to also exercise banner uploads on hosts without the Fileinfo class. The checks cover database-save failures, preserved form values, and cleanup of failed uploads.
