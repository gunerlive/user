<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use ESign\Csrf;
use ESign\Helpers;

$auth->requireLogin();

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    Helpers::jsonResponse(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง'], 400);
}

if (!Csrf::verify($payload['csrf_token'] ?? null)) {
    Helpers::jsonResponse(['success' => false, 'message' => 'คำขอไม่ถูกต้องหรือหมดอายุ กรุณาโหลดหน้าใหม่'], 419);
}

$documentId = (int) ($payload['document_id'] ?? 0);
$document = $documentService->find($documentId);
if (!$document) {
    Helpers::jsonResponse(['success' => false, 'message' => 'ไม่พบเอกสารนี้'], 404);
}
if ($document['status'] !== 'draft') {
    Helpers::jsonResponse(['success' => false, 'message' => 'เอกสารนี้ถูกดำเนินการไปแล้ว'], 409);
}

$signersPayload = $payload['signers'] ?? [];
if (!is_array($signersPayload) || empty($signersPayload)) {
    Helpers::jsonResponse(['success' => false, 'message' => 'ไม่มีข้อมูลผู้ลงนาม'], 400);
}

foreach ($signersPayload as $s) {
    $signerService->updatePosition(
        (int) ($s['id'] ?? 0),
        $documentId,
        max(1, (int) ($s['sig_page'] ?? 1)),
        max(0, min(100, (float) ($s['sig_x_pct'] ?? 10))),
        max(0, min(100, (float) ($s['sig_y_pct'] ?? 80))),
        max(5, min(60, (float) ($s['sig_w_pct'] ?? 22)))
    );
}

$documentService->setStatus($documentId, 'in_progress');
$documentService->logActivity($documentId, 'เริ่มกระบวนการลงนาม โดย ' . $auth->userName());

$signers = $signerService->listByDocument($documentId);
$firstSigner = $signers[0] ?? null;

$baseUrl = rtrim($config['app_base_url'], '/');
$firstLink = $firstSigner ? $baseUrl . '/sign.php?token=' . $firstSigner['token'] : null;

Helpers::jsonResponse([
    'success' => true,
    'first_signer_link' => $firstLink,
]);
