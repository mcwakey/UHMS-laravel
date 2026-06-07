<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Allows a standalone cash invoice to be raised for an external (non-facility)
 * blood recipient who has no visit/patient. Makes the invoice/payment patient &
 * visit links optional and records the external party + originating blood
 * request on the invoice. Purely additive — existing invoices are unaffected.
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
            foreach ([
                'invoices' => ['visit_id', 'patient_id'],
                'invoice_items' => ['visit_id', 'patient_id'],
                'payments' => ['patient_id'],
            ] as $table => $columns) {
                foreach ($columns as $column) {
                    DB::statement("ALTER TABLE {$table} MODIFY {$column} BIGINT UNSIGNED NULL");
                }
            }
        }

        Schema::table('invoices', function (Blueprint $table) {
            if (! $this->columnExists('invoices', 'external_party_name')) {
                $table->string('external_party_name')->nullable()->after('patient_id');
            }
            if (! $this->columnExists('invoices', 'blood_request_id')) {
                $table->foreignId('blood_request_id')->nullable()->after('external_party_name')
                    ->constrained('blood_requests')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if ($this->columnExists('invoices', 'blood_request_id')) {
                $table->dropConstrainedForeignId('blood_request_id');
            }
            if ($this->columnExists('invoices', 'external_party_name')) {
                $table->dropColumn('external_party_name');
            }
        });

        // Column nullability left loosened: re-tightening could fail if external
        // (null-patient) invoices/payments already exist.
    }
};
