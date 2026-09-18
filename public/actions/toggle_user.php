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

$userId = (int) ($_POST['user_id'] ?? 0);
$active = (bool) ($_POST['active'] ?? false);

if ($userId === $auth->userId()) {
    Helpers::redirect('../settings.php?error=cannot_disable_self');
}

$auth->setUserActive($userId, $active);

Helpers::redirect('../settings.php?msg=user_toggled');
