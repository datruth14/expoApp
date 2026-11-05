DELEGATE EXPERIENCE CENTRE – PHP / MYSQLI STARTER

PROJECT CONTENTS
- index.html: the original standalone HTML/CSS/JavaScript prototype. Its feedback management still uses this browser's localStorage.
- admin/: admin login and program manager. Sign in and create, edit, draft, or publish program pages.
- templates/program.php: shared public page template used for every published program.
- program.php: renders a published program by its slug, for example program.php?slug=summer-conference.
- config/: server-side MySQLi settings and deployment example. Never publish database credentials in browser files.
- database_schema_mysql.sql: schema for MySQL/MariaDB; import it using cPanel phpMyAdmin.
- database_schema_postgresql.sql: original PostgreSQL proposal, retained for reference; it is not used by this starter.
- agent.md: project-specific instructions for future development.

CPANEL SETUP
1. Confirm your hosting account supports PHP 8.1+ and MySQLi.
2. In cPanel > MySQL Databases, create a database and database user, then assign that user to the database with the required privileges. cPanel commonly prefixes the database and username with your account name.
3. Open phpMyAdmin, select that database, and import database_schema_mysql.sql.
4. Copy config/secrets.example.php to config/secrets.php. Enter the database host, full database name, full database username, and database user's password supplied by cPanel. The host is often localhost; use the host value shown by your provider.
5. Upload the project to a PHP-enabled location and serve it over HTTPS. Open /admin/login.php.

INITIAL ADMIN LOGIN
- Username: admin
- Password: admin12345
- Change this before making the site public. Generate a password hash with:
  php -r 'echo password_hash("your-new-password", PASSWORD_DEFAULT), PHP_EOL;'
  Set that hash as ADMIN_PASSWORD_HASH in the hosting environment or as admin_password_hash in config/secrets.php.

PROGRAM TEMPLATE
After logging in, select “Create program”. Add the name, URL slug, description, welcome message and colors. Save as a draft or publish. Every published program uses templates/program.php and is available at program.php?slug=your-slug. The admin manager and public program pages use the MySQL database configured in config/secrets.php.

IMPORTANT CURRENT LIMITATION
The original index.html is still a front-end prototype. Its delegate feedback form and feedback-management view use localStorage and are not connected to the new MySQL database or admin login. Do not treat that feedback as centrally collected or protected. The PHP/MySQLi work currently provides the reusable program-page manager only.

SECURITY NOTES
- Keep config/secrets.php private and never put credentials in index.html or other browser-delivered files.
- Use HTTPS. The default admin password is for initial setup only; replace it before public deployment.
- The PHP starter uses prepared MySQLi statements, session-based admin authentication, CSRF tokens on state-changing forms, and HTML output escaping.
