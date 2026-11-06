<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    respond(405, ['ok' => false, 'message' => 'Method not allowed.']);
}

$submittedToken = (string) ($_POST['csrf_token'] ?? '');
if ($submittedToken === '' || !hash_equals(csrf_token(), $submittedToken)) {
    respond(400, ['ok' => false, 'message' => 'Please reload the page and try again.']);
}

$delegate = current_delegate();
if (!$delegate) {
    respond(401, ['ok' => false, 'message' => kingschat_configured()
        ? 'Please sign in with KingsChat before submitting feedback.'
        : 'Please enter your username and phone number before submitting feedback.']);
}

$slug = trim((string) ($_POST['program_slug'] ?? ''));
$area = trim((string) ($_POST['area'] ?? ''));
$rating = filter_var($_POST['rating'] ?? null, FILTER_VALIDATE_INT);
$anonymous = isset($_POST['anonymous']);
$positive = trim((string) ($_POST['positive'] ?? ''));
$comments = trim((string) ($_POST['comments'] ?? ''));

if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)
    || $area === '' || mb_strlen($area) > 180
    || $rating === false || $rating < 1 || $rating > 5
    || mb_strlen($positive) > 10000 || mb_strlen($comments) > 10000) {
    respond(422, ['ok' => false, 'message' => 'Please check the required fields and try again.']);
}

$connection = db();
$program = $connection->query("SELECT id, services_json FROM programs WHERE slug = ? AND status = 'published' LIMIT 1", [$slug])->fetch();
if (!$program) {
    respond(404, ['ok' => false, 'message' => 'This program is not available.']);
}

$services = json_decode($program['services_json'], true);
$validAreas = array_map(static fn (array $service): string => (string) ($service[0] ?? ''), is_array($services) ? $services : []);
$validAreas[] = 'Overall event experience';
if (!in_array($area, $validAreas, true)) {
    respond(422, ['ok' => false, 'message' => 'Please choose a valid service area.']);
}

$delegateName = $anonymous ? null : ($delegate['display_name'] ?: $delegate['handle']);
$isAnonymous = $anonymous ? 1 : 0;
$programId = (int) $program['id'];
$connection->query('INSERT INTO feedback (program_id, delegate_id, service_area, rating, delegate_name, is_anonymous, went_well, comments) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', [$programId, (int) $delegate['id'], $area, $rating, $delegateName, $isAnonymous, $positive, $comments]);

respond(201, ['ok' => true, 'message' => 'Thank you. Your feedback has been recorded.']);
