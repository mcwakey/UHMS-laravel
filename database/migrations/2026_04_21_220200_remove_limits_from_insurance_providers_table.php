<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function cols(string $table): array
    {
        return array_map(fn ($r) => $r->Field, DB::select("SHOW COLUMNS FROM `{$table}`"));
    }

    public function up(): void
    {
        $existing = $this->cols('insurance_providers');
        $toDrop = array_filter(
            ['coverage_percentage','per_visit_limit','annual_limit','max_per_month','max_visits_per_month','tier'],
            fn ($c) => in_array($c, $existing)
        );
        if (!empty($toDrop)) {
            $drops = implode(', ', array_map(fn ($c) => "DROP COLUMN `{$c}`", $toDrop));
            DB::statement("ALTER TABLE `insurance_providers` {$drops}");
        }
    }

    public function down(): void
    {
        $existing = $this->cols('insurance_providers');
        $adds = [];
        if (!in_array('coverage_percentage', $existing)) {
            $adds[] = 'ADD COLUMN `coverage_percentage` DECIMAL(5,2) NOT NULL DEFAULT 100';
        }
        if (!in_array('per_visit_limit', $existing)) {
            $adds[] = 'ADD COLUMN `per_visit_limit` DECIMAL(12,2) NULL';
        }
        if (!in_array('annual_limit', $existing)) {
            $adds[] = 'ADD COLUMN `annual_limit` DECIMAL(12,2) NULL';
        }
        if (!in_array('max_per_month', $existing)) {
            $adds[] = 'ADD COLUMN `max_per_month` DECIMAL(12,2) NULL';
        }
        if (!in_array('max_visits_per_month', $existing)) {
            $adds[] = 'ADD COLUMN `max_visits_per_month` SMALLINT UNSIGNED NULL';
        }
        if (!in_array('tier', $existing)) {
            $adds[] = 'ADD COLUMN `tier` VARCHAR(191) NULL';
        }
        if (!empty($adds)) {
            DB::statement('ALTER TABLE `insurance_providers` ' . implode(', ', $adds));
        }
    }
};
