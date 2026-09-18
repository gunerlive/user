<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use ESign\Csrf;
use ESign\Helpers;
use ESign\PdfStamper;

$auth->requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Helpers::redirect('../new_document.php');
}

Csrf::verifyOrFail($_POST['csrf_token'] ?? null);

$maxBytes = (int) ($config['upload']['max_file_size_mb'] ?? 20) * 1024 * 1024;

if (empty($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] === UPLOAD_ERR_NO_FILE) {
    Helpers::redirect('../new_document.php?error=no_file');
}

if ($_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
    Helpers::redirect('../new_document.php?error=upload_failed');
}

$originalName = (string) $_FILES['pdf_file']['name'];
$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
$allowedExt = $config['upload']['allowed_extensions'] ?? ['pdf'];
if (!in_array($ext, $allowedExt, true)) {
    Helpers::redirect('../new_document.php?error=bad_ext');
}

if ($_FILES['pdf_file']['size'] > $maxBytes) {
    Helpers::redirect('../new_document.php?error=too_big');
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $_FILES['pdf_file']['tmp_name']);
finfo_close($finfo);
if ($mime !== 'application/pdf') {
    Helpers::redirect('../new_document.php?error=bad_ext');
}

$rawSigners = $_POST['signers'] ?? [];
$signers = [];
foreach ($rawSigners as $row) {
    $name = trim((string) ($row['full_name'] ?? ''));
    $position = trim((string) ($row['position_title'] ?? ''));
    if ($name === '' && $position === '') {
        continue;
    }
    $signers[] = [
        'full_name'      => $name !== '' ? $name : 'ไม่ระบุชื่อ',
        'position_title' => $position !== '' ? $position : 'ไม่ระบุตำแหน่ง',
        'sig_page'       => 1,
        'sig_x_pct'      => 10.0,
        'sig_y_pct'      => 82.0,
        'sig_w_pct'      => 22.0,
    ];
}

if (empty($signers)) {
    Helpers::redirect('../new_document.php?error=no_signers');
}

$workingDir = __DIR__ . '/../../storage/working';
$storedFilename = uniqid('doc_', true) . '.pdf';
$storedPath = $workingDir . '/' . $storedFilename;

if (!move_uploaded_file($_FILES['pdf_file']['tmp_name'], $storedPath)) {
    Helpers::redirect('../new_document.php?error=upload_failed');
}

try {
    $pageCount = PdfStamper::pageCount($storedPath);
} catch (\Throwable $e) {
    unlink($storedPath);
    Helpers::redirect('../new_document.php?error=upload_failed');
}

$documentId = $documentService->create([
    'document_number'   => trim((string) ($_POST['document_number'] ?? '')),
    'title'             => trim((string) ($_POST['title'] ?? '')) ?: 'ไม่มีชื่อเรื่อง',
    'description'       => trim((string) ($_POST['description'] ?? '')) ?: null,
    'original_filename' => $originalName,
    'working_file_path' => 'working/' . $storedFilename,
    'page_count'        => $pageCount,
], $auth->userId());

// กระจายตำแหน่งเริ่มต้นของแต่ละคนไม่ให้ซ้อนทับกันเป๊ะ ก่อนที่แอดมินจะปรับในขั้นตอนถัดไป
foreach ($signers as $i => &$signer) {
    $signer['sig_y_pct'] = min(90, 60 + $i * 10);
}
unset($signer);

$signerService->replaceSigners($documentId, $signers);
$documentService->logActivity($documentId, 'สร้างเอกสารและเพิ่มผู้ลงนาม ' . count($signers) . ' คน โดย ' . $auth->userName());

Helpers::redirect('../place_signatures.php?id=' . $documentId);
