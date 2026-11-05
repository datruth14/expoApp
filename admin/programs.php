<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_admin();

$result = db()->query('SELECT id, name, slug, status, updated_at FROM programs ORDER BY updated_at DESC');
$programs = $result->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Programs · Admin</title>
  <style>
    *{box-sizing:border-box}body{margin:0;background:#f2f7ff;color:#17345e;font:15px Arial,sans-serif}.wrap{max-width:1000px;margin:auto;padding:24px}.top{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}.card{margin-top:20px;padding:20px;background:#fff;border:1px solid #dce6f2;border-radius:16px;overflow:auto}a,button{color:#174889}.button{display:inline-block;padding:10px 14px;border:0;border-radius:9px;background:#174889;color:#fff;text-decoration:none;font-weight:bold;cursor:pointer}.logout{padding:8px 12px;background:#fff;border:1px solid #cbd9eb;border-radius:8px}table{width:100%;border-collapse:collapse;min-width:700px}th,td{text-align:left;padding:12px;border-bottom:1px solid #e5ebf3}th{color:#6b7e98}.status{text-transform:capitalize}
  </style>
</head>
<body>
  <main class="wrap">
    <div class="top">
      <div><h1>Program manager</h1><p>Create a new page from the shared program template.</p></div>
      <form method="post" action="logout.php"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><button class="logout" type="submit">Log out</button></form>
    </div>
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
              <td><?= h($program['name']) ?></td>
              <td><?= h($program['slug']) ?></td>
              <td class="status"><?= h($program['status']) ?></td>
              <td><?= h($program['updated_at']) ?></td>
              <td><a href="program-edit.php?id=<?= (int) $program['id'] ?>">Edit</a> · <a href="feedback.php?program_id=<?= (int) $program['id'] ?>">Feedback &amp; CSV</a><?php if ($program['status'] === 'published'): ?> · <a href="../program.php?slug=<?= rawurlencode($program['slug']) ?>" target="_blank" rel="noopener">View</a><?php endif; ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
