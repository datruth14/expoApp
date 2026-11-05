<?php
declare(strict_types=1);
// Shared presentation template for all published program records.
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="<?= h($program['description']) ?>">
  <title><?= h($program['name']) ?> · Delegate Experience Centre</title>
  <style>
    :root{--primary:<?= h($program['primary_color']) ?>;--accent:<?= h($program['accent_color']) ?>}*{box-sizing:border-box}body{margin:0;padding:24px;background:linear-gradient(140deg,#fff,#f2f7ff 65%,#fff8e9);font:16px Arial,sans-serif;color:#17345e}.wrap{max-width:900px;margin:auto}.hero,.content{padding:clamp(22px,5vw,48px);background:white;border:1px solid #dce6f2;border-radius:20px;box-shadow:0 10px 28px #17365d12}.hero{border-top:7px solid var(--accent)}.brand{font-size:12px;font-weight:bold;letter-spacing:1.5px;text-transform:uppercase;color:var(--primary)}h1{font-size:clamp(32px,6vw,54px);color:var(--primary);margin:24px 0 12px}.welcome{font-size:19px;line-height:1.6}.content{margin-top:18px;line-height:1.7;white-space:pre-line}.back{display:inline-block;margin-top:20px;color:var(--primary)}
  </style>
</head>
<body>
  <main class="wrap">
    <section class="hero">
      <div class="brand">Delegate Experience Centre</div>
      <h1><?= h($program['name']) ?></h1>
      <?php if ($program['welcome_message'] !== ''): ?><p class="welcome"><?= h($program['welcome_message']) ?></p><?php endif; ?>
    </section>
    <section class="content"><?= h($program['description']) ?></section>
    <a class="back" href="index.html">Back to Delegate Experience Centre</a>
  </main>
</body>
</html>
