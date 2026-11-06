<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method not allowed.');
}
verify_csrf();
unset($_SESSION['delegate_id'], $_SESSION['delegate_expires_at'], $_SESSION['kingschat_pending']);
session_regenerate_id(true);
header('Location: index.php', true, 303);
exit;
