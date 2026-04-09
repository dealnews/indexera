<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Ensure $_SESSION is available without a real HTTP session.
$_SESSION = [];
