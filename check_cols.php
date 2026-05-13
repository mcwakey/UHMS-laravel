<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->boot();

$cols = \Illuminate\Support\Facades\DB::select("SHOW COLUMNS FROM `invoice_items`");
foreach ($cols as $col) {
    echo $col->Field . ' - ' . $col->Type . PHP_EOL;
}
