<?php

/**
 * OmniShop Emergency Cache Clear
 * Visit this file directly to break a "Route Cache Catch-22"
 */

define('LARAVEL_START', microtime(true));

// 1. Load Autoloader & Bootstrap
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

// 2. Handle Request through Kernel to bootstrap the environment
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

try {
    echo "<h1>OmniShop Recovery Mode</h1>";
    
    echo "Clearing Route Cache... ";
    $kernel->call('route:clear');
    echo "Done.<br>";

    echo "Clearing Config Cache... ";
    $kernel->call('config:clear');
    echo "Done.<br>";

    echo "Clearing Application Cache... ";
    $kernel->call('cache:clear');
    echo "Done.<br>";

    echo "Optimizing... ";
    $kernel->call('optimize:clear');
    echo "Done.<br>";

    echo "<hr>";
    echo "<p style='color: green; font-weight: bold;'>Success! All caches have been purged.</p>";
    echo "<p>You can now visit the <a href='/api/maintenance/run?key=OMNI_RECOVERY_2026&action=migrate'>Maintenance Route</a> to run your migrations.</p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
