<?php
// Quick validation: check if migration added the expected columns
// Run with: D:\xampp3\php\php.exe check_migrate.php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

function colExists(string $table, string $col): bool {
    $r = DB::selectOne(
        'SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name=?',
        [$table, $col]
    );
    return (int)($r->c ?? 0) > 0;
}

// Check migration was recorded
$ran = DB::table('migrations')
    ->where('migration', '2026_05_25_100000_add_insurance_type_claim_workflows')
    ->exists();

echo "Migration in migrations table: " . ($ran ? "YES\n" : "NO\n");

// Check key columns
$checks = [
    ['insurance_providers', 'insurance_type_id'],
    ['insurance_providers', 'code'],
    ['claims', 'claim_type_code'],
    ['claims', 'insurance_type_id'],
    ['claims', 'total_claim_amount'],
    ['claim_items', 'claim_amount'],
    ['claim_items', 'invoice_item_id'],
];

foreach ($checks as [$tbl, $col]) {
    echo "{$tbl}.{$col}: " . (colExists($tbl, $col) ? "OK\n" : "MISSING\n");
}

// Check insurance_types table exists
$hasTable = DB::selectOne(
    'SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?',
    ['insurance_types']
);
echo "insurance_types table: " . ((int)($hasTable->c??0) > 0 ? "EXISTS\n" : "MISSING\n");
