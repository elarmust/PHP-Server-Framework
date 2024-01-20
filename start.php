<?php

/**
 * Copyright @ Elar Must.
 */

declare(strict_types=1);

error_reporting(E_ALL);
define('BASE_PATH', dirname(__FILE__));

if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 80200) {
    echo 'Framework supports PHP 8.2 or newer!';
    die();
}

// Use composer autoloader
require_once BASE_PATH . '/vendor/autoload.php';

use Framework\Framework;

new Framework();
