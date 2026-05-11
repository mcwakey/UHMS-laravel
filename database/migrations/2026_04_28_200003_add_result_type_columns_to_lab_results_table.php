<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function cols(): array
    {
        return array_map(fn ($r) => $r->Field, DB::select('SHOW COLUMNS FROM `lab_results`'));
    }

    public function up(): void
    {
        $existing = $this->cols();
        if (in_array('result_type', $existing)) return; // already exists

        // Ensure result_value is nullable via MODIFY — use raw SQL to avoid generation_expression issue.
        // Only needed if the column is currently NOT NULL.
        $rvRow = collect(DB::select('SHOW COLUMNS FROM `lab_results`'))->firstWhere('Field', 'result_value');
        if ($rvRow && $rvRow->Null === 'NO') {
            DB::statement('ALTER TABLE `lab_results` MODIFY COLUMN `result_value` TEXT NULL');
        }

        $adds = [];
        if (!in_array('result_type', $existing))      $adds[] = "ADD COLUMN `result_type` VARCHAR(191) NOT NULL DEFAULT 'parameters' AFTER `lab_request_id`";
        if (!in_array('result_text', $existing))      $adds[] = 'ADD COLUMN `result_text` LONGTEXT NULL AFTER `result_value`';
        if (!in_array('result_file', $existing))      $adds[] = 'ADD COLUMN `result_file` VARCHAR(191) NULL AFTER `result_text`';
        if (!in_array('result_file_name', $existing)) $adds[] = 'ADD COLUMN `result_file_name` VARCHAR(191) NULL AFTER `result_file`';
        if (!empty($adds)) DB::statement('ALTER TABLE `lab_results` ' . implode(', ', $adds));
    }

    public function down(): void
    {
        $existing = $this->cols();
        if (!in_array('result_type', $existing)) return;

        $toDrop = array_filter(['result_type','result_text','result_file','result_file_name'], fn ($c) => in_array($c, $existing));
        if (!empty($toDrop)) DB::statement('ALTER TABLE `lab_results` ' . implode(', ', array_map(fn ($c) => "DROP COLUMN `{$c}`", $toDrop)));
    }
};
