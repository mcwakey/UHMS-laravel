<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Configurable "overall result type" for the investigation module.
 *
 * - service_catalog gains the per-investigation configuration (what kind of
 *   overall conclusion this test produces: free_text / numeric / boolean /
 *   positive_negative) plus optional unit, normal range and custom labels.
 * - lab_results gains typed, canonical storage columns so the overall result
 *   can be reported/tallied. The legacy result_value/result_text columns are
 *   left untouched for full backward compatibility (old free-text results still
 *   display through the existing accessors).
 *
 * Column existence is guarded with a lean information_schema query on MySQL /
 * MariaDB because Laravel's native introspection asks older MariaDB versions
 * for generation_expression. SQLite keeps using the native column listing.
 */
return new class extends Migration
{
    public function up(): void
    {
        $catalogueCols = $this->columnListing('service_catalog');

        Schema::table('service_catalog', function (Blueprint $table) use ($catalogueCols) {
            if (! in_array('overall_result_type', $catalogueCols, true)) {
                $table->string('overall_result_type', 191)->default('free_text')->after('category');
            }
            if (! in_array('overall_result_unit', $catalogueCols, true)) {
                $table->string('overall_result_unit', 50)->nullable()->after('overall_result_type');
            }
            if (! in_array('overall_result_min_value', $catalogueCols, true)) {
                $table->decimal('overall_result_min_value', 14, 4)->nullable()->after('overall_result_unit');
            }
            if (! in_array('overall_result_max_value', $catalogueCols, true)) {
                $table->decimal('overall_result_max_value', 14, 4)->nullable()->after('overall_result_min_value');
            }
            if (! in_array('overall_result_positive_label', $catalogueCols, true)) {
                $table->string('overall_result_positive_label', 100)->nullable()->after('overall_result_max_value');
            }
            if (! in_array('overall_result_negative_label', $catalogueCols, true)) {
                $table->string('overall_result_negative_label', 100)->nullable()->after('overall_result_positive_label');
            }
            if (! in_array('overall_result_true_label', $catalogueCols, true)) {
                $table->string('overall_result_true_label', 100)->nullable()->after('overall_result_negative_label');
            }
            if (! in_array('overall_result_false_label', $catalogueCols, true)) {
                $table->string('overall_result_false_label', 100)->nullable()->after('overall_result_true_label');
            }
        });

        $resultCols = $this->columnListing('lab_results');

        Schema::table('lab_results', function (Blueprint $table) use ($resultCols) {
            // Snapshot of the configured type at entry time (null for legacy rows).
            if (! in_array('overall_result_type', $resultCols, true)) {
                $table->string('overall_result_type', 191)->nullable()->after('result_file_name');
            }
            if (! in_array('overall_result_text', $resultCols, true)) {
                $table->text('overall_result_text')->nullable()->after('overall_result_type');
            }
            if (! in_array('overall_result_numeric', $resultCols, true)) {
                $table->decimal('overall_result_numeric', 16, 4)->nullable()->after('overall_result_text');
            }
            if (! in_array('overall_result_boolean', $resultCols, true)) {
                $table->boolean('overall_result_boolean')->nullable()->after('overall_result_numeric');
            }
            // Canonical positive/negative outcome.
            if (! in_array('overall_result_outcome', $resultCols, true)) {
                $table->string('overall_result_outcome', 30)->nullable()->after('overall_result_boolean');
            }
            if (! in_array('overall_result_unit', $resultCols, true)) {
                $table->string('overall_result_unit', 50)->nullable()->after('overall_result_outcome');
            }
        });
    }

    public function down(): void
    {
        $catalogueCols = [
            'overall_result_type', 'overall_result_unit', 'overall_result_min_value',
            'overall_result_max_value', 'overall_result_positive_label',
            'overall_result_negative_label', 'overall_result_true_label',
            'overall_result_false_label',
        ];
        $existingCatalogue = $this->columnListing('service_catalog');
        Schema::table('service_catalog', function (Blueprint $table) use ($catalogueCols, $existingCatalogue) {
            foreach ($catalogueCols as $col) {
                if (in_array($col, $existingCatalogue, true)) {
                    $table->dropColumn($col);
                }
            }
        });

        $resultCols = [
            'overall_result_type', 'overall_result_text', 'overall_result_numeric',
            'overall_result_boolean', 'overall_result_outcome', 'overall_result_unit',
        ];
        $existingResults = $this->columnListing('lab_results');
        Schema::table('lab_results', function (Blueprint $table) use ($resultCols, $existingResults) {
            foreach ($resultCols as $col) {
                if (in_array($col, $existingResults, true)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    private function columnListing(string $table): array
    {
        if (DB::getDriverName() === 'sqlite') {
            return Schema::getColumnListing($table);
        }

        return collect(DB::select(
            'SELECT COLUMN_NAME AS column_name
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$table]
        ))->pluck('column_name')->all();
    }
};
