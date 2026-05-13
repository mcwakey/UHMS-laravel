<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

try {
    $app->make(App\Services\VisitService::class);
    echo 'VisitService: OK' . PHP_EOL;
} catch (Throwable $e) {
    echo 'VisitService ERROR: ' . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}

try {
    $app->make(App\Services\BillingService::class);
    echo 'BillingService: OK' . PHP_EOL;
} catch (Throwable $e) {
    echo 'BillingService ERROR: ' . $e->getMessage() . PHP_EOL;
}
