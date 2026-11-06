<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    http_response_code(405);
    exit('Method not allowed.');
}

$input = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
}
$slug = is_string($input['program'] ?? null) ? $input['program'] : '';
$area = is_string($input['area'] ?? null) ? $input['area'] : '';
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
    $manualError = '';
    $handle = '';
    $phone = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $handle = is_string($_POST['username'] ?? null) ? strtolower(ltrim(trim($_POST['username']), '@')) : '';
        $phone = is_string($_POST['phone'] ?? null) ? trim($_POST['phone']) : '';
        $normalizedPhone = preg_replace('/[\s()\-]/', '', $phone);
        if (!preg_match('/^[a-z0-9][a-z0-9._-]{1,62}[a-z0-9_-]$/', $handle)) {
            http_response_code(422);
            $manualError = 'Enter a username of 3–64 characters using letters, numbers, dots, underscores, or hyphens.';
        } elseif (!preg_match('/^\+[1-9][0-9]{6,14}$/', $normalizedPhone)) {
            http_response_code(422);
            $manualError = 'Enter a valid phone number with country code, for example +2348012345678.';
        } else {
            try {
                $delegateId = save_manual_delegate($handle, $normalizedPhone);
                session_regenerate_id(true);
                $_SESSION['delegate_id'] = $delegateId;
                $_SESSION['delegate_expires_at'] = time() + 8 * 60 * 60;
                header('Location: ' . $backUrl, true, 303);
                exit;
            } catch (Throwable $exception) {
                $reference = bin2hex(random_bytes(6));
                error_log('Delegate Experience Centre manual access [' . $reference . ']: ' . get_class($exception) . ' code ' . $exception->getCode());
                http_response_code(503);
                $manualError = 'We could not save your details. Please try again. Support reference: ' . $reference;
            }
        }
    }
    require __DIR__ . '/templates/delegate-manual-login.php';
    exit;
}

// A fallback form opened before activation cannot create another manual session.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Location: ' . delegate_login_url($slug, $area), true, 303);
    exit;
}

$state = bin2hex(random_bytes(32));
$_SESSION['kingschat_pending'] = ['state' => $state, 'created_at' => time(), 'slug' => $slug, 'area' => $area];
$loginUrl = 'https://accounts.kingschat.online/log-in?' . http_build_query([
    'clientId' => $settings['kingschat_client_id'], 'origin' => $state,
], '', '&', PHP_QUERY_RFC3986);
header('Location: ' . $loginUrl, true, 302);
exit;
