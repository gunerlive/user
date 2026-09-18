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

$fullName = trim((string) ($_POST['full_name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

if ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    Helpers::redirect('../settings.php?error=invalid_input');
}

if (strlen($password) < 8) {
    Helpers::redirect('../settings.php?error=weak_password');
}

if ($auth->emailExists($email)) {
    Helpers::redirect('../settings.php?error=email_exists');
}

$auth->createUser($fullName, $email, $password);

Helpers::redirect('../settings.php?msg=user_added');
