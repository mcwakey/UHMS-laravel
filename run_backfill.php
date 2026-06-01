<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\InvoiceItem;
use App\Services\ServiceRenderingService;

$service = app(ServiceRenderingService::class);

$items = InvoiceItem::whereNotNull('service_catalog_id')
    ->whereDoesntHave('serviceRendering')
    ->with(['invoice', 'visit.emergencyCase', 'visit.admission', 'visit.activeConsultationRoute', 'patient', 'department', 'serviceCatalog.department'])
    ->cursor();

$created = 0;
$skipped = 0;

foreach ($items as $item) {
    $rendering = $service->createForInvoiceItem($item);
    if ($rendering) {
        $created++;
        echo "Created rendering for: {$item->description}" . PHP_EOL;
    } else {
        $skipped++;
    }
}

echo PHP_EOL . "Done. Created: $created, Skipped: $skipped" . PHP_EOL;
echo "Total service_renderings now: " . App\Models\ServiceRendering::count() . PHP_EOL;
