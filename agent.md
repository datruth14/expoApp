# Project instructions

## Project overview

Delegate Experience Centre uses PHP 8.1+, MySQL/MariaDB, and the shared public UI in `templates/program.php`. Programs are database records rendered through this template; the initial program is seeded as “Loveword Expo 2026”. Public feedback is stored in MySQL and belongs to one program.

## Stack and conventions

- Use MySQLi with strict error reporting and UTF-8 (`utf8mb4`). Use prepared statements for SQL values.
- Keep database and admin credential configuration server-side. Never place secrets in HTML, JavaScript, or committed source files.
- Escape dynamic HTML with `htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`.
- Protect state-changing requests with POST and CSRF tokens. Keep admin routes behind `require_admin()` in `includes/bootstrap.php`.
- Validate feedback against that program's service areas and always scope admin queries/exports by `program_id`.
- Banner uploads are limited to validated JPG, PNG, and WebP images; keep uploads under `uploads/banners/` and preserve its no-script `.htaccess` protection.
- Use `database_schema_mysql.sql` for new databases. Use `database_upgrade_v2.sql` only once for an installation created from the earlier starter schema. The PostgreSQL SQL file is reference-only.
- Keep deployed PHP/assets and uploaded banners on the cPanel account filesystem (`public_html` and `uploads/banners/`). Keep program and feedback records in the cPanel MySQL database. Do not add browser-side persistence.

## Admin and deployment

- Admin username defaults to `admin`; the initial password is `admin12345`. Change it before public deployment using `ADMIN_PASSWORD_HASH` or `config/secrets.php`.
- Configure cPanel MySQL credentials in ignored `config/secrets.php`, import the appropriate schema, and ensure `uploads/banners` is writable by PHP.
- Keep HTTPS enabled. Never commit `config/secrets.php` or real credentials.

## Checks

- Run `php -l` on changed PHP files.
- If available, verify login, program creation/editing, banner upload, public feedback submission, program-scoped admin review, and CSV export against a disposable MySQL/MariaDB database.
