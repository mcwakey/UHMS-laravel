<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\InvoiceItem;

$items = InvoiceItem::whereNotNull('service_catalog_id')
    ->whereDoesntHave('serviceRendering')
    ->with(['serviceCatalog.department'])
    ->get(['id', 'service_catalog_id', 'description', 'source_type']);

$excludedSourceTypes = [
    'consultation_service', 'investigation_service', 'procedure_service',
    'pharmacy_product', 'pharmacy_billing_selection', 'ward_consumable',
    'emergency_consumable', 'emergency_bed_charge', 'emergency_daily_consumable_charge',
    'admission_fee', 'admission_bed_charge', 'admission_daily_consumable_charge',
    'investigation_consumable', 'procedure_consumable',
];
$excludedCategories = [
    'consultation', 'investigation', 'lab', 'laboratory', 'radiology', 'xray', 'x-ray', 'scan',
    'pharmacy', 'drug', 'drugs', 'medication', 'product', 'procedure', 'procedures',
    'theatre', 'surgery', 'bed_charge', 'admission_fee', 'registration', 'administrative',
    'consumable', 'consumables',
];
$excludedDeptTypes = ['consultation', 'investigation', 'procedure', 'pharmacy', 'radiology', 'administrative'];

$reasons = [];
foreach ($items as $item) {
    $service = $item->serviceCatalog;
    if (!$service) { $reasons['no_catalog'][] = $item->description; continue; }

    $sourceType = strtolower((string)$item->source_type);
    if (in_array($item->source_type, $excludedSourceTypes, true)) {
        $reasons['excluded_source_type:'.$item->source_type][] = $item->description;
        continue;
    }
    foreach (['consultation','investigation','lab','radiology','pharmacy','drug','product','procedure','theatre','surgery','consumable','bed_charge'] as $n) {
        if ($sourceType !== '' && str_contains($sourceType, $n)) {
            $reasons['source_type_needle:'.$n][] = $item->description;
            continue 2;
        }
    }

    $category = strtolower((string)$service->category);
    if (in_array($category, $excludedCategories, true)) {
        $reasons['excluded_category:'.$category][] = $item->description;
        continue;
    }

    $deptType = strtolower((string)($service->department_type?->value ?? $service->department?->type ?? ''));
    if (in_array($deptType, $excludedDeptTypes, true)) {
        $reasons['excluded_dept_type:'.$deptType][] = $item->description;
        continue;
    }

    $reasons['SHOULD_TRACK'][] = $item->description . ' | cat:' . $service->category;
}

foreach ($reasons as $reason => $descs) {
    echo "\n[$reason] (" . count($descs) . ")\n";
    foreach (array_unique($descs) as $d) {
        echo "  - $d\n";
    }
}
