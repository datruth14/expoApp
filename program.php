<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
if ($slug === '' || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
    http_response_code(404);
    exit('Program not found.');
}

$statement = db()->prepare("SELECT name, slug, description, welcome_message, primary_color, accent_color FROM programs WHERE slug = ? AND status = 'published' LIMIT 1");
$statement->bind_param('s', $slug);
$statement->execute();
$program = $statement->get_result()->fetch_assoc();

if (!$program) {
    http_response_code(404);
    exit('Program not found.');
}

require __DIR__ . '/templates/program.php';
