<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add the index that failed in the original migration due to the name exceeding MySQL's 64-char limit.
        // Skipped on fresh installs where the fixed migration already creates the index with the short name.
        if (! Schema::hasTable('medication_administrations')) {
            return;
        }

        $exists = DB::selectOne(
            "SELECT COUNT(*) AS cnt FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'medication_administrations'
               AND INDEX_NAME = 'ma_emergency_administered_at_idx'"
        );

        if (! $exists || $exists->cnt == 0) {
            Schema::table('medication_administrations', function (Blueprint $table) {
                $table->index(['emergency_case_id', 'administered_at'], 'ma_emergency_administered_at_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('medication_administrations')) {
            Schema::table('medication_administrations', function (Blueprint $table) {
                $table->dropIndex('ma_emergency_administered_at_idx');
            });
        }
    }
};
