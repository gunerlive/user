<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$auth->logout();
\ESign\Helpers::redirect('login.php');
