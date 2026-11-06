<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_admin();
header('Cache-Control: no-store');

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    http_response_code(405);
    exit('Method not allowed.');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
}
$input = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST['program_id'] ?? null : $_GET['id'] ?? null;
$id = filter_var($input, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) {
    http_response_code(422);
    exit('Choose a valid program.');
}

$connection = db();
$program = $connection->query('SELECT id, name, banner_path FROM programs WHERE id = ?', [$id])->fetch();
if (!$program) {
    http_response_code(404);
    exit('Program not found.');
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // One atomic DELETE: FK constraints remove feedback and clear the saved homepage.
        $connection->query('DELETE FROM programs WHERE id = ?', [$id]);
    } catch (Throwable $exception) {
        error_log('Delegate Experience Centre program delete: ' . $exception->getMessage());
        http_response_code(503);
        $error = 'Unable to delete the program. Check the database user’s DELETE privileges and the PHP error log, then try again.';
    }
    if ($error === '') {
        $notice = 'Program deleted along with its feedback.';
        $banner = $program['banner_path'];
        // Only remove app-managed files, and never remove a banner used by another program.
        if (is_string($banner) && preg_match('~^uploads/banners/[a-f0-9]{32}\.(jpg|png|webp)$~', $banner)) {
            try {
                $shared = $connection->query('SELECT id FROM programs WHERE banner_path = ? LIMIT 1', [$banner])->fetch();
                $file = __DIR__ . '/../' . $banner;
                if (!$shared && is_file($file) && !unlink($file)) {
                    throw new RuntimeException('Banner file could not be removed.');
                }
            } catch (Throwable $exception) {
                error_log('Delegate Experience Centre deleted-program banner cleanup: ' . $exception->getMessage());
                $notice .= ' Its old banner could not be cleaned up; check the server error log.';
            }
        }
        $_SESSION['program_notice'] = $notice;
        header('Location: programs.php', true, 303);
        exit;
    }
}
$summary = $connection->query('SELECT COUNT(*) AS total FROM feedback WHERE program_id = ?', [$id])->fetch();
$homeProgram = homepage_program();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Delete program · Admin</title>
  <link rel="stylesheet" href="../assets/loading.css">
  <script defer src="../assets/loading.js"></script>
  <style>
    *{box-sizing:border-box}body{margin:0;padding:24px;background:#f2f7ff;color:#17345e;font:16px/1.6 Arial,sans-serif}main{max-width:650px;margin:6vh auto;padding:28px;border:1px solid #dce6f2;border-radius:18px;background:#fff}h1{line-height:1.2}.error{padding:12px;background:#fff0ed;color:#8b2415;border-radius:8px}.actions{display:flex;align-items:center;gap:18px;flex-wrap:wrap;margin-top:24px}button{padding:12px 16px;border:0;border-radius:9px;background:#a32c28;color:white;font:inherit;cursor:pointer}a{color:#174889}
  </style>
</head>
<body>
  <main>
    <h1>Delete <?= h($program['name']) ?>?</h1>
    <?php if ($error !== ''): ?><p class="error" role="alert"><?= h($error) ?></p><?php endif; ?>
    <p>This permanently deletes the program and its <strong><?= (int) $summary['total'] ?> feedback record(s)</strong>. Export the feedback first if you need to keep it.</p>
    <?php if ($homeProgram && (int) $homeProgram['id'] === $id): ?><p>This is the current homepage. The site will show the next published program, or a “No programs currently published” message if none remain.</p><?php endif; ?>
    <p><a href="export-feedback.php?program_id=<?= (int) $id ?>" data-download>Download feedback CSV</a></p>
    <form method="post" action="program-delete.php">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="program_id" value="<?= (int) $id ?>">
      <div class="actions"><button type="submit">Delete program and feedback</button><a href="programs.php">Cancel</a></div>
    </form>
  </main>
</body>
</html>
