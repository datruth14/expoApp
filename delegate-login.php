<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    http_response_code(405);
    exit('Method not allowed.');
}

$slug = is_string($_GET['program'] ?? null) ? $_GET['program'] : '';
$area = is_string($_GET['area'] ?? null) ? $_GET['area'] : '';
$program = db()->query("SELECT slug, services_json FROM programs WHERE slug = ? AND status = 'published'", [$slug])->fetch();
if (!$program) {
    http_response_code(404);
    exit('Program not found.');
}
$areas = array_column(json_decode($program['services_json'], true) ?: default_program_services(), 0);
$areas[] = 'Overall event experience';
if (!in_array($area, $areas, true)) {
    http_response_code(422);
    exit('Please choose a valid service area.');
}
$backUrl = feedback_return_url($slug, $area);
if (current_delegate()) {
    header('Location: ' . $backUrl, true, 303);
    exit;
}
if (!kingschat_configured()) {
    http_response_code(503);
    $loginError = 'KingsChat sign-in is not available yet. Please contact the event team.';
    require __DIR__ . '/templates/delegate-login-error.php';
    exit;
}

$state = bin2hex(random_bytes(32));
$_SESSION['kingschat_pending'] = ['state' => $state, 'created_at' => time(), 'slug' => $slug, 'area' => $area];
$loginUrl = 'https://accounts.kingschat.online/log-in?' . http_build_query([
    'clientId' => $settings['kingschat_client_id'], 'origin' => $state,
], '', '&', PHP_QUERY_RFC3986);
header('Location: ' . $loginUrl, true, 302);
exit;
