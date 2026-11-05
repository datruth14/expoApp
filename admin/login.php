<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

header('Cache-Control: no-store');

if (!empty($_SESSION['is_admin'])) {
    header('Location: programs.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $attempts = (int) ($_SESSION['login_attempts'] ?? 0);
    $lockedUntil = (int) ($_SESSION['login_locked_until'] ?? 0);

    if ($lockedUntil > time()) {
        $error = 'Too many attempts. Please wait a few minutes and try again.';
    } elseif (hash_equals((string) $settings['admin_username'], $username)
        && password_verify($password, (string) $settings['admin_password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['is_admin'] = true;
        $_SESSION['login_attempts'] = 0;
        unset($_SESSION['login_locked_until']);
        header('Location: programs.php');
        exit;
    } else {
        $attempts++;
        $_SESSION['login_attempts'] = $attempts >= 5 ? 0 : $attempts;
        if ($attempts >= 5) {
            $_SESSION['login_locked_until'] = time() + 300;
        }
        $error = 'Username or password is incorrect.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin login · Delegate Experience Centre</title>
  <style>
    *{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:20px;background:#f2f7ff;font:16px Arial,sans-serif;color:#17345e}.card{width:min(100%,420px);padding:28px;background:#fff;border:1px solid #dce6f2;border-radius:18px;box-shadow:0 12px 32px #17365d18}h1{margin-top:0;color:#123c7a}label{display:block;margin:16px 0 6px;font-weight:bold}input{width:100%;padding:12px;border:1px solid #cbd9eb;border-radius:9px;font:inherit}.button{margin-top:20px;width:100%;padding:12px;border:0;border-radius:9px;background:#174889;color:#fff;font-weight:bold;font:inherit;cursor:pointer}.error{padding:10px;background:#fff0ed;color:#8b2415;border-radius:8px}.back{display:inline-block;margin-top:18px;color:#174889}
  </style>
</head>
<body>
  <main class="card">
    <h1>Administrator login</h1>
    <p>Sign in to create and manage program pages.</p>
    <?php if ($error !== ''): ?><p class="error" role="alert"><?= h($error) ?></p><?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
      <label for="username">Username</label>
      <input id="username" name="username" autocomplete="username" required>
      <label for="password">Password</label>
      <input id="password" name="password" type="password" autocomplete="current-password" required>
      <button class="button" type="submit">Log in</button>
    </form>
    <a class="back" href="../index.php">View homepage</a>
  </main>
</body>
</html>
