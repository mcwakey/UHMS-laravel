<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$total = App\Models\ServiceRendering::count();
$items_without_rendering = App\Models\InvoiceItem::whereNotNull('service_catalog_id')->whereDoesntHave('serviceRendering')->count();
$items_with_rendering = App\Models\InvoiceItem::whereNotNull('service_catalog_id')->whereHas('serviceRendering')->count();
$null_service_id = App\Models\ServiceRendering::whereNull('service_id')->count();

$lines = [];
$lines[] = 'service_renderings total: ' . $total;
$lines[] = 'service_renderings with null service_id: ' . $null_service_id;
$lines[] = 'invoice_items with catalog but NO rendering: ' . $items_without_rendering;
$lines[] = 'invoice_items with catalog AND rendering: ' . $items_with_rendering;

$samples = App\Models\InvoiceItem::whereNotNull('service_catalog_id')
    ->whereDoesntHave('serviceRendering')
    ->with('serviceCatalog')
    ->limit(5)
    ->get(['id', 'service_catalog_id', 'description', 'source_type']);

foreach ($samples as $item) {
    $lines[] = "Item #{$item->id} - {$item->description} ({$item->source_type}) - catalog: " . ($item->serviceCatalog?->name ?? 'NULL');
}

file_put_contents(__DIR__ . '/cr_result.txt', implode("\n", $lines));
echo implode("\n", $lines);
