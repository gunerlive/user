<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use ESign\Auth;
use ESign\Csrf;
use ESign\Helpers;

// หน้านี้ใช้งานได้ครั้งเดียวตอนติดตั้งระบบใหม่ๆ เท่านั้น เพื่อความปลอดภัย
// เมื่อมีผู้ใช้ในระบบแล้วจะไม่สามารถเข้าหน้านี้ได้อีก
if (Auth::hasAnyUser($db)) {
    Helpers::redirect('login.php');
}

$errors = [];
$fullName = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyOrFail($_POST['csrf_token'] ?? null);

    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

    if ($fullName === '') {
        $errors[] = 'กรุณากรอกชื่อ-นามสกุล';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'กรุณากรอกอีเมลให้ถูกต้อง';
    }
    if (strlen($password) < 8) {
        $errors[] = 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร';
    }
    if ($password !== $passwordConfirm) {
        $errors[] = 'รหัสผ่านทั้งสองช่องไม่ตรงกัน';
    }

    if (empty($errors)) {
        $stmt = $db->prepare('INSERT INTO signflow_users (full_name, email, password_hash) VALUES (:full_name, :email, :password_hash)');
        $stmt->execute([
            'full_name'      => $fullName,
            'email'          => $email,
            'password_hash'  => password_hash($password, PASSWORD_DEFAULT),
        ]);

        $user = $auth->attempt($email, $password);
        if ($user) {
            $auth->login($user);
            Helpers::redirect('dashboard.php');
        }
        Helpers::redirect('login.php');
    }
}

$pageTitle = 'ตั้งค่าเริ่มต้นระบบ';
include __DIR__ . '/partials/header.php';
?>
<div class="container" style="max-width: 480px;">
  <div class="card">
    <h1 style="font-size: 20px; margin-bottom: 6px;">สร้างบัญชีเจ้าหน้าที่คนแรก</h1>
    <p style="font-size: 13px; color: var(--text-muted); margin: 0 0 20px;">ระบบยังไม่มีผู้ใช้งาน กรุณาสร้างบัญชีผู้ดูแล/ผู้รับผิดชอบเอกสารคนแรกเพื่อเริ่มใช้งาน</p>

    <?php foreach ($errors as $error): ?>
      <div class="alert alert-danger" style="margin-bottom: 12px;"><?= Helpers::e($error) ?></div>
    <?php endforeach; ?>

    <form method="post" novalidate>
      <?= Csrf::field() ?>
      <div class="form-group">
        <label class="form-label" for="full_name">ชื่อ-นามสกุล</label>
        <input class="form-control" type="text" id="full_name" name="full_name" value="<?= Helpers::e($fullName) ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="email">อีเมล / ชื่อผู้ใช้สำหรับเข้าสู่ระบบ</label>
        <input class="form-control" type="email" id="email" name="email" value="<?= Helpers::e($email) ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="password">รหัสผ่าน (อย่างน้อย 8 ตัวอักษร)</label>
        <input class="form-control" type="password" id="password" name="password" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="password_confirm">ยืนยันรหัสผ่าน</label>
        <input class="form-control" type="password" id="password_confirm" name="password_confirm" required>
      </div>
      <button type="submit" class="btn btn-primary btn-block">สร้างบัญชีและเข้าสู่ระบบ</button>
    </form>
  </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
