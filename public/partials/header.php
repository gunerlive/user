<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php $systemName = $config['system_name'] ?? 'ระบบเซ็นเอกสารออนไลน์'; ?>
<title><?= \ESign\Helpers::e(($pageTitle ?? '') !== '' ? $pageTitle . ' - ' . $systemName : $systemName) ?></title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<div class="navbar">
  <a href="dashboard.php" class="navbar-brand">
    <span class="logo">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg>
    </span>
    <?= \ESign\Helpers::e($systemName) ?>
  </a>
  <?php if (isset($auth) && $auth->check()): ?>
  <div class="navbar-user">
    <a href="settings.php" class="btn btn-secondary btn-sm">ตั้งค่าระบบ</a>
    <span><?= \ESign\Helpers::e($auth->userName()) ?></span>
    <span class="avatar"><?= \ESign\Helpers::e(mb_substr($auth->userName(), 0, 1)) ?></span>
    <a href="logout.php" class="btn btn-secondary btn-sm">ออกจากระบบ</a>
  </div>
  <?php endif; ?>
</div>
