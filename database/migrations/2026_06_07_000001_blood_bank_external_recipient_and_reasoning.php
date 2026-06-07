<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Supports blood requests for recipients who are NOT registered facility
 * patients / are not in attendance (referrals, walk-ins, transfers): the
 * visit/patient links become nullable and structured external-recipient
 * identity is captured on the recipient record. Also persists the
 * compatibility reasoning produced by BloodBankCompatibilityService so the
 * crossmatch decision ("why compatible / incompatible") is visible and
 * reportable. Purely additive — existing rows are preserved.
 */
return new class extends Migration
{
    /**
     * Driver-aware column check. Laravel's Schema::hasColumn() queries
     * information_schema.generation_expression, which MariaDB 10.1 lacks, so we
     * use a lean information_schema query there and the native helper on sqlite.
     */
    private function columnExists(string $table, string $column): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return Schema::hasColumn($table, $column);
        }

        return ! empty(DB::select(
            'select column_name from information_schema.columns where table_schema = database() and table_name = ? and column_name = ?',
            [$table, $column]
        ));
    }

    public function up(): void
    {
        // ── Relax the facility-patient requirement so external recipients work ──
        // Raw MODIFY keeps the existing foreign keys (and their cascade rules)
        // intact while only loosening nullability — the safest path on MariaDB.
        if (DB::getDriverName() !== 'sqlite') {
            foreach ([
                'blood_requests' => ['visit_id', 'patient_id'],
                'blood_recipients' => ['patient_id'],
                'blood_crossmatches' => ['visit_id', 'patient_id'],
                'blood_issues' => ['visit_id', 'patient_id'],
            ] as $table => $columns) {
                foreach ($columns as $column) {
                    DB::statement("ALTER TABLE {$table} MODIFY {$column} BIGINT UNSIGNED NULL");
                }
            }
        }

        // ── Recipient type marker on the request (quick filter/badge) ──
        Schema::table('blood_requests', function (Blueprint $table) {
            if (! $this->columnExists('blood_requests', 'recipient_type')) {
                $table->string('recipient_type')->default('PATIENT')->after('patient_id');
            }
        });

        // ── External (non-facility) recipient identity on the recipient record ──
        Schema::table('blood_recipients', function (Blueprint $table) {
            $columns = [
                'recipient_type' => fn () => $table->string('recipient_type')->default('PATIENT')->after('patient_id'),
                'external_name' => fn () => $table->string('external_name')->nullable(),
                'external_sex' => fn () => $table->string('external_sex')->nullable(),
                'external_age' => fn () => $table->unsignedSmallInteger('external_age')->nullable(),
                'external_facility' => fn () => $table->string('external_facility')->nullable(),
                'external_contact' => fn () => $table->string('external_contact')->nullable(),
                'external_reference' => fn () => $table->string('external_reference')->nullable(),
            ];
            foreach ($columns as $name => $add) {
                if (! $this->columnExists('blood_recipients', $name)) {
                    $add();
                }
            }
        });

        // ── Persisted compatibility reasoning for crossmatch transparency ──
        Schema::table('blood_crossmatches', function (Blueprint $table) {
            if (! $this->columnExists('blood_crossmatches', 'compatibility_reason')) {
                $table->text('compatibility_reason')->nullable()->after('compatibility_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('blood_requests', function (Blueprint $table) {
            if ($this->columnExists('blood_requests', 'recipient_type')) {
                $table->dropColumn('recipient_type');
            }
        });

        Schema::table('blood_recipients', function (Blueprint $table) {
            foreach ([
                'recipient_type', 'external_name', 'external_sex', 'external_age',
                'external_facility', 'external_contact', 'external_reference',
            ] as $c) {
                if ($this->columnExists('blood_recipients', $c)) {
                    $table->dropColumn($c);
                }
            }
        });

        Schema::table('blood_crossmatches', function (Blueprint $table) {
            if ($this->columnExists('blood_crossmatches', 'compatibility_reason')) {
                $table->dropColumn('compatibility_reason');
            }
        });

        // Column nullability is intentionally left loosened: re-tightening could
        // fail if external-recipient rows (null visit/patient) already exist.
    }
};
