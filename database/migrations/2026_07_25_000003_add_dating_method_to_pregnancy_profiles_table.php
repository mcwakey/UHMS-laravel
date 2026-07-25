<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 14R.3 — `dating_method` becomes Pregnancy-Profile-owned (decision R2).
 *
 * Additive and nullable: existing pregnancy profiles stay valid and are NOT
 * backfilled. Nothing about current LMP/EDD calculation changes; this only
 * records which method was used.
 *
 * Follows the project's MariaDB-safe pattern (raw idempotent ALTER on
 * MySQL/MariaDB, Blueprint on sqlite for the test suite).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('pregnancy_profiles', 'dating_method')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('pregnancy_profiles', function (Blueprint $table) {
                $table->string('dating_method')->nullable()->after('gestational_age_days');
            });

            return;
        }

        DB::statement(
            'ALTER TABLE pregnancy_profiles ADD COLUMN dating_method VARCHAR(255) NULL AFTER gestational_age_days'
        );
    }

    public function down(): void
    {
        if (! Schema::hasColumn('pregnancy_profiles', 'dating_method')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('pregnancy_profiles', function (Blueprint $table) {
                $table->dropColumn('dating_method');
            });

            return;
        }

        DB::statement('ALTER TABLE pregnancy_profiles DROP COLUMN dating_method');
    }
};
