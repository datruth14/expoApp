<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $isHttps,
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

$settings = require __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/database.php';

function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function db(): AppDatabase
{
    static $connection;
    global $settings;

    if (!$connection instanceof AppDatabase) {
        if ($settings['db_driver'] === 'sqlite') {
            $connection = open_sqlite_database($settings['sqlite_path']);
            return $connection;
        }
        if ($settings['db_driver'] !== 'mysql') {
            throw new RuntimeException('Unsupported db_driver. Choose mysql or sqlite.');
        }
        if ($settings['db_name'] === '' || $settings['db_user'] === '') {
            throw new RuntimeException('Database is not configured. Set the cPanel MySQL connection details in config/secrets.php.');
        }

        if (!extension_loaded('mysqli')) {
            throw new RuntimeException('The MySQLi PHP extension is not enabled. Enable mysqli or nd_mysqli for this domain in cPanel.');
        }

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $mysqli = new mysqli(
            $settings['db_host'],
            $settings['db_user'],
            $settings['db_password'],
            $settings['db_name'],
            (int) $settings['db_port']
        );
        $mysqli->set_charset('utf8mb4');
        $connection = new AppDatabase($mysqli);
    }

    return $connection;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    if (!is_string($submitted) || !hash_equals(csrf_token(), $submitted)) {
        http_response_code(400);
        exit('Invalid request token. Please reload the page and try again.');
    }
}

function require_admin(): void
{
    if (empty($_SESSION['is_admin'])) {
        header('Location: login.php');
        exit;
    }
}

function default_program_services(): array
{
    return [
        ['Exhibition Experience', 'Exhibition stands and displays', '🎪'],
        ['Food Court Experience', 'Food vendors and dining facilities', '🍽️'],
        ['Accreditation Experience', 'Registration and badge collection', '🪪'],
        ['Access Control Experience', 'Entry points and access routes', '🔐'],
        ['Hospitality Experience', 'Guest reception and assistance', '🛎️'],
        ['Transportation Experience', 'Shuttle and transport services', '🚌'],
        ['Car Parking Experience', 'Parking locations and vehicle access', '🚘'],
        ['Venue Experience', 'Facilities, environment and overall venue experience', '🏟️'],
        ['Lost & Found', 'Help with misplaced belongings', '🧳'],
    ];
}
