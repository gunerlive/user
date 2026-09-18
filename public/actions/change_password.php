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

$currentPassword = (string) ($_POST['current_password'] ?? '');
$newPassword = (string) ($_POST['new_password'] ?? '');
$newPasswordConfirm = (string) ($_POST['new_password_confirm'] ?? '');

if (strlen($newPassword) < 8) {
    Helpers::redirect('../settings.php?error=weak_password');
}

if ($newPassword !== $newPasswordConfirm) {
    Helpers::redirect('../settings.php?error=password_mismatch');
}

if (!$auth->changePassword((int) $auth->userId(), $currentPassword, $newPassword)) {
    Helpers::redirect('../settings.php?error=wrong_password');
}

Helpers::redirect('../settings.php?msg=password_changed');
