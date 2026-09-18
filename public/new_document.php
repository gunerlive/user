<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use ESign\Csrf;
use ESign\Helpers;

$auth->requireLogin();

$errors = [];
if (isset($_GET['error'])) {
    $errors[] = match ($_GET['error']) {
        'no_file' => 'กรุณาเลือกไฟล์ PDF ที่จะอัปโหลด',
        'bad_ext' => 'รองรับเฉพาะไฟล์นามสกุล .pdf เท่านั้น',
        'too_big' => 'ไฟล์มีขนาดใหญ่เกินกำหนด (สูงสุด ' . (int) ($config['upload']['max_file_size_mb'] ?? 20) . ' MB)',
        'no_signers' => 'กรุณาระบุผู้ลงนามอย่างน้อย 1 คน',
        'upload_failed' => 'อัปโหลดไฟล์ไม่สำเร็จ กรุณาลองใหม่อีกครั้ง',
        default => 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง',
    };
}

$pageTitle = 'ออกหนังสือใหม่';
include __DIR__ . '/partials/header.php';
?>
<div class="container">
  <div class="page-header">
    <div>
      <div class="page-title">ออกหนังสือใหม่</div>
      <div class="page-subtitle">ขั้นตอนที่ 1 จาก 2 — อัปโหลดเอกสารและระบุรายชื่อผู้ลงนาม</div>
    </div>
    <a href="dashboard.php" class="btn btn-secondary">&lt; กลับ</a>
  </div>

  <?php foreach ($errors as $error): ?>
    <div class="alert alert-danger" style="margin-bottom: 16px;"><?= Helpers::e($error) ?></div>
  <?php endforeach; ?>

  <form method="post" action="actions/create_document.php" enctype="multipart/form-data" id="newDocForm">
    <?= Csrf::field() ?>
    <div class="card" style="margin-bottom: 18px;">
      <div style="font-weight: 700; margin-bottom: 14px;">ข้อมูลหนังสือ</div>
      <div class="form-group">
        <label class="form-label" for="document_number">เลขที่หนังสือ</label>
        <input class="form-control" type="text" id="document_number" name="document_number" placeholder="เช่น 022/2569">
      </div>
      <div class="form-group">
        <label class="form-label" for="title">เรื่อง</label>
        <input class="form-control" type="text" id="title" name="title" required placeholder="เช่น ขออนุมัติจัดกิจกรรมทัศนศึกษาประจำปี 2569">
      </div>
      <div class="form-group" style="margin-bottom: 0;">
        <label class="form-label" for="description">คำอธิบายเพิ่มเติม (ถ้ามี)</label>
        <textarea class="form-control" id="description" name="description" rows="2"></textarea>
      </div>
    </div>

    <div class="card" style="margin-bottom: 18px;">
      <div style="font-weight: 700; margin-bottom: 14px;">ไฟล์เอกสาร (PDF)</div>
      <div class="dropzone" id="dropzone">
        <div id="dropzoneText">ลากไฟล์ PDF มาวาง หรือ <span style="color: var(--accent); font-weight: 700;">คลิกเพื่อเลือกไฟล์</span></div>
        <input type="file" id="pdfFile" name="pdf_file" accept="application/pdf" style="display:none;" required>
      </div>
      <div class="form-hint" style="margin-top: 8px;">รองรับเฉพาะไฟล์ .pdf ขนาดไม่เกิน <?= (int) ($config['upload']['max_file_size_mb'] ?? 20) ?> MB</div>
    </div>

    <div class="card" style="margin-bottom: 18px;">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
        <div style="font-weight: 700;">ผู้ลงนามตามลำดับ</div>
        <button type="button" class="btn btn-secondary btn-sm" id="addSignerBtn">+ เพิ่มผู้ลงนาม</button>
      </div>
      <div id="signerList"></div>
      <template id="signerRowTemplate">
        <div class="signer-row">
          <div class="signer-dot" style="background: var(--accent);"><span class="signer-order">1</span></div>
          <input class="form-control" type="text" name="signers[][full_name]" placeholder="ชื่อ-นามสกุล" required style="flex: 1;">
          <input class="form-control" type="text" name="signers[][position_title]" placeholder="ตำแหน่ง" required style="flex: 1;">
          <button type="button" class="btn btn-secondary btn-sm remove-signer-btn" aria-label="ลบผู้ลงนาม" style="flex-shrink: 0;">&#10005;</button>
        </div>
      </template>
      <div class="form-hint">ผู้ลงนามจะได้รับลิงก์ให้เซ็นเรียงตามลำดับที่กำหนดไว้นี้ ในขั้นตอนถัดไปจะให้คลิกกำหนดตำแหน่งลายเซ็นบนเอกสาร</div>
    </div>

    <button type="submit" class="btn btn-primary btn-block" style="height: 52px; font-size: 15px;">ถัดไป: กำหนดตำแหน่งลายเซ็น &gt;</button>
  </form>
</div>
<?php
$extraScripts = ['assets/js/new_document.js'];
include __DIR__ . '/partials/footer.php';
?>
