<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use ESign\Csrf;
use ESign\GoogleApiClient;
use ESign\Helpers;

$auth->requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Helpers::redirect('../dashboard.php');
}

Csrf::verifyOrFail($_POST['csrf_token'] ?? null);

$documentId = (int) ($_POST['document_id'] ?? 0);
$document = $documentService->find($documentId);

if (!$document) {
    http_response_code(404);
    die('ไม่พบเอกสารนี้');
}

if ($document['status'] !== 'ready_for_review') {
    Helpers::redirect('../document.php?id=' . $documentId . '&save_error=' . urlencode('เอกสารนี้ยังลงนามไม่ครบ หรือถูกบันทึกไปแล้ว'));
}

$path = __DIR__ . '/../../storage/' . $document['working_file_path'];
if (!is_file($path)) {
    Helpers::redirect('../document.php?id=' . $documentId . '&save_error=' . urlencode('ไม่พบไฟล์เอกสารบนเซิร์ฟเวอร์'));
}

$description = trim($document['document_number'] . ' - ' . $document['title'] . ($document['description'] ? ' | ' . $document['description'] : ''));

try {
    $client = new GoogleApiClient($config['google_api']);
    $result = $client->uploadFile($path, $description, $auth->userName());
} catch (\Throwable $e) {
    Helpers::redirect('../document.php?id=' . $documentId . '&save_error=' . urlencode($e->getMessage()));
}

$documentService->markSaved($documentId, $result);
@unlink($path);
$documentService->logActivity($documentId, 'บันทึกเข้า Google Drive โดย ' . $auth->userName() . ' และลบไฟล์ชั่วคราวออกจากเซิร์ฟเวอร์แล้ว');

Helpers::redirect('../document.php?id=' . $documentId);
