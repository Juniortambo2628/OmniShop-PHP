<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
if (file_exists(__DIR__.'/../vendor/autoload.php')) {
    require __DIR__.'/../vendor/autoload.php';
    /** @var Application $app */
    $app = require_once __DIR__.'/../bootstrap/app.php';
} else {
    // Staging path: core is outside the public folder
    require __DIR__.'/../../omnishop-core/vendor/autoload.php';
    /** @var Application $app */
    $app = require_once __DIR__.'/../../omnishop-core/bootstrap/app.php';
}

$app->handleRequest(Request::capture());
