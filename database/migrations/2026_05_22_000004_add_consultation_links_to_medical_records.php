<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add consultation-route linkage to medical_records.
 *
 * A consultation produces a medical record bound to the specific
 * (department, service, consultation_route) the doctor was working in
 * at the time. This is required so a single visit (which may pass
 * through multiple consultation services via referrals) yields one
 * medical record per consultation rather than a single mixed record.
 *
 * NOTE on MariaDB 10.1.32 + Laravel 12: Blueprint::table() / foreignId()
 * fails with "Unknown column 'generation_expression'" when introspecting
 * pre-existing tables. We branch the migration: raw idempotent ALTERs
 * on MySQL/MariaDB, normal Blueprint on sqlite (used by the test suite).
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            Schema::table('medical_records', function ($table) {
                if (! Schema::hasColumn('medical_records', 'service_id')) {
                    $table->foreignId('service_id')->nullable()->after('doctor_id');
                }
                if (! Schema::hasColumn('medical_records', 'department_id')) {
                    $table->foreignId('department_id')->nullable()->after('service_id');
                }
                if (! Schema::hasColumn('medical_records', 'consultation_route_id')) {
                    $table->foreignId('consultation_route_id')->nullable()->after('department_id');
                }
            });

            return;
        }

        // MariaDB / MySQL — use raw, idempotent ALTERs guarded against the
        // generation_expression bug in Blueprint::table.
        $addColumn = function (string $column, string $definition) {
            $exists = DB::selectOne(
                "SELECT COUNT(*) AS c FROM information_schema.columns
                 WHERE table_schema = DATABASE()
                   AND table_name   = 'medical_records'
                   AND column_name  = ?",
                [$column]
            );
            if ((int) ($exists->c ?? 0) === 0) {
                DB::statement("ALTER TABLE medical_records ADD COLUMN {$column} {$definition}");
            }
        };

        $addColumn('service_id',            'BIGINT UNSIGNED NULL AFTER doctor_id');
        $addColumn('department_id',         'BIGINT UNSIGNED NULL AFTER service_id');
        $addColumn('consultation_route_id', 'BIGINT UNSIGNED NULL AFTER department_id');
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            Schema::table('medical_records', function ($table) {
                foreach (['consultation_route_id', 'department_id', 'service_id'] as $col) {
                    if (Schema::hasColumn('medical_records', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });

            return;
        }

        foreach (['consultation_route_id', 'department_id', 'service_id'] as $col) {
            $exists = DB::selectOne(
                "SELECT COUNT(*) AS c FROM information_schema.columns
                 WHERE table_schema = DATABASE()
                   AND table_name   = 'medical_records'
                   AND column_name  = ?",
                [$col]
            );
            if ((int) ($exists->c ?? 0) > 0) {
                DB::statement("ALTER TABLE medical_records DROP COLUMN {$col}");
            }
        }
    }
};
