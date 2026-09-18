<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use ESign\Auth;

if (!Auth::hasAnyUser($db)) {
    \ESign\Helpers::redirect('setup.php');
}

if ($auth->check()) {
    \ESign\Helpers::redirect('dashboard.php');
}

\ESign\Helpers::redirect('login.php');
