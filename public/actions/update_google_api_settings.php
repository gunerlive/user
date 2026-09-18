<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use ESign\Csrf;
use ESign\Helpers;

$auth->requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Helpers::redirect('../settings.php');
}

Csrf::verifyOrFail($_POST['csrf_token'] ?? null);

$systemKey = trim((string) ($_POST['google_api_system_key'] ?? ''));
$apiSecret = trim((string) ($_POST['google_api_api_secret'] ?? ''));
$uploaderName = trim((string) ($_POST['google_api_uploader_name'] ?? ''));

// เว้นว่างไว้ = ไม่เปลี่ยนค่าเดิม (ป้องกันการเผลอลบรหัสลับโดยไม่ตั้งใจ)
$toSave = [];
if ($systemKey !== '') {
    $toSave['google_api_system_key'] = $systemKey;
}
if ($apiSecret !== '') {
    $toSave['google_api_api_secret'] = $apiSecret;
}
$toSave['google_api_uploader_name'] = $uploaderName;

$settingsService->setMany($toSave);

Helpers::redirect('../settings.php?msg=google_saved');
