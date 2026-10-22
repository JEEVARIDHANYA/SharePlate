<?php
require_once __DIR__ . '/functions.php';
$page_title = $page_title ?? 'SharePlate — Share More. Waste Less.';
$flash_message = take_flash();
$is_admin_page = str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/');
$base_prefix = $is_admin_page ? '../' : '';
$admin_href = $is_admin_page ? 'dashboard.php' : 'admin/dashboard.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Share surplus food with nearby people and charities through SharePlate.">
  <title><?= e($page_title) ?></title>
  <link rel="stylesheet" href="<?= e($base_prefix) ?>assets/css/style.css">
  <script defer src="<?= e($base_prefix) ?>assets/js/script.js"></script>
</head>
<body>
<header class="site-header"><div class="container nav-wrap">
  <a class="brand" href="<?= e($base_prefix) ?>index.php" aria-label="SharePlate home"><span class="brand-mark">✳</span> SharePlate</a>
  <button class="nav-toggle" type="button" aria-label="Toggle navigation" aria-expanded="false">☰</button>
  <nav class="main-nav" aria-label="Main navigation">
    <a href="<?= e($base_prefix) ?>available-food.php">Find Food</a>
    <?php if (current_user() && in_array(current_user()['role'], ['donor', 'admin'], true)): ?><a href="<?= e($base_prefix) ?>add-food.php">Donate Food</a><?php endif; ?>
    <?php if (current_user()): ?>
      <?php if (current_user()['role'] === 'donor'): ?><a href="<?= e($base_prefix) ?>my-donations.php">My Donations</a><?php elseif (in_array(current_user()['role'], ['recipient', 'charity'], true)): ?><a href="<?= e($base_prefix) ?>my-requests.php">My Requests</a><?php endif; ?>
      <?php if (current_user()['role'] === 'admin'): ?><a href="<?= e($admin_href) ?>">Admin</a><?php endif; ?>
      <a href="<?= e($base_prefix) ?>profile.php">Hi, <?= e(explode(' ', current_user()['name'])[0]) ?></a>
      <form method="post" action="<?= e($base_prefix) ?>logout.php" class="nav-logout"><?= csrf_field() ?><button class="link-button nav-login">Log out</button></form>
    <?php else: ?><a href="<?= e($base_prefix) ?>login.php">Log in</a><a class="button button-small" href="<?= e($base_prefix) ?>register.php">Join SharePlate</a><?php endif; ?>
  </nav>
</div></header>
<main>
<?php if ($flash_message): ?><div class="container"><div class="alert alert-<?= e($flash_message['type']) ?>" role="status"><?= e($flash_message['message']) ?></div></div><?php endif; ?>
