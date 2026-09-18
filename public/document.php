<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use ESign\Csrf;
use ESign\Helpers;

$auth->requireLogin();

$documentId = (int) ($_GET['id'] ?? 0);
$document = $documentService->find($documentId);

if (!$document) {
    http_response_code(404);
    die('ไม่พบเอกสารนี้');
}

$signers = $signerService->listByDocument($documentId);
$currentSigner = $signerService->currentSigner($documentId);
$activity = $documentService->getActivity($documentId);
$baseUrl = rtrim($config['app_base_url'], '/');
[$statusLabel, $statusColor, $statusBg] = Helpers::statusLabel($document['status']);
$fileExists = is_file(__DIR__ . '/../storage/' . $document['working_file_path']);

$saveError = $_GET['save_error'] ?? null;

$pageTitle = $document['title'];
include __DIR__ . '/partials/header.php';
?>
<div class="container-wide">
  <div class="page-header">
    <div>
      <div class="page-title"><?= Helpers::e($document['document_number']) ?> — <?= Helpers::e($document['title']) ?></div>
      <div class="page-subtitle">
        <span class="badge" style="color: <?= $statusColor ?>; background: <?= $statusBg ?>;"><?= Helpers::e($statusLabel) ?></span>
        สร้างเมื่อ <?= Helpers::thaiDate($document['created_at']) ?>
      </div>
    </div>
    <a href="dashboard.php" class="btn btn-secondary">&lt; กลับไปรายการหนังสือ</a>
  </div>

  <?php if ($saveError): ?>
    <div class="alert alert-danger" style="margin-bottom: 18px;"><?= Helpers::e(urldecode($saveError)) ?></div>
  <?php endif; ?>

  <div style="display: flex; gap: 24px; align-items: flex-start; flex-wrap: wrap;">
    <div style="flex: 2 1 520px; min-width: 320px;">
      <?php if ($fileExists): ?>
        <iframe src="stream_pdf.php?id=<?= $documentId ?>" style="width: 100%; height: 760px; border: 1px solid var(--border); border-radius: 16px; background: #fff;"></iframe>
      <?php else: ?>
        <div class="card empty-state">ไฟล์เอกสารถูกบันทึกเข้า Google Drive และลบออกจากเซิร์ฟเวอร์แล้ว</div>
      <?php endif; ?>
    </div>

    <div style="flex: 1 1 320px; min-width: 300px; display: flex; flex-direction: column; gap: 16px;">
      <div class="card">
        <div style="font-weight: 700; margin-bottom: 12px;">รายชื่อผู้ลงนาม</div>
        <?php foreach ($signers as $s): ?>
          <?php $isCurrent = $currentSigner && (int) $currentSigner['id'] === (int) $s['id']; ?>
          <div class="signer-row" style="background: <?= $isCurrent ? '#eff6ff' : '#f8fafc' ?>; border-color: <?= $isCurrent ? 'var(--accent)' : 'var(--border)' ?>;">
            <div class="signer-dot" style="background: <?= $s['status'] === 'signed' ? 'var(--success)' : 'var(--accent)' ?>;">
              <?php if ($s['status'] === 'signed'): ?>&#10003;<?php else: ?><?= $s['order_no'] ?><?php endif; ?>
            </div>
            <div style="flex-grow: 1; min-width: 0;">
              <div style="font-size: 13px; font-weight: 700; color: var(--text);"><?= Helpers::e($s['full_name']) ?></div>
              <div style="font-size: 11px; color: var(--text-muted);"><?= Helpers::e($s['position_title']) ?></div>
              <?php if ($s['status'] === 'signed'): ?>
                <div style="font-size: 11px; color: var(--success);">เซ็นแล้ว <?= Helpers::thaiDate($s['signed_at']) ?></div>
              <?php elseif ($isCurrent && $document['status'] === 'in_progress'): ?>
                <div style="display: flex; gap: 6px; align-items: center; margin-top: 6px;">
                  <input class="form-control" style="height: 32px; font-size: 11px; padding: 0 8px;" readonly value="<?= Helpers::e($baseUrl . '/sign.php?token=' . $s['token']) ?>">
                  <button type="button" class="btn btn-secondary btn-sm" data-copy="<?= Helpers::e($baseUrl . '/sign.php?token=' . $s['token']) ?>">คัดลอก</button>
                </div>
              <?php else: ?>
                <div style="font-size: 11px; color: var(--text-faint);">รอคิว</div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if ($document['status'] === 'draft'): ?>
        <div class="alert alert-warning">ยังไม่ได้กำหนดตำแหน่งลายเซ็นและสร้างลิงก์ให้ผู้ลงนาม</div>
        <a href="place_signatures.php?id=<?= $documentId ?>" class="btn btn-primary btn-block">ไปกำหนดตำแหน่งลายเซ็น</a>

      <?php elseif ($document['status'] === 'in_progress'): ?>
        <div class="alert alert-info">
          กำลังรอ <strong><?= Helpers::e($currentSigner['full_name'] ?? '-') ?></strong>
          (<?= Helpers::e($currentSigner['position_title'] ?? '-') ?>) ลงนาม — คัดลอกลิงก์ด้านบนส่งให้ผู้ลงนามได้เลย
        </div>
        <?php if ($fileExists): ?><a href="download.php?id=<?= $documentId ?>" class="btn btn-secondary btn-block">ดาวน์โหลดสำเนาปัจจุบัน</a><?php endif; ?>

      <?php elseif ($document['status'] === 'ready_for_review'): ?>
        <div class="alert alert-success">ลงนามครบทุกคนแล้ว กรุณาตรวจสอบความถูกต้องก่อนบันทึกเข้าระบบ</div>
        <a href="download.php?id=<?= $documentId ?>" class="btn btn-secondary btn-block">ดาวน์โหลด PDF เพื่อปริ้น</a>
        <form method="post" action="actions/save_to_drive.php" onsubmit="return confirm('ยืนยันการบันทึก? เมื่อบันทึกแล้ว ระบบจะอัปโหลดไฟล์ขึ้น Google Drive และลบไฟล์ชั่วคราวออกจากเซิร์ฟเวอร์ทันที');">
          <?= Csrf::field() ?>
          <input type="hidden" name="document_id" value="<?= $documentId ?>">
          <button type="submit" class="btn btn-primary btn-block" style="height: 52px;">บันทึกเข้าระบบ (Google Drive)</button>
        </form>

      <?php elseif ($document['status'] === 'saved'): ?>
        <div class="alert alert-success">
          บันทึกเข้าระบบเรียบร้อยแล้ว เมื่อ <?= Helpers::thaiDate($document['saved_at']) ?><br>
          ไฟล์ถูกอัปโหลดขึ้น Google Drive และลบไฟล์ชั่วคราวออกจากเซิร์ฟเวอร์แล้ว
        </div>
        <?php if ($document['drive_web_view_link']): ?>
          <a href="<?= Helpers::e($document['drive_web_view_link']) ?>" target="_blank" rel="noopener" class="btn btn-primary btn-block">เปิด / ดาวน์โหลดจาก Google Drive</a>
        <?php endif; ?>
      <?php endif; ?>

      <div class="card">
        <div style="font-weight: 700; margin-bottom: 10px; font-size: 13px;">ประวัติการทำรายการ</div>
        <?php foreach ($activity as $log): ?>
          <div style="font-size: 12px; color: var(--text-muted); padding: 6px 0; border-bottom: 1px solid #f1f5f9;">
            <?= Helpers::thaiDate($log['created_at']) ?> — <?= Helpers::e($log['message']) ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
<?php
$extraScripts = ['assets/js/copy.js'];
include __DIR__ . '/partials/footer.php';
?>
