<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use ESign\Helpers;

$auth->requireLogin();

$statusFilter = $_GET['status'] ?? 'all';
$documents = $documentService->listAll($statusFilter);
$counts = $documentService->counts();

$tabs = [
    'all'              => 'ทั้งหมด',
    'draft'            => 'ร่าง',
    'in_progress'      => 'กำลังลงนาม',
    'ready_for_review' => 'รอตรวจสอบ',
    'saved'            => 'บันทึกแล้ว',
];

$pageTitle = 'รายการหนังสือ';
include __DIR__ . '/partials/header.php';
?>
<div class="container-wide">
  <div class="page-header">
    <div>
      <div class="page-title">รายการหนังสือ</div>
      <div class="page-subtitle">ติดตามสถานะการลงนามและบันทึกเข้า Google Drive</div>
    </div>
    <a href="new_document.php" class="btn btn-primary">+ ออกหนังสือใหม่</a>
  </div>

  <div class="tabs" style="margin-bottom: 18px;">
    <?php foreach ($tabs as $key => $label): ?>
      <a class="tab <?= $statusFilter === $key ? 'active' : '' ?>" href="dashboard.php?status=<?= urlencode($key) ?>">
        <?= Helpers::e($label) ?> (<?= (int) ($counts[$key] ?? 0) ?>)
      </a>
    <?php endforeach; ?>
  </div>

  <div class="card" style="padding: 0; overflow: hidden;">
    <?php if (empty($documents)): ?>
      <div class="empty-state">
        <p>ยังไม่มีหนังสือในหมวดนี้</p>
        <a href="new_document.php" class="btn btn-primary" style="margin-top: 10px;">+ ออกหนังสือใหม่</a>
      </div>
    <?php else: ?>
      <table class="doc-table">
        <thead>
          <tr>
            <th>เลขที่</th>
            <th>ชื่อเรื่อง</th>
            <th>ความคืบหน้าการเซ็น</th>
            <th>สถานะ</th>
            <th>วันที่สร้าง</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($documents as $doc): ?>
            <?php
              $signers = $signerService->listByDocument((int) $doc['id']);
              $total = count($signers);
              $signedCount = count(array_filter($signers, fn ($s) => $s['status'] === 'signed'));
              $pct = $total > 0 ? round($signedCount / $total * 100) : 0;
              [$label, $color, $bg] = Helpers::statusLabel($doc['status']);
              $barColor = $pct >= 100 ? '#10b981' : '#f59e0b';
            ?>
            <tr>
              <td><?= Helpers::e($doc['document_number']) ?></td>
              <td style="font-weight: 600; max-width: 360px; white-space: normal;"><?= Helpers::e($doc['title']) ?></td>
              <td style="min-width: 180px;">
                <div class="progress-track"><div class="progress-fill" style="width: <?= $pct ?>%; background: <?= $barColor ?>;"></div></div>
                <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;"><?= $signedCount ?> / <?= $total ?> คนเซ็นแล้ว</div>
              </td>
              <td><span class="badge" style="color: <?= $color ?>; background: <?= $bg ?>;"><?= Helpers::e($label) ?></span></td>
              <td style="color: var(--text-muted); font-size: 13px;"><?= date('d/m/Y', strtotime($doc['created_at'])) ?></td>
              <td><a href="document.php?id=<?= (int) $doc['id'] ?>" style="font-weight: 700;">ดู &gt;</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
