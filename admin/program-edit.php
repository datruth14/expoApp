<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_admin();

$connection = db();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$program = [
    'name' => '',
    'slug' => '',
    'platform_title' => 'Delegate Experience Centre',
    'tagline' => '',
    'description' => '',
    'welcome_message' => '',
    'banner_path' => null,
    'primary_color' => '#123c7a',
    'accent_color' => '#e5ad36',
    'status' => 'draft',
];
$error = '';

if ($id > 0) {
    $saved = $connection->query('SELECT name, slug, platform_title, tagline, description, welcome_message, banner_path, primary_color, accent_color, status FROM programs WHERE id = ?', [$id])->fetch();
    if (!$saved) {
        http_response_code(404);
        exit('Program not found.');
    }
    $program = $saved;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $uploadedBanner = null;
    try {
        foreach (['name', 'slug', 'platform_title', 'tagline', 'description', 'welcome_message', 'primary_color', 'accent_color', 'status'] as $key) {
            $program[$key] = trim((string) ($_POST[$key] ?? ''));
        }
        $program['slug'] = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $program['slug'] !== '' ? $program['slug'] : $program['name']), '-'));

        if ($program['name'] === '' || $program['slug'] === '' || $program['description'] === '' || $program['platform_title'] === '') {
            $error = 'Program name, URL slug, platform title, and description are required.';
        } elseif (mb_strlen($program['name']) > 160 || mb_strlen($program['platform_title']) > 160 || mb_strlen($program['tagline']) > 255 || strlen($program['slug']) > 180) {
            $error = 'The program name, URL slug, platform title, or tagline is too long.';
        } elseif (strlen($program['description']) > 65535 || strlen($program['welcome_message']) > 65535) {
            $error = 'The description or welcome message exceeds the database text limit. Please shorten it.';
        } elseif (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $program['slug'])) {
            $error = 'Use lowercase letters, numbers, and hyphens in the URL slug.';
        } elseif (!preg_match('/^#[0-9a-fA-F]{6}$/', $program['primary_color']) || !preg_match('/^#[0-9a-fA-F]{6}$/', $program['accent_color'])) {
            $error = 'Choose valid six-digit hex colors.';
        } elseif (!in_array($program['status'], ['draft', 'published'], true)) {
            $error = 'Choose a valid publishing status.';
        } else {
            $check = $connection->query('SELECT id FROM programs WHERE slug = ? AND id <> ?', [$program['slug'], $id]);
            if ($check->fetch()) {
                $error = 'That URL slug is already in use.';
            }
        }

        $oldBanner = $program['banner_path'];
        $newBanner = $oldBanner;
        $file = $_FILES['banner'] ?? null;
        $removeBanner = isset($_POST['remove_banner']);
        if ($error === '' && $file && $file['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
                $error = 'The banner could not be uploaded. Please try again.';
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $error = 'The banner must be 5 MB or smaller.';
            } else {
                $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                $imageInfo = @getimagesize($file['tmp_name']);
                // getimagesize also identifies the MIME type on hosts without Fileinfo.
                $mime = $imageInfo['mime'] ?? '';
                if (class_exists('finfo') && method_exists('finfo', 'file')) {
                    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
                }
                if (!isset($extensions[$mime]) || !$imageInfo || $imageInfo[0] > 10000 || $imageInfo[1] > 10000) {
                    $error = 'Choose a valid JPG, PNG, or WebP image no larger than 10,000 by 10,000 pixels.';
                } else {
                    $uploadDirectory = __DIR__ . '/../uploads/banners';
                    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
                        $error = 'The banner upload folder could not be created. Check hosting folder permissions.';
                    } else {
                        $fileName = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
                        if (!move_uploaded_file($file['tmp_name'], $uploadDirectory . '/' . $fileName)) {
                            $error = 'The banner could not be saved. Check hosting folder permissions.';
                        } else {
                            $newBanner = 'uploads/banners/' . $fileName;
                            $uploadedBanner = $newBanner;
                        }
                    }
                }
            }
        } elseif ($error === '' && $removeBanner) {
            $newBanner = null;
        }

        if ($error === '') {
            if ($id > 0) {
                $connection->query('UPDATE programs SET name = ?, slug = ?, platform_title = ?, tagline = ?, description = ?, welcome_message = ?, banner_path = ?, primary_color = ?, accent_color = ?, status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?', [$program['name'], $program['slug'], $program['platform_title'], $program['tagline'], $program['description'], $program['welcome_message'], $newBanner, $program['primary_color'], $program['accent_color'], $program['status'], $id]);
            } else {
                // ASCII JSON escapes preserve emoji even on older MySQL utf8 columns.
                $servicesJson = json_encode(default_program_services(), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
                $connection->query('INSERT INTO programs (name, slug, platform_title, tagline, description, welcome_message, banner_path, primary_color, accent_color, services_json, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$program['name'], $program['slug'], $program['platform_title'], $program['tagline'], $program['description'], $program['welcome_message'], $newBanner, $program['primary_color'], $program['accent_color'], $servicesJson, $program['status']]);
            }
            $uploadedBanner = null; // The saved row now owns the uploaded file.

            if ($oldBanner && $oldBanner !== $newBanner && str_starts_with($oldBanner, 'uploads/banners/')) {
                $oldFile = __DIR__ . '/../' . $oldBanner;
                if (is_file($oldFile)) {
                    unlink($oldFile);
                }
            }

            header('Location: programs.php');
            exit;
        }
    } catch (Throwable $exception) {
        if ($uploadedBanner !== null) {
            $uploadedFile = __DIR__ . '/../' . $uploadedBanner;
            if (is_file($uploadedFile)) {
                unlink($uploadedFile);
            }
        }
        $reference = bin2hex(random_bytes(6));
        error_log('Delegate Experience Centre program save [' . $reference . '] (' . $exception->getCode() . '): ' . $exception->getMessage());
        $hint = match ((int) $exception->getCode()) {
            1054, 1146 => 'The database schema needs updating. Check the imported schema and required upgrades.',
            1142, 1143 => 'The database user needs INSERT and UPDATE privileges for the programs table in cPanel.',
            1062 => 'The URL slug is already in use. Choose another slug.',
            1366 => 'A database column cannot store these characters. The MySQL tables should use utf8mb4.',
            1406 => 'One of the values exceeds the live database column size. Check that the schema is up to date.',
            default => 'Check the error details below or the cPanel PHP error log.',
        };
        $error = 'Unable to save the program. ' . $hint . ' Details: ' . $exception->getMessage() . ' (Reference: ' . $reference . ').';
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
    *{box-sizing:border-box}body{margin:0;background:#f2f7ff;color:#17345e;font:15px Arial,sans-serif}.wrap{max-width:760px;margin:auto;padding:24px}.card{padding:22px;background:#fff;border:1px solid #dce6f2;border-radius:16px}label{display:block;margin:14px 0 6px;font-weight:bold}input:not([type=color]):not([type=checkbox]),textarea,select{width:100%;padding:11px;border:1px solid #cbd9eb;border-radius:8px;font:inherit}input[type=file]{width:100%;padding:10px;border:1px solid #cbd9eb;border-radius:8px;background:white}textarea{min-height:100px}.colors{display:grid;grid-template-columns:1fr 1fr;gap:16px}.colors input{width:100%;height:44px}.actions{display:flex;gap:12px;align-items:center;margin-top:20px}.button{padding:11px 15px;border:0;border-radius:9px;background:#174889;color:white;text-decoration:none;font-weight:bold;cursor:pointer}.error{padding:10px;background:#fff0ed;color:#8b2415;border-radius:8px}.preview{display:block;max-width:100%;max-height:260px;object-fit:contain;margin:10px 0;border:1px solid #dce6f2;border-radius:10px}.hint{font-size:12px;color:#627895}
  </style>
</head>
<body>
  <main class="wrap">
    <h1><?= $id > 0 ? 'Edit program' : 'Create a program' ?></h1>
    <p>Program pages share the Delegate Experience Centre template. Each program has its own banner and feedback records.</p>
    <form class="card" method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
      <?php if ($error !== ''): ?><p class="error" role="alert"><?= h($error) ?></p><?php endif; ?>
      <label for="name">Program name</label>
      <input id="name" name="name" maxlength="160" value="<?= h($program['name']) ?>" required>
      <label for="slug">URL slug</label>
      <input id="slug" name="slug" maxlength="180" value="<?= h($program['slug']) ?>" placeholder="my-program" required>
      <label for="platform_title">Platform title</label>
      <input id="platform_title" name="platform_title" maxlength="160" value="<?= h($program['platform_title']) ?>" required>
      <label for="tagline">Tagline</label>
      <input id="tagline" name="tagline" maxlength="255" value="<?= h($program['tagline']) ?>">
      <label for="description">Description</label>
      <textarea id="description" name="description" required><?= h($program['description']) ?></textarea>
      <label for="welcome_message">Welcome message</label>
      <textarea id="welcome_message" name="welcome_message"><?= h($program['welcome_message']) ?></textarea>
      <label for="banner">Program banner (JPG, PNG, or WebP; max 5 MB)</label>
      <input id="banner" name="banner" type="file" accept="image/jpeg,image/png,image/webp">
      <?php if (!empty($program['banner_path'])): ?>
        <img class="preview" src="../<?= h($program['banner_path']) ?>" alt="Current program banner">
        <label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="remove_banner"> Remove current banner</label>
      <?php endif; ?>
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
