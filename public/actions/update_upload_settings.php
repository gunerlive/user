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

$maxSize = (int) ($_POST['upload_max_file_size_mb'] ?? 0);
if ($maxSize < 1 || $maxSize > 200) {
    Helpers::redirect('../settings.php?error=invalid_input');
}

$settingsService->set('upload_max_file_size_mb', (string) $maxSize);

Helpers::redirect('../settings.php?msg=upload_saved');
