<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use ESign\Csrf;
use ESign\Helpers;

$auth->requireLogin();

$messages = [
    'general_saved'    => 'บันทึกการตั้งค่าทั่วไปเรียบร้อยแล้ว',
    'google_saved'     => 'บันทึกการเชื่อมต่อ Google_API เรียบร้อยแล้ว',
    'upload_saved'     => 'บันทึกการตั้งค่าการอัปโหลดเรียบร้อยแล้ว',
    'user_added'       => 'เพิ่มบัญชีผู้ใช้งานเรียบร้อยแล้ว',
    'user_toggled'     => 'เปลี่ยนสถานะบัญชีผู้ใช้งานเรียบร้อยแล้ว',
    'password_changed' => 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว',
];
$errors = [
    'email_exists'    => 'อีเมลนี้มีผู้ใช้งานในระบบแล้ว',
    'weak_password'   => 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร',
    'invalid_input'   => 'กรุณากรอกข้อมูลให้ครบถ้วนและถูกต้อง',
    'wrong_password'  => 'รหัสผ่านปัจจุบันไม่ถูกต้อง',
    'password_mismatch' => 'รหัสผ่านใหม่ทั้งสองช่องไม่ตรงกัน',
    'cannot_disable_self' => 'ไม่สามารถปิดการใช้งานบัญชีของตัวเองได้',
];

$flashMsg = $messages[$_GET['msg'] ?? ''] ?? null;
$flashErr = $errors[$_GET['error'] ?? ''] ?? null;

$users = $auth->listUsers();

function maskedHint(?string $value): string
{
    if (!$value) {
        return 'ยังไม่ได้ตั้งค่า';
    }
    $len = mb_strlen($value);
    return $len <= 4 ? str_repeat('*', $len) : mb_substr($value, 0, 2) . str_repeat('*', $len - 4) . mb_substr($value, -2);
}

$pageTitle = 'ตั้งค่าระบบ';
include __DIR__ . '/partials/header.php';
?>
<div class="container" style="max-width: 720px;">
  <div class="page-header">
    <div>
      <div class="page-title">ตั้งค่าระบบ</div>
      <div class="page-subtitle">ปรับการตั้งค่าต่างๆ ได้จากหน้านี้ ไม่ต้องแก้ไขไฟล์บนโฮสต์</div>
    </div>
    <a href="dashboard.php" class="btn btn-secondary">&lt; กลับ</a>
  </div>

  <?php if ($flashMsg): ?><div class="alert alert-success" style="margin-bottom: 18px;"><?= Helpers::e($flashMsg) ?></div><?php endif; ?>
  <?php if ($flashErr): ?><div class="alert alert-danger" style="margin-bottom: 18px;"><?= Helpers::e($flashErr) ?></div><?php endif; ?>

  <div class="card" style="margin-bottom: 18px;">
    <div style="font-weight: 700; margin-bottom: 14px;">ตั้งค่าทั่วไป</div>
    <form method="post" action="actions/update_general_settings.php">
      <?= Csrf::field() ?>
      <div class="form-group">
        <label class="form-label" for="system_name">ชื่อระบบ (แสดงบนหัวเว็บและแท็บเบราว์เซอร์)</label>
        <input class="form-control" type="text" id="system_name" name="system_name" value="<?= Helpers::e($config['system_name'] ?? '') ?>" required>
      </div>
      <div class="form-group" style="margin-bottom: 0;">
        <label class="form-label" for="app_base_url">URL เต็มของระบบนี้ (ไม่มี / ปิดท้าย)</label>
        <input class="form-control" type="url" id="app_base_url" name="app_base_url" value="<?= Helpers::e($config['app_base_url']) ?>" required placeholder="https://signflow.rpk24.ac.th">
        <div class="form-hint">ใช้สร้างลิงก์เซ็นเอกสารที่จะส่งให้ผู้ลงนาม ต้องตรงกับโดเมนจริงของระบบ</div>
      </div>
      <button type="submit" class="btn btn-primary" style="margin-top: 16px;">บันทึก</button>
    </form>
  </div>

  <div class="card" style="margin-bottom: 18px;">
    <div style="font-weight: 700; margin-bottom: 4px;">เชื่อมต่อ Google_API กลางของโรงเรียน</div>
    <div class="form-hint" style="margin-bottom: 14px;">ระบบนี้ไม่ทำ OAuth เอง — ส่งไฟล์ไปที่ API กลางของโรงเรียนด้วยรหัสนี้เท่านั้น</div>
    <form method="post" action="actions/update_google_api_settings.php">
      <?= Csrf::field() ?>
      <div class="form-group">
        <label class="form-label" for="google_api_system_key">System Key</label>
        <input class="form-control" type="text" id="google_api_system_key" name="google_api_system_key" placeholder="ปัจจุบัน: <?= Helpers::e(maskedHint($config['google_api']['system_key'])) ?> (เว้นว่างไว้ถ้าไม่เปลี่ยน)">
      </div>
      <div class="form-group">
        <label class="form-label" for="google_api_api_secret">API Secret</label>
        <input class="form-control" type="password" id="google_api_api_secret" name="google_api_api_secret" placeholder="ปัจจุบัน: <?= Helpers::e(maskedHint($config['google_api']['api_secret'])) ?> (เว้นว่างไว้ถ้าไม่เปลี่ยน)">
      </div>
      <div class="form-group" style="margin-bottom: 0;">
        <label class="form-label" for="google_api_uploader_name">ชื่อผู้อัปโหลด (uploader_name ที่จะส่งไปยัง API กลาง)</label>
        <input class="form-control" type="text" id="google_api_uploader_name" name="google_api_uploader_name" value="<?= Helpers::e($config['google_api']['uploader_name']) ?>">
      </div>
      <button type="submit" class="btn btn-primary" style="margin-top: 16px;">บันทึก</button>
    </form>
  </div>

  <div class="card" style="margin-bottom: 18px;">
    <div style="font-weight: 700; margin-bottom: 14px;">การอัปโหลดเอกสาร</div>
    <form method="post" action="actions/update_upload_settings.php">
      <?= Csrf::field() ?>
      <div class="form-group" style="margin-bottom: 0;">
        <label class="form-label" for="upload_max_file_size_mb">ขนาดไฟล์สูงสุดที่อัปโหลดได้ (MB)</label>
        <input class="form-control" type="number" min="1" max="200" id="upload_max_file_size_mb" name="upload_max_file_size_mb" value="<?= (int) $config['upload']['max_file_size_mb'] ?>" required style="max-width: 160px;">
      </div>
      <button type="submit" class="btn btn-primary" style="margin-top: 16px;">บันทึก</button>
    </form>
  </div>

  <div class="card" style="margin-bottom: 18px;">
    <div style="font-weight: 700; margin-bottom: 14px;">จัดการผู้ใช้งาน (เจ้าหน้าที่ผู้รับผิดชอบเอกสาร)</div>
    <?php foreach ($users as $u): ?>
      <div class="signer-row">
        <div style="flex-grow: 1; min-width: 0;">
          <div style="font-size: 13px; font-weight: 700;"><?= Helpers::e($u['full_name']) ?> <?= ((int) $u['id'] === $auth->userId()) ? '(คุณ)' : '' ?></div>
          <div style="font-size: 12px; color: var(--text-muted);"><?= Helpers::e($u['email']) ?></div>
        </div>
        <span class="badge" style="background: <?= $u['is_active'] ? 'var(--success-bg)' : 'var(--danger-bg)' ?>; color: <?= $u['is_active'] ? '#166534' : '#991b1b' ?>;">
          <?= $u['is_active'] ? 'ใช้งานอยู่' : 'ปิดการใช้งาน' ?>
        </span>
        <?php if ((int) $u['id'] !== $auth->userId()): ?>
          <form method="post" action="actions/toggle_user.php" style="margin: 0;">
            <?= Csrf::field() ?>
            <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
            <input type="hidden" name="active" value="<?= $u['is_active'] ? '0' : '1' ?>">
            <button type="submit" class="btn btn-secondary btn-sm"><?= $u['is_active'] ? 'ปิดการใช้งาน' : 'เปิดใช้งาน' ?></button>
          </form>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>

    <div style="font-weight: 700; margin: 18px 0 12px; font-size: 13px;">เพิ่มผู้ใช้งานใหม่</div>
    <form method="post" action="actions/add_user.php">
      <?= Csrf::field() ?>
      <div class="form-group">
        <label class="form-label" for="new_full_name">ชื่อ-นามสกุล</label>
        <input class="form-control" type="text" id="new_full_name" name="full_name" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="new_email">อีเมล</label>
        <input class="form-control" type="email" id="new_email" name="email" required>
      </div>
      <div class="form-group" style="margin-bottom: 0;">
        <label class="form-label" for="new_password">รหัสผ่าน (อย่างน้อย 8 ตัวอักษร)</label>
        <input class="form-control" type="password" id="new_password" name="password" required>
      </div>
      <button type="submit" class="btn btn-primary" style="margin-top: 16px;">เพิ่มผู้ใช้งาน</button>
    </form>
  </div>

  <div class="card">
    <div style="font-weight: 700; margin-bottom: 14px;">เปลี่ยนรหัสผ่านของฉัน</div>
    <form method="post" action="actions/change_password.php">
      <?= Csrf::field() ?>
      <div class="form-group">
        <label class="form-label" for="current_password">รหัสผ่านปัจจุบัน</label>
        <input class="form-control" type="password" id="current_password" name="current_password" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="new_password2">รหัสผ่านใหม่ (อย่างน้อย 8 ตัวอักษร)</label>
        <input class="form-control" type="password" id="new_password2" name="new_password" required>
      </div>
      <div class="form-group" style="margin-bottom: 0;">
        <label class="form-label" for="new_password_confirm">ยืนยันรหัสผ่านใหม่</label>
        <input class="form-control" type="password" id="new_password_confirm" name="new_password_confirm" required>
      </div>
      <button type="submit" class="btn btn-primary" style="margin-top: 16px;">เปลี่ยนรหัสผ่าน</button>
    </form>
  </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
