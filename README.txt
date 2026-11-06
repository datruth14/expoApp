DELEGATE EXPERIENCE CENTRE – PHP / MYSQLI + OFFLINE SQLITE

RUN OFFLINE (NO CPANEL OR MYSQL REQUIRED)
1. Install PHP 8.1+ with pdo_sqlite, cURL, mbstring, Fileinfo, and sessions enabled.
2. From the project directory, run:
   DB_DRIVER=sqlite php -S 127.0.0.1:8000 local-router.php
3. Open http://127.0.0.1:8000/ for the homepage or http://127.0.0.1:8000/admin for admin login.
4. Log in as admin using admin / admin12345. Programs, delegates, and feedback persist in storage/experience.db inside this project. The first request creates the tables and seeds Loveword Expo 2026 automatically. No SQL import is needed offline.
5. Uploaded banner files stay in uploads/banners. Restarting the PHP server preserves the database and banner files.

Optional: copy config/offline.example.php to config/secrets.php to select SQLite without an environment variable, then run php -S 127.0.0.1:8000 local-router.php. An existing config/secrets.php overrides environment settings; set its db_driver to sqlite if it already exists. Use the router so the local server blocks downloads of the .db and config files.

Browsing and admin management work offline. While KingsChat credentials are missing, delegates can use the temporary username-and-phone form offline; these entries are explicitly unverified. Actual KingsChat verification requires internet and registered application credentials. The integration test uses a private local provider mock instead of real KingsChat accounts.

MOVING TO LIVE CPANEL
- Set db_driver to mysql in config/secrets.php using config/secrets.example.php as a guide, enter the cPanel database credentials, and import the MySQL schema as described below.
- SQLite offline data and cPanel MySQL data are separate. Switching drivers does not transfer offline records. Back up storage/experience.db if you want to keep your offline data.
- Keep the SQLite .db file private and out of Git. It is ignored; the SQL schema and automatic initialization code are committed instead.

PROJECT CONTENTS
- index.php: displays the admin-selected homepage program for visitors and signed-in admins. The root .htaccess selects this entry point; index.html remains a fallback redirect.
- program.php and templates/program.php: the public Delegate Experience Centre UI used by every program.
- admin/: admin login, program manager, per-program feedback viewer, and CSV export.
- submit-feedback.php: validates and saves public feedback to MySQLi.
- delegate-login.php, delegate-callback.php, delegate-logout.php and includes/kingschat.php: KingsChat sign-in with server-side authorization-code exchange and verified delegate sessions.
- uploads/banners/: admin-uploaded program banners (the directory must be writable by PHP).
- config/: server-side MySQLi settings and deployment example. Keep real credentials private.
- database_schema_mysql.sql: full schema and initial Loveword Expo 2026 program seed for a new database.
- database_schema_sqlite.sql: SQLite schema used automatically in offline mode.
- includes/database.php: shared prepared-query interface for MySQLi and SQLite.
- storage/experience.db: automatically created offline SQLite database (not committed).
- local-router.php: offline PHP-server router that blocks access to private files.
- assets/loading.css and assets/loading.js: shared button spinners and shimmer indicators for public/admin actions, forms, navigation, and CSV downloads; bundled locally for offline use.
- database_upgrade_v2.sql: one-time migration for databases made from the earlier starter schema.
- database_upgrade_v3.sql: adds persistent homepage selection to an existing MySQL database.
- database_upgrade_v4.sql: one-time migration adding delegates and linking new feedback to verified KingsChat accounts.
- database_upgrade_v5.sql: one-time MySQL migration supporting temporary username-and-phone access and authentication-method labels.
- database_upgrade_sqlite_v4.sql: internal, automatic SQLite delegate-table upgrade; do not import this file into MySQL.
- database_schema_postgresql.sql: original PostgreSQL proposal, retained for reference only.
- agent.md: project conventions for future work.

CPANEL SETUP
1. Confirm PHP 8.1+ and the MySQLi, cURL, Fileinfo, mbstring, and session extensions are enabled.
2. Upload the project files to your cPanel account, normally under public_html (or the document root assigned to your domain). Keep the directory structure intact.
3. In cPanel > MySQL Databases, create a database and database user, assign the user to the database with the required privileges, and note the full prefixed names and database host.
4. For a new database, import database_schema_mysql.sql through phpMyAdmin. It creates the tables and the first published program, “Loveword Expo 2026”.
5. For an existing database, take a backup and apply only the migrations not already installed: v2 for the original starter, v3 for homepage selection, v4 for KingsChat delegates, and database_upgrade_v5.sql for temporary access. Do not rerun v2, v4, or v5 after their columns already exist. New databases created from the current full schema need no upgrades. Existing SQLite databases upgrade automatically on the next request and retain old feedback and verified delegate associations.
6. Copy config/secrets.example.php to config/secrets.php on the cPanel account and enter the cPanel database host, full database name, full database username, and database user's password. Keep secrets.php private.
7. Ensure uploads/banners is writable by PHP and serve the site over HTTPS.
8. Open /admin to sign in.

TEMPORARY DELEGATE ACCESS AND AUTOMATIC ACTIVATION
- When either kingschat_client_id or kingschat_api_key is missing, “Rate this service” offers a username-and-phone form. Enter a username and international phone number (with country code). The normalized details are stored server-side; no OTP or KingsChat ownership verification is performed.
- Temporary delegate records have auth_method = manual, no KingsChat user ID, and are labeled “Manual (unverified)” in the feedback viewer and CSV. Phone numbers are not included in public pages or standard feedback exports.
- Once both Client ID and API key are configured, the UI automatically switches to “Continue with KingsChat”. Existing manual sessions must sign in again through KingsChat; stale fallback forms cannot create more manual sessions. No extra switch is required.
- A Client ID alone does not activate verified login. Configure the API key and callback too. Once active, provider errors do not silently fall back to manual access.
- Matching typed usernames are never merged with verified KingsChat accounts. Historical manual feedback retains its original unverified attribution.

KINGSCHAT DELEGATE SIGN-IN SETUP
1. Register a project/application at https://developers.kingschat.online/ (https://developer.kingsch.at/ also opens the portal).
2. Obtain its Client ID and project API key, and enable the profile access required by the user profile API.
3. Set the app's callback/redirect URL to https://experience.ministryprograms.org/delegate-callback.php and callback delivery to REDIRECT. The integration uses a top-level GET callback, not FORM_POST, so the SameSite=Lax session cookie can validate the sign-in state.
4. Add these entries to the existing config/secrets.php, keeping its database/admin settings:
   'kingschat_client_id' => 'YOUR_CLIENT_ID',
   'kingschat_api_key' => 'YOUR_PROJECT_API_KEY',
5. Back up the database and apply the missing v4/v5 upgrades for an existing MySQL database before deploying this version. For SQLite, upgrades are automatic.
6. Browse a program, choose a service, click “Rate this service”, then “Continue with KingsChat”. After verification, the same service's feedback form opens with the KingsChat handle shown.

For actual localhost login, use a separate developer application/callback if KingsChat permits local callbacks; otherwise test authentication on registered HTTPS staging. Do not use someone else's Client ID. No KingsChat password, access token, or refresh token is stored in the app database or browser storage. See docs/kingschat.md for flow details and references. Real-provider end-to-end testing requires your own credentials.

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
- Visitors can browse freely. Feedback requires a delegate session: verified KingsChat when configured, or temporary username-and-phone access while credentials are missing. Admin login alone never authorizes delegate feedback.
- Feedback is saved to the selected program and the current delegate ID. Admins can open “Feedback & CSV” to filter/review submissions or export CSV, including usernames and their verified/unverified access method. KingsChat-mode names/handles always come from the verified profile.
- Delegates can hide their name/handle in feedback views and CSV exports; this does not remove the internal delegate association. Old feedback remains readable with no delegate association. Admin and delegate logout actions are independent.
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
The same checks cover manual username/phone validation, normalized record reuse, migration of existing verified users/feedback, report labels, automatic activation, stale manual-session rejection, and separation of manual records from verified accounts.
