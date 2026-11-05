<?php
// Copy this file to secrets.php and replace the database placeholders.
// To change the admin password, generate a hash with:
// php -r 'echo password_hash("your-new-password", PASSWORD_DEFAULT), PHP_EOL;'
return [
    'db_host' => 'localhost',
    'db_port' => 3306,
    'db_name' => 'cpanel_account_database',
    'db_user' => 'cpanel_account_user',
    'db_password' => 'replace-with-your-database-password',
    'admin_username' => 'admin',
    // This hash corresponds to the requested initial password: admin12345.
    'admin_password_hash' => '$2y$10$s0qOCq.GgdSWiTAU4RzBm.ccWbs/poa08EHJOnC7zwRCQF3RQwz2W',
];
