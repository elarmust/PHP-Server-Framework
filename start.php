<?php

/**
 * copyright @ WereWolf Labs OÜ.
 */

declare(strict_types = 1);

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('UTC');
define('BASE_PATH', dirname(__FILE__));

if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 80200) {
    echo 'Framework supports PHP 8.2 or newer!';
    die();
}

spl_autoload_register(function($class) {
    $namespace = str_replace('\\', '/', $class) . '.php';

    $paths = ['src', 'modules', 'vendor'];

    foreach ($paths as $path) {
        if (file_exists(BASE_PATH . '/' . $path . '/' . $namespace)) {
            require_once BASE_PATH . '/' . $path . '/' . $namespace;
        }
    }
});

require_once BASE_PATH . '/vendor/autoload.php';

use Framework\FrameworkServer;
$server = new FrameworkServer();
