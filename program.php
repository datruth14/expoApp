<?php
declare(strict_types=1);
try {
    require __DIR__ . '/includes/bootstrap.php';

    $slug = trim((string) ($_GET['slug'] ?? ''));
    if ($slug === '') {
        $homeProgram = homepage_program();
        $slug = (string) ($homeProgram['slug'] ?? '');
    }
    if ($slug === '' || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
        http_response_code(404);
        exit('Program not found.');
    }

    $program = db()->query("SELECT id, name, slug, platform_title, tagline, description, welcome_message, banner_path, primary_color, accent_color, services_json FROM programs WHERE slug = ? AND status = 'published' LIMIT 1", [$slug])->fetch();

    if (!$program) {
        http_response_code(404);
        exit('Program not found.');
    }

    $program['services'] = json_decode($program['services_json'], true);
    if (!is_array($program['services'])) {
        $program['services'] = default_program_services();
    }
    $program['id'] = (int) $program['id'];
    $delegate = current_delegate();
    $requestedArea = is_string($_GET['area'] ?? null) ? $_GET['area'] : '';
    $validAreas = array_column($program['services'], 0);
    $validAreas[] = 'Overall event experience';
    if (!in_array($requestedArea, $validAreas, true)) {
        $requestedArea = '';
    }
    $feedbackCsrf = csrf_token();

    require __DIR__ . '/templates/program.php';
} catch (Throwable $exception) {
    $reference = bin2hex(random_bytes(6));
    error_log('Delegate Experience Centre public homepage [' . $reference . '] (' . $exception->getCode() . '): ' . $exception->getMessage());
    http_response_code(503);
    header('Cache-Control: no-store');
    header('Retry-After: 300');
    require __DIR__ . '/templates/unavailable.php';
}
