<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$updated = App\Models\ServiceCatalog::where('name', 'like', '%Medical Certificate%')
    ->update(['requires_rendering_tracking' => true]);

echo "Updated $updated service(s) to requires_rendering_tracking=true" . PHP_EOL;

$service = App\Models\ServiceCatalog::where('name', 'like', '%Medical Certificate%')->first();
if ($service) {
    echo "Medical Certificate: id={$service->id}, dept_type={$service->department_type?->value}, requires_rendering_tracking=" . var_export($service->requires_rendering_tracking, true) . PHP_EOL;
}
