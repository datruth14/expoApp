<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Delegate access · <?= h($program['slug']) ?></title>
  <link rel="stylesheet" href="assets/loading.css">
  <script defer src="assets/loading.js"></script>
  <style>
    *{box-sizing:border-box}body{margin:0;padding:24px;background:#f2f7ff;color:#17345e;font:16px/1.6 Arial,sans-serif}main{max-width:520px;margin:5vh auto;padding:28px;border:1px solid #dce6f2;border-radius:18px;background:#fff}h1{line-height:1.2}label{display:block;margin:16px 0 6px;font-weight:bold}input{width:100%;padding:12px;border:1px solid #cbd9eb;border-radius:9px;font:inherit}button{margin-top:20px;padding:12px 16px;border:0;border-radius:9px;background:#174889;color:white;font:inherit;cursor:pointer}a{color:#174889}.notice{font-size:13px;color:#627895}.error{padding:12px;background:#fff0ed;color:#8b2415;border-radius:8px}
  </style>
</head>
<body>
  <main>
    <h1>Access delegate feedback</h1>
    <p>Enter your KingsChat username and phone number to continue.</p>
    <p class="notice">Temporary access uses the details you provide. It does not verify ownership of your KingsChat account or phone number.</p>
    <?php if ($manualError !== ''): ?><p class="error" role="alert"><?= h($manualError) ?></p><?php endif; ?>
    <form method="post" action="delegate-login.php">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="program" value="<?= h($slug) ?>">
      <input type="hidden" name="area" value="<?= h($area) ?>">
      <label for="username">KingsChat username</label>
      <input id="username" name="username" autocomplete="username" maxlength="65" value="<?= h($handle) ?>" placeholder="@your.username" required>
      <label for="phone">Phone number with country code</label>
      <input id="phone" name="phone" type="tel" autocomplete="tel" maxlength="32" value="<?= h($phone) ?>" placeholder="+2348012345678" required>
      <button type="submit">Continue to feedback</button>
    </form>
    <p><a href="<?= h($backUrl) ?>">Back to the program</a></p>
  </main>
</body>
</html>
