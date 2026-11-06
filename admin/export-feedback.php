<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_admin();

$programId = filter_input(INPUT_GET, 'program_id', FILTER_VALIDATE_INT) ?: 0;
$connection = db();
$program = $connection->query('SELECT name, slug FROM programs WHERE id = ?', [$programId])->fetch();
if (!$program) {
    http_response_code(404);
    exit('Program not found.');
}

$rows = $connection->query('SELECT f.submitted_at, f.service_area, f.rating, f.delegate_name, f.is_anonymous, f.went_well, f.comments, d.handle, d.auth_method FROM feedback f LEFT JOIN delegates d ON d.id = f.delegate_id WHERE f.program_id = ? ORDER BY f.submitted_at DESC', [$programId]);

$filename = preg_replace('/[^a-z0-9-]+/i', '-', $program['slug']) . '-feedback.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store, no-cache, must-revalidate');
$output = fopen('php://output', 'wb');
fwrite($output, "\xEF\xBB\xBF");

$safeCell = static function (mixed $value): string {
    $value = (string) ($value ?? '');
    if (preg_match('/^[\x00-\x20]*[=+@-]/', $value)) {
        return "'" . $value;
    }
    return $value;
};

fputcsv($output, ['Submitted at', 'Program', 'Delegate', 'Service area', 'Rating', 'What went well', 'Comments', 'KingsChat handle / supplied username', 'Access method'], ',', '"', '\\');
while ($row = $rows->fetch()) {
    fputcsv($output, [
        $safeCell($row['submitted_at']),
        $safeCell($program['name']),
        $safeCell(!empty($row['is_anonymous']) ? 'Anonymous' : ($row['delegate_name'] ?: 'Not provided')),
        $safeCell($row['service_area']),
        (int) $row['rating'],
        $safeCell($row['went_well']),
        $safeCell($row['comments']),
        $safeCell(!empty($row['is_anonymous']) ? '' : ($row['handle'] ?? '')),
        delegate_auth_label($row['auth_method']),
    ], ',', '"', '\\');
}
fclose($output);
