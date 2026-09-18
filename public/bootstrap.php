<?php

declare(strict_types=1);

date_default_timezone_set('Asia/Bangkok');

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
]);
session_start();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

require __DIR__ . '/../vendor/autoload.php';

$configPath = __DIR__ . '/../config/config.php';
if (!is_file($configPath)) {
    http_response_code(500);
    die('ยังไม่ได้ตั้งค่าระบบ: กรุณาคัดลอกไฟล์ config/config.sample.php เป็น config/config.php แล้วกรอกข้อมูลให้ครบถ้วนก่อนใช้งาน');
}

/** @var array $config */
$config = require $configPath;

use ESign\Auth;
use ESign\Database;
use ESign\DocumentService;
use ESign\SignerService;

$db = Database::connection($config['db']);
$auth = new Auth($db);
$documentService = new DocumentService($db);
$signerService = new SignerService($db);
