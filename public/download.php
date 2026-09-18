<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use ESign\Helpers;

$auth->requireLogin();

$document = $documentService->find((int) ($_GET['id'] ?? 0));
if (!$document) {
    http_response_code(404);
    die('ไม่พบเอกสารนี้');
}

if ($document['status'] === 'saved' && $document['drive_web_view_link']) {
    Helpers::redirect($document['drive_web_view_link']);
}

$path = __DIR__ . '/../storage/' . $document['working_file_path'];
if (!is_file($path)) {
    http_response_code(410);
    die('ไม่พบไฟล์เอกสารบนเซิร์ฟเวอร์');
}

$safeName = preg_replace('/[^\p{L}\p{N}\-_\. ]+/u', '_', $document['document_number'] . '_' . $document['title']);
$safeName = trim((string) $safeName) ?: 'document';

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . rawurlencode($safeName) . '.pdf"');
header('Content-Length: ' . (string) filesize($path));
readfile($path);
