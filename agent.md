# Project instructions

## Project overview

Delegate Experience Centre is currently a standalone HTML/CSS/JavaScript prototype (`index.html`). The PHP/MySQLi starter in this package adds an authenticated admin area for creating and publishing reusable program pages. The prototype's feedback management still uses browser `localStorage`; do not represent it as centrally stored or protected by the PHP login.

## Stack and conventions

- Target PHP 8.1+ and MySQL/MariaDB on cPanel hosting.
- Use MySQLi with strict error reporting and UTF-8 (`utf8mb4`). Use prepared statements for all values in SQL queries.
- Keep database and admin credential configuration in server-side settings only. Never put secrets in HTML, JavaScript, or committed source files.
- Escape dynamic values in HTML with `htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`.
- Protect state-changing requests with POST and CSRF tokens. Keep admin pages behind the session authentication helpers in `includes/bootstrap.php`.
- Use `templates/program.php` as the shared presentation template for program pages; store each program's content/settings as a database row rather than duplicating page files.
- Use `database_schema_mysql.sql` for MySQL/MariaDB. `database_schema_postgresql.sql` is the original proposal and is not used by this PHP starter.

## Admin and deployment

- Admin username defaults to `admin`; the requested initial password is `admin12345`. Change it before making the site public by setting `ADMIN_PASSWORD_HASH` to a PHP `password_hash()` value in the hosting environment or `config/secrets.php`.
- Copy `config/secrets.example.php` to `config/secrets.php`, enter the cPanel database host/name/user/password, and import `database_schema_mysql.sql` through phpMyAdmin.
- Keep `config/secrets.php` private and out of version control. The `config/` directory is denied by `.htaccess` as defense in depth.
- Serve the site over HTTPS. Do not deploy until the real database credentials have been configured and the schema imported.

## Checks

- Run `php -l` on changed PHP files.
- If available, run the project against a disposable MySQL/MariaDB database and verify login, create/edit/publish, and public rendering of a program.
