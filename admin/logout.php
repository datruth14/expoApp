<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}

verify_csrf();
// Admin and delegate sessions have independent sign-out actions.
unset($_SESSION['is_admin'], $_SESSION['login_attempts'], $_SESSION['login_locked_until']);
session_regenerate_id(true);
header('Location: login.php');
exit;
