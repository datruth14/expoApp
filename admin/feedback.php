<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_admin();

$connection = db();
$programId = filter_input(INPUT_GET, 'program_id', FILTER_VALIDATE_INT) ?: 0;
$programStatement = $connection->prepare('SELECT id, name, slug FROM programs WHERE id = ?');
$programStatement->bind_param('i', $programId);
$programStatement->execute();
$program = $programStatement->get_result()->fetch_assoc();
if (!$program) {
    http_response_code(404);
    exit('Program not found.');
}

$area = trim((string) ($_GET['area'] ?? ''));
$filter = $connection->prepare('SELECT DISTINCT service_area FROM feedback WHERE program_id = ? ORDER BY service_area');
$filter->bind_param('i', $programId);
$filter->execute();
$areas = $filter->get_result()->fetch_all(MYSQLI_ASSOC);

$summary = $connection->prepare('SELECT COUNT(*) AS total, AVG(rating) AS average_rating, SUM(rating = 5) AS five_star FROM feedback WHERE program_id = ?');
$summary->bind_param('i', $programId);
$summary->execute();
$stats = $summary->get_result()->fetch_assoc();

if ($area !== '') {
    $recordsQuery = $connection->prepare('SELECT submitted_at, service_area, rating, delegate_name, is_anonymous, went_well, comments FROM feedback WHERE program_id = ? AND service_area = ? ORDER BY submitted_at DESC');
    $recordsQuery->bind_param('is', $programId, $area);
} else {
    $recordsQuery = $connection->prepare('SELECT submitted_at, service_area, rating, delegate_name, is_anonymous, went_well, comments FROM feedback WHERE program_id = ? ORDER BY submitted_at DESC');
    $recordsQuery->bind_param('i', $programId);
}
$recordsQuery->execute();
$records = $recordsQuery->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Feedback · <?= h($program['name']) ?></title>
  <style>
    *{box-sizing:border-box}body{margin:0;background:#f2f7ff;color:#17345e;font:15px Arial,sans-serif}.wrap{max-width:1120px;margin:auto;padding:24px}.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin:20px 0}.stat,.card{padding:18px;background:white;border:1px solid #dce6f2;border-radius:15px}.stat b{display:block;font-size:24px;color:#1753a0}.toolbar{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:16px 0}.button{display:inline-block;padding:10px 14px;border:0;border-radius:9px;background:#174889;color:#fff;text-decoration:none;font-weight:bold}.select{padding:10px;border:1px solid #cbd9eb;border-radius:8px;background:white}table{width:100%;border-collapse:collapse;min-width:850px}th,td{text-align:left;padding:10px;border-bottom:1px solid #e5ebf3;vertical-align:top}th{color:#6b7e98}.tablewrap{overflow-x:auto}.comment{max-width:260px;white-space:pre-wrap;overflow-wrap:anywhere;font-size:13px}.muted{color:#627895;font-size:13px}@media(max-width:600px){.stats{grid-template-columns:1fr}}
  </style>
</head>
<body>
  <main class="wrap">
    <p><a href="programs.php">← Back to programs</a></p>
    <h1>Feedback · <?= h($program['name']) ?></h1>
    <p class="muted">Feedback is stored against this program and kept separate from other programs.</p>
    <section class="stats">
      <div class="stat"><b><?= (int) $stats['total'] ?></b><span>TOTAL RESPONSES</span></div>
      <div class="stat"><b><?= $stats['average_rating'] !== null ? number_format((float) $stats['average_rating'], 1) . '/5' : '—' ?></b><span>AVERAGE RATING</span></div>
      <div class="stat"><b><?= (int) ($stats['five_star'] ?? 0) ?></b><span>5-STAR RATINGS</span></div>
    </section>
    <div class="toolbar">
      <form method="get" action="feedback.php">
        <input type="hidden" name="program_id" value="<?= (int) $programId ?>">
        <label for="area">Service area</label>
        <select class="select" id="area" name="area" onchange="this.form.submit()">
          <option value="">All service areas</option>
          <?php foreach ($areas as $item): ?><option value="<?= h($item['service_area']) ?>"<?= $area === $item['service_area'] ? ' selected' : '' ?>><?= h($item['service_area']) ?></option><?php endforeach; ?>
        </select>
      </form>
      <a class="button" href="export-feedback.php?program_id=<?= (int) $programId ?>">Download CSV</a>
    </div>
    <section class="card tablewrap">
      <?php if ($records === []): ?><p>No feedback has been submitted<?= $area !== '' ? ' for this service area' : '' ?> yet.</p><?php else: ?>
        <table>
          <thead><tr><th>Date</th><th>Service area</th><th>Rating</th><th>Delegate</th><th>What went well?</th><th>Comments</th></tr></thead>
          <tbody><?php foreach ($records as $record): ?>
            <tr>
              <td><?= h($record['submitted_at']) ?></td>
              <td><?= h($record['service_area']) ?></td>
              <td><?= str_repeat('★', (int) $record['rating']) ?></td>
              <td><?= !empty($record['is_anonymous']) ? 'Anonymous' : h($record['delegate_name'] ?: 'Not provided') ?></td>
              <td class="comment"><?= h($record['went_well'] ?: '—') ?></td>
              <td class="comment"><?= h($record['comments'] ?: '—') ?></td>
            </tr>
          <?php endforeach; ?></tbody>
        </table>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
