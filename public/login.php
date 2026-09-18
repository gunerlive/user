<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use ESign\Auth;
use ESign\Csrf;
use ESign\Helpers;

if (!Auth::hasAnyUser($db)) {
    Helpers::redirect('setup.php');
}

if ($auth->check()) {
    Helpers::redirect('dashboard.php');
}

$error = null;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyOrFail($_POST['csrf_token'] ?? null);

    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $user = $auth->attempt($email, $password);
    if ($user) {
        $auth->login($user);
        Helpers::redirect('dashboard.php');
    }
    $error = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง';
}

$pageTitle = 'เข้าสู่ระบบ';
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>เข้าสู่ระบบ - <?= Helpers::e($config['system_name'] ?? 'ระบบเซ็นเอกสารออนไลน์') ?></title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<div style="min-height: 100vh; display: flex;">
  <div style="width: 420px; background: var(--accent); color: #ffffff; padding: 48px 40px; display: flex; flex-direction: column; justify-content: space-between; flex-shrink: 0;">
    <div>
      <div style="width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,0.15); display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg>
      </div>
      <div style="font-size: 24px; font-weight: 800;"><?= Helpers::e($config['system_name'] ?? 'ระบบเซ็นเอกสารออนไลน์') ?></div>
      <div style="font-size: 14px; opacity: 0.85; margin-top: 8px;">สำหรับหนังสือราชการที่ต้องผ่านการลงนามหลายท่านตามลำดับ</div>
    </div>
    <div style="font-size: 12px; opacity: 0.6;">ใช้งานผ่านเว็บเบราว์เซอร์ ไม่ต้องติดตั้งโปรแกรมเพิ่มเติม</div>
  </div>
  <div style="flex-grow: 1; display: flex; align-items: center; justify-content: center; padding: 40px;">
    <div style="width: 100%; max-width: 380px;">
      <h1 style="font-size: 22px; margin-bottom: 4px;">เข้าสู่ระบบ</h1>
      <p style="font-size: 13px; color: var(--text-muted); margin: 0 0 20px;">สำหรับเจ้าหน้าที่ผู้รับผิดชอบเอกสาร</p>

      <?php if ($error): ?>
        <div class="alert alert-danger" style="margin-bottom: 16px;"><?= Helpers::e($error) ?></div>
      <?php endif; ?>

      <form method="post" novalidate>
        <?= Csrf::field() ?>
        <div class="form-group">
          <label class="form-label" for="email">อีเมล / ชื่อผู้ใช้</label>
          <input class="form-control" type="email" id="email" name="email" value="<?= Helpers::e($email) ?>" required autofocus>
        </div>
        <div class="form-group">
          <label class="form-label" for="password">รหัสผ่าน</label>
          <input class="form-control" type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block">เข้าสู่ระบบ</button>
      </form>
      <p style="text-align: center; font-size: 13px; color: var(--text-faint); margin-top: 16px;">ลืมรหัสผ่าน? ติดต่อผู้ดูแลระบบ</p>
    </div>
  </div>
</div>
</body>
</html>
