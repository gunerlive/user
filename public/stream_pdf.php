<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$document = null;

if (isset($_GET['id'])) {
    $auth->requireLogin();
    $document = $documentService->find((int) $_GET['id']);
} elseif (isset($_GET['token'])) {
    $signer = $signerService->findByToken((string) $_GET['token']);
    if ($signer) {
        $document = $documentService->find((int) $signer['document_id']);
    }
} else {
    http_response_code(400);
    die('ไม่ระบุเอกสาร');
}

if (!$document) {
    http_response_code(404);
    die('ไม่พบเอกสารนี้');
}

$path = __DIR__ . '/../storage/' . $document['working_file_path'];

if (!is_file($path)) {
    http_response_code(410);
    die('ไม่พบไฟล์เอกสารบนเซิร์ฟเวอร์ (เอกสารอาจถูกบันทึกเข้า Google Drive และลบไฟล์ชั่วคราวออกแล้ว)');
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . rawurlencode($document['original_filename'] ?: 'document.pdf') . '"');
header('Content-Length: ' . (string) filesize($path));
header('Cache-Control: private, max-age=0, must-revalidate');
readfile($path);
