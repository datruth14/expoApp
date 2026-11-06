<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');

// Use REDIRECT callback delivery in the KingsChat dashboard. Cross-site FORM_POST
// callbacks do not carry the session's SameSite=Lax cookie and are not accepted.
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    http_response_code(405);
    exit('Configure KingsChat callback delivery as REDIRECT.');
}

$pending = $_SESSION['kingschat_pending'] ?? null;
$origin = $_GET['origin'] ?? '';
$code = $_GET['code'] ?? '';
$backUrl = 'index.php';
if (!is_array($pending) || !is_string($origin) || !is_string($code)
    || $origin === '' || !hash_equals($pending['state'], $origin)
    || (int) $pending['created_at'] < time() - 600 || $code === '' || strlen($code) > 8192) {
    http_response_code(400);
    $loginError = 'This sign-in request is invalid or expired. Return to the program and try again.';
    require __DIR__ . '/templates/delegate-login-error.php';
    exit;
}
unset($_SESSION['kingschat_pending']); // One-time state; failed exchanges must start again.
$backUrl = feedback_return_url($pending['slug'], $pending['area']);
try {
    if (!kingschat_configured()) {
        throw new RuntimeException('KingsChat Client ID or API key is not configured.');
    }
    $profile = verify_kingschat_code($code);
    $delegateId = save_delegate_profile($profile);
    session_regenerate_id(true);
    $_SESSION['delegate_id'] = $delegateId;
    $_SESSION['delegate_expires_at'] = $profile['expires_at'];
    // Provider tokens are used only for verification, never saved or sent to the browser.
    header('Location: ' . $backUrl, true, 303);
    exit;
} catch (Throwable $exception) {
    $reference = bin2hex(random_bytes(6));
    // Database exceptions can contain profile data. Log only the class/code.
    $detail = $exception instanceof PDOException || $exception instanceof mysqli_sql_exception
        ? get_class($exception) . ' code ' . $exception->getCode()
        : $exception->getMessage();
    error_log('Delegate Experience Centre KingsChat sign-in [' . $reference . ']: ' . $detail);
    http_response_code(502);
    $loginError = 'We could not complete KingsChat sign-in. Please try again. Support reference: ' . $reference;
    require __DIR__ . '/templates/delegate-login-error.php';
}
