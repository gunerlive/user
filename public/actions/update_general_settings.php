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

$systemName = trim((string) ($_POST['system_name'] ?? ''));
$appBaseUrl = rtrim(trim((string) ($_POST['app_base_url'] ?? '')), '/');

if ($systemName === '' || !filter_var($appBaseUrl, FILTER_VALIDATE_URL)) {
    Helpers::redirect('../settings.php?error=invalid_input');
}

$settingsService->setMany([
    'system_name'  => $systemName,
    'app_base_url' => $appBaseUrl,
]);

Helpers::redirect('../settings.php?msg=general_saved');
