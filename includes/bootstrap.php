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

function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function db(): mysqli
{
    static $connection;
    global $settings;

    if (!$connection instanceof mysqli) {
        if ($settings['db_name'] === '' || $settings['db_user'] === '') {
            throw new RuntimeException('Database is not configured. Set the cPanel MySQL connection details in config/secrets.php.');
        }

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $connection = new mysqli(
            $settings['db_host'],
            $settings['db_user'],
            $settings['db_password'],
            $settings['db_name'],
            (int) $settings['db_port']
        );
        $connection->set_charset('utf8mb4');
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
