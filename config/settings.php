<?php
declare(strict_types=1);

$localSettings = is_file(__DIR__ . '/secrets.php')
    ? require __DIR__ . '/secrets.php'
    : [];

if (!is_array($localSettings)) {
    throw new RuntimeException('config/secrets.php must return an array.');
}

return array_replace([
    'db_host' => getenv('MYSQL_HOST') ?: 'localhost',
    'db_port' => (int) (getenv('MYSQL_PORT') ?: 3306),
    'db_name' => getenv('MYSQL_DATABASE') ?: '',
    'db_user' => getenv('MYSQL_USER') ?: '',
    'db_password' => getenv('MYSQL_PASSWORD') ?: '',
    'admin_username' => getenv('ADMIN_USERNAME') ?: 'admin',
    // Initial password: admin12345. Set ADMIN_PASSWORD_HASH before public deployment.
    'admin_password_hash' => getenv('ADMIN_PASSWORD_HASH') ?: '$2y$10$s0qOCq.GgdSWiTAU4RzBm.ccWbs/poa08EHJOnC7zwRCQF3RQwz2W',
], $localSettings);
