<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_admin();

$programs = [];
$databaseError = '';
$selectionError = '';
$homeProgram = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
}
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $programId = filter_var($_POST['home_program_id'] ?? null, FILTER_VALIDATE_INT);
        $candidate = $programId ? db()->query("SELECT id FROM programs WHERE id = ? AND status = 'published'", [$programId])->fetch() : false;
        if (!$candidate) {
            http_response_code(422);
            $selectionError = 'Choose a published program for the homepage. Publish drafts before selecting them.';
        } else {
            db()->query('UPDATE platform_settings SET home_program_id = ? WHERE id = 1', [$programId]);
            $_SESSION['program_notice'] = 'The homepage program has been updated.';
            header('Location: programs.php');
            exit;
        }
    }
    $homeProgram = homepage_program();
    $result = db()->query('SELECT id, name, slug, status, updated_at FROM programs ORDER BY updated_at DESC');
    $programs = $result->fetchAll();
} catch (Throwable $exception) {
    error_log('Delegate Experience Centre admin/programs.php: ' . $exception->getMessage());

    if (str_contains($exception->getMessage(), 'Database is not configured')) {
        $databaseError = 'The cPanel database settings are missing. Create config/secrets.php from config/secrets.example.php and enter the database host, full database name, full database username, and password.';
    } elseif (str_contains($exception->getMessage(), 'MySQLi PHP extension is not enabled')) {
        $databaseError = $exception->getMessage();
    } elseif (class_exists('mysqli_sql_exception') && $exception instanceof mysqli_sql_exception) {
        $databaseError = match ((int) $exception->getCode()) {
            1044 => 'The database user does not have access to this database. In cPanel > MySQL Databases, add the configured user to the configured database and assign the required privileges.',
            1045 => 'MySQL rejected the database login. Check the database username/password in config/secrets.php and confirm that the user is assigned to this database in cPanel.',
            1049 => 'The configured MySQL database was not found. Check the full cPanel database name in config/secrets.php.',
            1146 => 'A database table is missing. For an existing installation, import database_upgrade_v3.sql to enable homepage selection (run the v2 upgrade first if still needed). For a new database, import database_schema_mysql.sql.',
            1054 => 'The database schema is out of date. Back up the database and run database_upgrade_v2.sql in phpMyAdmin.',
            2002, 2003 => 'The MySQL server connection failed. Verify the database host and port with your cPanel provider and confirm its MySQL service is running.',
            2005 => 'The database host could not be resolved. Use the MySQL hostname supplied by your cPanel provider in config/secrets.php.',
            default => 'The cPanel MySQL database could not be reached. Check config/secrets.php, the database host, and the database user privileges. The detailed error is in the cPanel PHP error log.',
        };
        $databaseError .= ' MySQL error code: ' . (int) $exception->getCode() . '.';
    } else {
        $databaseError = 'The program list could not be loaded. Confirm cPanel is using PHP 8.1+ with MySQLi enabled, and check the cPanel PHP error log for details.';
    }
}
$notice = (string) ($_SESSION['program_notice'] ?? '');
unset($_SESSION['program_notice']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Programs · Admin</title>
  <style>
    *{box-sizing:border-box}body{margin:0;background:#f2f7ff;color:#17345e;font:15px Arial,sans-serif}.wrap{max-width:1000px;margin:auto;padding:24px}.top{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}.card{margin-top:20px;padding:20px;background:#fff;border:1px solid #dce6f2;border-radius:16px;overflow:auto}.error-card{padding:18px;background:#fff7e6;border:1px solid #e9bf63;border-radius:12px;color:#5b421a;line-height:1.6}a,button{color:#174889}.button{display:inline-block;padding:10px 14px;border:0;border-radius:9px;background:#174889;color:#fff;text-decoration:none;font-weight:bold;cursor:pointer}.logout{padding:8px 12px;background:#fff;border:1px solid #cbd9eb;border-radius:8px}table{width:100%;border-collapse:collapse;min-width:700px}th,td{text-align:left;padding:12px;border-bottom:1px solid #e5ebf3}th{color:#6b7e98}.status{text-transform:capitalize}
  </style>
</head>
<body>
  <main class="wrap">
    <div class="top">
      <div><h1>Program manager</h1><p>Create a new page from the shared program template.</p></div>
      <div>
        <p><a href="../index.php">View homepage</a></p>
        <form method="post" action="logout.php"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><button class="logout" type="submit">Log out</button></form>
      </div>
    </div>
    <?php if ($databaseError !== ''): ?>
      <section class="error-card" role="alert">
        <h2>Database setup needs attention</h2>
        <p><?= h($databaseError) ?></p>
        <p>After correcting the setup, reload this page. No program records were changed.</p>
      </section>
    <?php else: ?>
    <?php if ($notice !== ''): ?><p role="status"><?= h($notice) ?></p><?php endif; ?>
    <?php if ($selectionError !== ''): ?><p class="error-card" role="alert"><?= h($selectionError) ?></p><?php endif; ?>
    <p><strong>Current homepage:</strong> <?= $homeProgram ? h($homeProgram['name']) : 'No published program' ?>. Choose a published program below to change what visitors see at the site address.</p>
    <a class="button" href="program-edit.php">Create program</a>
    <section class="card">
      <?php if ($programs === []): ?>
        <p>No programs yet. Create one to start using the template.</p>
      <?php else: ?>
        <table>
          <thead><tr><th>Program</th><th>Page URL</th><th>Status</th><th>Last updated</th><th>Manage</th></tr></thead>
          <tbody>
          <?php foreach ($programs as $program): ?>
            <tr>
              <td><?= h($program['name']) ?><?php if ($homeProgram && (int) $program['id'] === (int) $homeProgram['id']): ?><br><strong>Homepage</strong><?php endif; ?></td>
              <td><?= h($program['slug']) ?></td>
              <td class="status"><?= h($program['status']) ?></td>
              <td><?= h($program['updated_at']) ?></td>
              <td><a href="program-edit.php?id=<?= (int) $program['id'] ?>">Edit</a> · <a href="feedback.php?program_id=<?= (int) $program['id'] ?>">Feedback &amp; CSV</a><?php if ($program['status'] === 'published'): ?> · <a href="../program.php?slug=<?= rawurlencode($program['slug']) ?>" target="_blank" rel="noopener">View</a>
                <?php if (!$homeProgram || (int) $program['id'] !== (int) $homeProgram['id']): ?>
                <form method="post" action="programs.php" style="margin-top:10px">
                  <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                  <input type="hidden" name="home_program_id" value="<?= (int) $program['id'] ?>">
                  <button class="button" type="submit">Set as homepage</button>
                </form>
                <?php endif; ?>
              <?php endif; ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </section>
    <?php endif; ?>
  </main>
</body>
</html>
