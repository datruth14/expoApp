DELEGATE EXPERIENCE CENTRE – PHP / MYSQLI PROGRAM TEMPLATE

PROJECT CONTENTS
- index.php: public homepage for visitors and signed-in admins. The root .htaccess selects this entry point; index.html remains a fallback redirect.
- program.php and templates/program.php: the public Delegate Experience Centre UI used by every program.
- admin/: admin login, program manager, per-program feedback viewer, and CSV export.
- submit-feedback.php: validates and saves public feedback to MySQLi.
- uploads/banners/: admin-uploaded program banners (the directory must be writable by PHP).
- config/: server-side MySQLi settings and deployment example. Keep real credentials private.
- database_schema_mysql.sql: full schema and initial Loveword Expo 2026 program seed for a new database.
- database_upgrade_v2.sql: one-time migration for databases made from the earlier starter schema.
- database_schema_postgresql.sql: original PostgreSQL proposal, retained for reference only.
- agent.md: project conventions for future work.

CPANEL SETUP
1. Confirm PHP 8.1+ and the MySQLi, Fileinfo, mbstring, and session extensions are enabled.
2. Upload the project files to your cPanel account, normally under public_html (or the document root assigned to your domain). Keep the directory structure intact.
3. In cPanel > MySQL Databases, create a database and database user, assign the user to the database with the required privileges, and note the full prefixed names and database host.
4. For a new database, import database_schema_mysql.sql through phpMyAdmin. It creates the tables and the first published program, “Loveword Expo 2026”.
5. If the database was created using the earlier starter's schema, take a backup and run database_upgrade_v2.sql once instead. Do not run the upgrade after importing the current full schema.
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
- The root page opens the first published program. The initial program is Loveword Expo 2026.
- Sign in to create additional programs. Each gets the same Delegate Experience Centre UI, its own URL, banner, colors, and separate feedback records.
- Edit a program to upload or remove its JPG, PNG, or WebP banner (maximum 5 MB). Banner files are stored on the cPanel account in uploads/banners; program settings and feedback records are stored in the cPanel MySQL database.
- Public feedback is saved to the selected program. Admins can open a program's “Feedback & CSV” page to filter and review submissions or download its CSV export.
- The current PHP app stores no application data in the browser. All application files and uploaded banners stay on the cPanel account; structured program and feedback data stays in its MySQL database.

SECURITY NOTES
- Never commit or place config/secrets.php in browser-accessible source. It is excluded by .gitignore and the config directory is denied by .htaccess.
- Use HTTPS. Replace the requested initial admin password before making the site public.
- Feedback management is admin-only; submissions are program-scoped, queries are parameterized, forms use CSRF protection, and exported CSV cells are guarded against spreadsheet formulas.

HOMEPAGE / DATABASE TROUBLESHOOTING
- If the homepage is unavailable, open /admin and check the program manager's database diagnostic. Confirm MySQLi (mysqli or nd_mysqli) is enabled for this domain's PHP version.
- MySQL 1044/1045: verify the full cPanel database/user names, password, and the user's database assignment/privileges. MySQL 2002/2003/2005: verify the host/port with the hosting provider and check the MySQL service.
- MySQL 1146/1054: import the schema or the appropriate upgrade as described above.
- Public failures are logged with a support reference in the cPanel PHP error log and return HTTP 503 without exposing database details to visitors. A Git push alone does not configure the hosting database or run SQL imports.
