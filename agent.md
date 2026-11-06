# Project instructions

## Project overview

Delegate Experience Centre uses PHP 8.1+, MySQL/MariaDB for cPanel or SQLite for offline use, and the shared public UI in `templates/program.php`. Programs are database records rendered through this template; the initial program is seeded as “Loveword Expo 2026”. Public feedback belongs to one program.

## Stack and conventions

- Use the prepared-query interface in `includes/database.php` for application SQL. MySQL uses MySQLi with strict errors and `utf8mb4`; SQLite uses PDO. Keep application queries compatible with both drivers.
- Keep database and admin credential configuration server-side. Never place secrets in HTML, JavaScript, or committed source files.
- Escape dynamic HTML with `htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`.
- Protect state-changing requests with POST and CSRF tokens. Keep admin routes behind `require_admin()` in `includes/bootstrap.php`.
- Validate feedback against that program's service areas and always scope admin queries/exports by `program_id`.
- Banner uploads are limited to validated JPG, PNG, and WebP images; keep uploads under `uploads/banners/` and preserve its no-script `.htaccess` protection.
- Use `database_schema_mysql.sql` for new databases. Use `database_upgrade_v2.sql` only once for an installation created from the earlier starter schema. The PostgreSQL SQL file is reference-only.
- Keep deployed PHP/assets and uploaded banners on the cPanel account filesystem (`public_html` and `uploads/banners/`). Keep program and feedback records in the cPanel MySQL database. Do not add browser-side persistence.
- Offline mode is explicit (`DB_DRIVER=sqlite` or `db_driver` in ignored `config/secrets.php`). Never fall back from MySQL to SQLite on a connection failure. SQLite auto-initializes `storage/experience.db`; keep runtime database files ignored and private.

## Admin and deployment

- Admin username defaults to `admin`; the initial password is `admin12345`. Change it before public deployment using `ADMIN_PASSWORD_HASH` or `config/secrets.php`.
- Keep the admin sign-in reachable at `/admin` without exposing a login link on the public program page.
- The public root (`index.php`) must remain accessible to signed-in admins. Admin authentication redirects belong only on admin routes; keep a public homepage link in the admin manager.
- Homepage selection is stored in `platform_settings` and changed by a CSRF-protected admin POST. Only published programs can be displayed; `homepage_program()` falls back to the oldest published program if needed. Existing MySQL installations need `database_upgrade_v3.sql`; SQLite upgrades automatically to schema version 2.
- Configure cPanel MySQL credentials in ignored `config/secrets.php`, import the appropriate schema, and ensure `uploads/banners` is writable by PHP.
- Keep HTTPS enabled. Never commit `config/secrets.php` or real credentials.
- Delegate browsing is public; submitting feedback requires `current_delegate()`. With complete KingsChat credentials, require server-side provider verification. While credentials are incomplete, accept explicitly unverified username-and-phone access. Admin login is not delegate login. Preserve independent logout behavior.
- KingsChat uses authorization-code exchange and the profile API, with credentials in `config/secrets.php`. Require a session-bound, one-time `origin` nonce, REDIRECT callback delivery, and TLS. Never trust browser-supplied handles/IDs or persist provider tokens. See `docs/kingschat.md`.
- New installations use the full schemas; existing MySQL installations need the missing v4/v5 upgrades for delegates/manual access. SQLite auto-upgrades to schema version 4 and preserves existing IDs and feedback links when rebuilding the old delegates table.
- Manual records have no provider ID, an explicit `auth_method`, and normalized username/phone data. Never merge them with verified accounts based on a matching handle. Omit phones from standard feedback exports and label manual feedback unverified.
- Both Client ID and API key activate KingsChat automatically. Reject old manual sessions and stale manual form submissions in configured mode; never downgrade after a provider failure.
- Mask both name and KingsChat handle for anonymous/report-hidden feedback in HTML and CSV while preserving its internal `delegate_id` link.
- Program deletion is admin-only and requires a CSRF-protected POST after the confirmation page. Use the existing FK cascade for feedback and SET NULL for homepage selection; keep delegates and other programs intact. Only remove managed banner paths that are not referenced by another program. No published programs should render the public empty-state template.
- Start offline with `DB_DRIVER=sqlite php -S 127.0.0.1:8000 local-router.php`. The router protects private files because PHP's built-in server ignores `.htaccess`.

## Checks

- Run `php -l` on changed PHP files.
- Run `python3 tests/offline_smoke.py` for meaningful HTTP integration tests against a disposable SQLite file.
- If available, verify login, program creation/editing, banner upload, public feedback submission, program-scoped admin review, and CSV export against a disposable MySQL/MariaDB database.
