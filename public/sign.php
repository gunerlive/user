<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use ESign\Csrf;
use ESign\Helpers;

$token = (string) ($_GET['token'] ?? '');
$signer = $token !== '' ? $signerService->findByToken($token) : null;

if (!$signer) {
    http_response_code(404);
    $notFound = true;
} else {
    $documentId = (int) $signer['document_id'];
    $isTurn = $signerService->isSignerTurn($signer);
    $fileExists = is_file(__DIR__ . '/../storage/' . $signer['working_file_path']);
    $baseUrl = rtrim($config['app_base_url'], '/');

    $nextSigner = null;
    if ($signer['status'] === 'signed' && $signer['document_status'] === 'in_progress') {
        $nextSigner = $signerService->currentSigner($documentId);
    }
}

$pageTitle = 'หน้าลงนามเอกสาร';
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>หน้าลงนามเอกสาร - <?= Helpers::e($config['system_name'] ?? 'ระบบเซ็นเอกสารออนไลน์') ?></title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<div class="navbar">
  <div class="navbar-brand">
    <span class="logo"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg></span>
    เอกสารรอการลงนาม
  </div>
</div>

<?php if (!empty($notFound)): ?>
  <div class="container" style="max-width: 480px; text-align: center;">
    <div class="card">ไม่พบลิงก์นี้ อาจถูกยกเลิกหรือพิมพ์ผิด กรุณาตรวจสอบลิงก์ที่ได้รับอีกครั้ง</div>
  </div>

<?php elseif ($signer['document_status'] === 'cancelled'): ?>
  <div class="container" style="max-width: 480px; text-align: center;">
    <div class="alert alert-danger">เอกสารนี้ถูกยกเลิกแล้ว ไม่สามารถลงนามได้</div>
  </div>

<?php elseif ($signer['status'] === 'signed'): ?>
  <div class="container" style="max-width: 480px;">
    <div class="card" style="text-align: center;">
      <div style="width: 64px; height: 64px; border-radius: 999px; background: var(--success-bg); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
      </div>
      <div style="font-size: 17px; font-weight: 800; margin-bottom: 6px;">คุณได้เซ็นเอกสารนี้แล้ว</div>
      <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 4px;"><?= Helpers::e($signer['document_title']) ?></div>
      <div style="font-size: 12px; color: var(--text-faint); margin-bottom: 20px;">เซ็นเมื่อ <?= Helpers::thaiDate($signer['signed_at']) ?></div>

      <?php if ($signer['document_status'] === 'saved'): ?>
        <div class="alert alert-info" style="text-align: right; margin-bottom: 12px;">เอกสารนี้ลงนามครบและบันทึกเข้าระบบเรียบร้อยแล้ว</div>
        <?php if (!empty($signer['drive_web_view_link'])): ?>
          <a class="btn btn-primary btn-block" target="_blank" rel="noopener" href="<?= Helpers::e($signer['drive_web_view_link'] ?? '') ?>">เปิดเอกสารฉบับสมบูรณ์</a>
        <?php endif; ?>
      <?php elseif ($signer['document_status'] === 'ready_for_review'): ?>
        <div class="alert alert-info" style="text-align: right;">เอกสารลงนามครบทุกคนแล้ว ขณะนี้รอเจ้าหน้าที่ตรวจสอบและบันทึกเข้าระบบ</div>
      <?php elseif ($nextSigner): ?>
        <div style="text-align: right; background: #f8fafc; border: 1px solid var(--border); border-radius: 12px; padding: 14px; margin-bottom: 8px;">
          <div style="font-size: 12px; color: var(--text-faint); margin-bottom: 4px;">กรุณาส่งลิงก์นี้ให้ผู้ลงนามคนถัดไป</div>
          <div style="font-size: 14px; font-weight: 700; margin-bottom: 8px;"><?= Helpers::e($nextSigner['full_name']) ?> (<?= Helpers::e($nextSigner['position_title']) ?>)</div>
          <div style="display: flex; gap: 8px;">
            <input class="form-control" readonly style="font-size: 12px;" value="<?= Helpers::e($baseUrl . '/sign.php?token=' . $nextSigner['token']) ?>">
            <button type="button" class="btn btn-primary btn-sm" data-copy="<?= Helpers::e($baseUrl . '/sign.php?token=' . $nextSigner['token']) ?>">คัดลอก</button>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <script src="assets/js/copy.js"></script>

<?php else: ?>
  <div class="container" style="max-width: 640px;">
    <div class="card" style="margin-bottom: 16px;">
      <div style="font-size: 11px; color: var(--text-faint);"><?= Helpers::e($signer['document_number']) ?></div>
      <div style="font-size: 16px; font-weight: 700; margin-bottom: 10px;"><?= Helpers::e($signer['document_title']) ?></div>
      <div style="display: flex; align-items: center; gap: 10px;">
        <div class="signer-dot" style="background: var(--accent); width: 32px; height: 32px;"><?= (int) $signer['order_no'] ?></div>
        <div>
          <div style="font-size: 13px; font-weight: 700;">คุณคือผู้ลงนามลำดับที่ <?= (int) $signer['order_no'] ?></div>
          <div style="font-size: 12px; color: var(--text-muted);"><?= Helpers::e($signer['full_name']) ?> — <?= Helpers::e($signer['position_title']) ?></div>
        </div>
      </div>
    </div>

    <?php if (($_GET['error'] ?? '') === 'stamp_failed'): ?>
      <div class="alert alert-danger" style="margin-bottom: 16px;">เกิดข้อผิดพลาดขณะบันทึกลายเซ็น กรุณาลองวาดลายเซ็นและกดยืนยันอีกครั้ง</div>
    <?php endif; ?>

    <?php if (!$isTurn): ?>
      <div class="alert alert-warning">ยังไม่ถึงคิวของคุณ กรุณารอผู้ลงนามคนก่อนหน้าดำเนินการให้เสร็จก่อน แล้วลองเปิดลิงก์นี้อีกครั้ง</div>
      <?php if ($fileExists): ?>
        <div class="card" style="margin-top: 16px; padding: 8px;">
          <div id="pdfShell" style="max-height: 420px; overflow: auto;"></div>
        </div>
      <?php endif; ?>
    <?php else: ?>
      <?php if ($fileExists): ?>
        <div class="card" style="margin-bottom: 16px; padding: 8px;">
          <div id="pdfShell" style="max-height: 420px; overflow: auto;"></div>
        </div>
      <?php endif; ?>

      <form method="post" action="actions/submit_signature.php" id="signForm">
        <?= Csrf::field() ?>
        <input type="hidden" name="token" value="<?= Helpers::e($token) ?>">
        <input type="hidden" name="signature_data" id="signatureData">
        <div class="card">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
            <div style="font-weight: 700; font-size: 13px;">วาดลายเซ็นของคุณ</div>
            <button type="button" class="btn btn-secondary btn-sm" id="clearPadBtn">ล้าง</button>
          </div>
          <div style="border: 2px dashed #cbd5e1; border-radius: 12px; background: #f8fafc; touch-action: none;">
            <canvas id="signaturePad" style="width: 100%; height: 180px; display: block;"></canvas>
          </div>
          <div id="signError" class="alert alert-danger" style="display: none; margin-top: 10px;">กรุณาวาดลายเซ็นก่อนกดยืนยัน</div>
          <button type="submit" class="btn btn-primary btn-block" style="height: 52px; margin-top: 16px;">ยืนยันการเซ็นเอกสาร</button>
        </div>
      </form>
    <?php endif; ?>
  </div>

  <?php if ($fileExists): ?>
  <script>
  window.SIGN_PAGE_CONFIG = {
    pdfUrl: 'stream_pdf.php?token=<?= urlencode($token) ?>',
    highlight: {
      page: <?= (int) $signer['sig_page'] ?>,
      x: <?= (float) $signer['sig_x_pct'] ?>,
      y: <?= (float) $signer['sig_y_pct'] ?>,
      w: <?= (float) $signer['sig_w_pct'] ?>
    },
    isTurn: <?= $isTurn ? 'true' : 'false' ?>
  };
  </script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
  <?php endif; ?>
  <?php if ($isTurn): ?>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/signature_pad/4.1.7/signature_pad.umd.min.js"></script>
  <?php endif; ?>
  <script src="assets/js/sign.js"></script>
<?php endif; ?>
</body>
</html>
