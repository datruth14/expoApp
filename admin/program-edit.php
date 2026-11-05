<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_admin();

$connection = db();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$program = [
    'name' => '',
    'slug' => '',
    'description' => '',
    'welcome_message' => '',
    'primary_color' => '#123c7a',
    'accent_color' => '#e5ad36',
    'status' => 'draft',
];
$error = '';

if ($id > 0) {
    $statement = $connection->prepare('SELECT name, slug, description, welcome_message, primary_color, accent_color, status FROM programs WHERE id = ?');
    $statement->bind_param('i', $id);
    $statement->execute();
    $saved = $statement->get_result()->fetch_assoc();
    if (!$saved) {
        http_response_code(404);
        exit('Program not found.');
    }
    $program = $saved;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach (array_keys($program) as $key) {
        $program[$key] = trim((string) ($_POST[$key] ?? ''));
    }
    $program['slug'] = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $program['slug'] !== '' ? $program['slug'] : $program['name']), '-'));

    if ($program['name'] === '' || $program['slug'] === '' || $program['description'] === '') {
        $error = 'Program name, URL slug, and description are required.';
    } elseif (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $program['slug'])) {
        $error = 'Use lowercase letters, numbers, and hyphens in the URL slug.';
    } elseif (!preg_match('/^#[0-9a-fA-F]{6}$/', $program['primary_color']) || !preg_match('/^#[0-9a-fA-F]{6}$/', $program['accent_color'])) {
        $error = 'Choose valid six-digit hex colors.';
    } elseif (!in_array($program['status'], ['draft', 'published'], true)) {
        $error = 'Choose a valid publishing status.';
    } else {
        $check = $connection->prepare('SELECT id FROM programs WHERE slug = ? AND id <> ?');
        $check->bind_param('si', $program['slug'], $id);
        $check->execute();
        if ($check->get_result()->fetch_assoc()) {
            $error = 'That URL slug is already in use.';
        } else {
            if ($id > 0) {
                $statement = $connection->prepare('UPDATE programs SET name = ?, slug = ?, description = ?, welcome_message = ?, primary_color = ?, accent_color = ?, status = ? WHERE id = ?');
                $statement->bind_param('sssssssi', $program['name'], $program['slug'], $program['description'], $program['welcome_message'], $program['primary_color'], $program['accent_color'], $program['status'], $id);
                $statement->execute();
            } else {
                $statement = $connection->prepare('INSERT INTO programs (name, slug, description, welcome_message, primary_color, accent_color, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $statement->bind_param('sssssss', $program['name'], $program['slug'], $program['description'], $program['welcome_message'], $program['primary_color'], $program['accent_color'], $program['status']);
                $statement->execute();
            }
            header('Location: programs.php');
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $id > 0 ? 'Edit' : 'Create' ?> program · Admin</title>
  <style>
    *{box-sizing:border-box}body{margin:0;background:#f2f7ff;color:#17345e;font:15px Arial,sans-serif}.wrap{max-width:760px;margin:auto;padding:24px}.card{padding:22px;background:#fff;border:1px solid #dce6f2;border-radius:16px}label{display:block;margin:14px 0 6px;font-weight:bold}input:not([type=color]),textarea,select{width:100%;padding:11px;border:1px solid #cbd9eb;border-radius:8px;font:inherit}textarea{min-height:110px}.colors{display:grid;grid-template-columns:1fr 1fr;gap:16px}.colors input{width:100%;height:44px}.actions{display:flex;gap:12px;align-items:center;margin-top:20px}.button{padding:11px 15px;border:0;border-radius:9px;background:#174889;color:white;text-decoration:none;font-weight:bold;cursor:pointer}.error{padding:10px;background:#fff0ed;color:#8b2415;border-radius:8px}
  </style>
</head>
<body>
  <main class="wrap">
    <h1><?= $id > 0 ? 'Edit program' : 'Create a program' ?></h1>
    <p>Each program uses the shared public page template. Save it as a draft or publish it.</p>
    <form class="card" method="post">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
      <?php if ($error !== ''): ?><p class="error" role="alert"><?= h($error) ?></p><?php endif; ?>
      <label for="name">Program name</label>
      <input id="name" name="name" maxlength="160" value="<?= h($program['name']) ?>" required>
      <label for="slug">URL slug</label>
      <input id="slug" name="slug" maxlength="180" value="<?= h($program['slug']) ?>" placeholder="my-program">
      <label for="description">Description</label>
      <textarea id="description" name="description" required><?= h($program['description']) ?></textarea>
      <label for="welcome_message">Welcome message</label>
      <textarea id="welcome_message" name="welcome_message"><?= h($program['welcome_message']) ?></textarea>
      <div class="colors">
        <div><label for="primary_color">Primary color</label><input id="primary_color" name="primary_color" type="color" value="<?= h($program['primary_color']) ?>"></div>
        <div><label for="accent_color">Accent color</label><input id="accent_color" name="accent_color" type="color" value="<?= h($program['accent_color']) ?>"></div>
      </div>
      <label for="status">Status</label>
      <select id="status" name="status"><option value="draft"<?= $program['status'] === 'draft' ? ' selected' : '' ?>>Draft</option><option value="published"<?= $program['status'] === 'published' ? ' selected' : '' ?>>Published</option></select>
      <div class="actions"><button class="button" type="submit">Save program</button><a href="programs.php">Cancel</a></div>
    </form>
  </main>
</body>
</html>
