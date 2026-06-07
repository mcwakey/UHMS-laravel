<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Allows a lab request to be raised for an external / walk-in patient who has no
 * facility visit (e.g. a counter-sale investigation). Makes the visit/patient
 * links optional and records the external party. Purely additive.
 */
return new class extends Migration
{
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
        // Loosen the facility-patient requirement (raw MODIFY keeps FKs/cascade).
        if (DB::getDriverName() !== 'sqlite') {
            foreach (['visit_id', 'patient_id'] as $column) {
                DB::statement("ALTER TABLE lab_requests MODIFY {$column} BIGINT UNSIGNED NULL");
            }
        }

        Schema::table('lab_requests', function (Blueprint $table) {
            if (! $this->columnExists('lab_requests', 'external_party_name')) {
                $table->string('external_party_name')->nullable()->after('patient_id');
            }
            if (! $this->columnExists('lab_requests', 'external_party_contact')) {
                $table->string('external_party_contact')->nullable()->after('external_party_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lab_requests', function (Blueprint $table) {
            foreach (['external_party_name', 'external_party_contact'] as $c) {
                if ($this->columnExists('lab_requests', $c)) {
                    $table->dropColumn($c);
                }
            }
        });

        // Nullability left loosened (re-tightening could fail if walk-in rows exist).
    }
};
