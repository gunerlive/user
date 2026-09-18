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

if ($document['status'] !== 'draft') {
    Helpers::redirect('document.php?id=' . $documentId);
}

$signers = $signerService->listByDocument($documentId);
$colors = ['#1e3a8a', '#0d9488', '#b45309', '#7c2d12', '#4338ca', '#be185d'];
foreach ($signers as $i => &$s) {
    $s['color'] = $colors[$i % count($colors)];
}
unset($s);

$pageTitle = 'กำหนดตำแหน่งลายเซ็น';
include __DIR__ . '/partials/header.php';
?>
<div class="container-wide">
  <div class="page-header">
    <div>
      <div class="page-title"><?= Helpers::e($document['title']) ?></div>
      <div class="page-subtitle">ขั้นตอนที่ 2 จาก 2 — คลิกชื่อผู้ลงนามด้านซ้าย แล้วคลิกบนเอกสารเพื่อวางตำแหน่งลายเซ็น</div>
    </div>
    <a href="dashboard.php" class="btn btn-secondary">&lt; กลับ</a>
  </div>

  <div style="display: flex; gap: 24px; align-items: flex-start;">
    <div style="width: 340px; flex-shrink: 0; display: flex; flex-direction: column; gap: 16px; position: sticky; top: 16px;">
      <div class="card">
        <div style="font-weight: 700; margin-bottom: 12px;">ผู้ลงนาม (คลิกเพื่อเลือก)</div>
        <div id="signerChips"></div>
        <div class="form-hint" style="margin-top: 8px;">ผู้ลงนามที่เลือกอยู่จะมีกรอบเน้นสี คลิกบนเอกสารด้านขวาเพื่อวาง/ย้ายตำแหน่งลายเซ็นของคนนั้น</div>
      </div>
      <div id="finalizeResult"></div>
      <button type="button" id="finalizeBtn" class="btn btn-primary btn-block" style="height: 52px;">เสร็จสิ้น สร้างลิงก์ให้ผู้ลงนาม</button>
    </div>
    <div style="flex-grow: 1; min-width: 0;">
      <div class="pdf-shell" id="pdfShell" style="flex-direction: column; min-height: 500px;">
        <div style="color: var(--text-muted); font-size: 13px;">กำลังโหลดเอกสาร...</div>
      </div>
    </div>
  </div>
</div>
<script>
window.PLACE_SIGNATURES_CONFIG = {
  documentId: <?= (int) $documentId ?>,
  pdfUrl: 'stream_pdf.php?id=<?= (int) $documentId ?>',
  csrfToken: <?= json_encode(Csrf::token(), JSON_UNESCAPED_UNICODE) ?>,
  signers: <?= json_encode(array_map(fn ($s) => [
      'id' => (int) $s['id'],
      'order_no' => (int) $s['order_no'],
      'full_name' => $s['full_name'],
      'position_title' => $s['position_title'],
      'sig_page' => (int) $s['sig_page'],
      'sig_x_pct' => (float) $s['sig_x_pct'],
      'sig_y_pct' => (float) $s['sig_y_pct'],
      'sig_w_pct' => (float) $s['sig_w_pct'],
      'color' => $s['color'],
  ], $signers), JSON_UNESCAPED_UNICODE) ?>
};
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<?php
$extraScripts = ['assets/js/place_signatures.js'];
include __DIR__ . '/partials/footer.php';
?>
