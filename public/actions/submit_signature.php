<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use ESign\Csrf;
use ESign\Helpers;
use ESign\PdfStamper;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Helpers::redirect('../sign.php');
}

Csrf::verifyOrFail($_POST['csrf_token'] ?? null);

$token = (string) ($_POST['token'] ?? '');
$signer = $token !== '' ? $signerService->findByToken($token) : null;

if (!$signer) {
    http_response_code(404);
    die('ไม่พบลิงก์นี้');
}

// ป้องกันการส่งซ้ำ (กดย้อนกลับแล้วส่งฟอร์มอีกครั้ง) หรือเซ็นนอกลำดับคิว
if ($signer['status'] === 'signed'
    || $signer['document_status'] !== 'in_progress'
    || !$signerService->isSignerTurn($signer)
) {
    Helpers::redirect('../sign.php?token=' . urlencode($token));
}

$signatureData = (string) ($_POST['signature_data'] ?? '');
if ($signatureData === '') {
    Helpers::redirect('../sign.php?token=' . urlencode($token));
}

$documentId = (int) $signer['document_id'];
$workingPath = __DIR__ . '/../../storage/' . $signer['working_file_path'];
$tmpDir = __DIR__ . '/../../storage/tmp';

try {
    $composedImagePath = PdfStamper::composeSignatureImage(
        $signatureData,
        $signer['full_name'],
        Helpers::thaiDate(date('Y-m-d H:i:s')),
        $config['thai_font_path'] ?? null,
        $tmpDir
    );

    PdfStamper::stampSignature(
        $workingPath,
        (int) $signer['sig_page'],
        (float) $signer['sig_x_pct'],
        (float) $signer['sig_y_pct'],
        (float) $signer['sig_w_pct'],
        $composedImagePath,
        $tmpDir
    );

    @unlink($composedImagePath);
} catch (\Throwable $e) {
    Helpers::redirect('../sign.php?token=' . urlencode($token) . '&error=stamp_failed');
}

$signerService->markSigned((int) $signer['id'], Helpers::clientIp());
$documentService->logActivity($documentId, $signer['full_name'] . ' (' . $signer['position_title'] . ') ได้ลงนามเอกสารแล้ว');

if ($signerService->allSigned($documentId)) {
    $documentService->setStatus($documentId, 'ready_for_review');
    $documentService->logActivity($documentId, 'ลงนามครบทุกคนแล้ว รอเจ้าหน้าที่ตรวจสอบและบันทึกเข้าระบบ');
}

Helpers::redirect('../sign.php?token=' . urlencode($token));
