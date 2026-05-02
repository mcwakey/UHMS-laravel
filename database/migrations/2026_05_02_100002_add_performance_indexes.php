<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Performance indexes — Phase 1 stabilization.
 *
 * Adds composite/single-column indexes on the hottest query paths identified
 * in `UHMS_Optimum_Solutions_Implementation_Plan.md` (Section 15).
 *
 * Uses raw SHOW INDEX checks because this MySQL version chokes on the
 * information_schema.columns.generation_expression column that
 * Laravel's Schema::hasIndex tries to read.
 */
return new class extends Migration
{
    /**
     * @var array<int, array{0:string, 1:string, 2:array<int,string>}> [table, index_name, columns]
     */
    private array $indexes = [
        // visits — hottest table
        ['visits', 'idx_visits_patient_id',           ['patient_id']],
        ['visits', 'idx_visits_status',               ['status']],
        ['visits', 'idx_visits_current_dept',         ['current_department_id']],
        ['visits', 'idx_visits_created_at',           ['created_at']],
        ['visits', 'idx_visits_status_created',       ['status', 'created_at']],

        // billing
        ['billing_items',   'idx_billing_items_visit',     ['visit_id']],
        ['invoices',        'idx_invoices_patient',        ['patient_id']],
        ['invoices',        'idx_invoices_status',         ['status']],
        ['invoices',        'idx_invoices_created',        ['created_at']],
        ['payments',        'idx_payments_invoice',        ['invoice_id']],
        ['payments',        'idx_payments_created',        ['created_at']],

        // pharmacy / prescriptions
        ['prescriptions',   'idx_rx_visit',                ['visit_id']],
        ['prescriptions',   'idx_rx_status',               ['status']],
        ['drug_stocks',     'idx_drugstocks_drug',         ['drug_id']],

        // investigations / lab
        ['lab_requests',    'idx_labreq_visit',            ['visit_id']],
        ['lab_requests',    'idx_labreq_status',           ['status']],
        ['lab_requests',    'idx_labreq_target_dept',      ['target_department_id']],

        // appointments
        ['appointments',    'idx_appt_patient',            ['patient_id']],
        ['appointments',    'idx_appt_doctor_date',        ['doctor_id', 'appointment_date']],
        ['appointments',    'idx_appt_status',             ['status']],

        // claims
        ['claims',          'idx_claims_status',           ['status']],
        ['claims',          'idx_claims_patient',          ['patient_id']],

        // patients (fast search)
        ['patients',        'idx_patients_phone',          ['phone']],
        ['patients',        'idx_patients_created',        ['created_at']],
    ];

    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        foreach ($this->indexes as [$table, $name, $cols]) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            // Verify all columns exist (this MySQL barfs on Schema::hasColumn)
            $missing = false;
            foreach ($cols as $col) {
                $colSafe = str_replace('`', '', $col);
                $rs = DB::select("SHOW COLUMNS FROM `{$table}` LIKE '{$colSafe}'");
                if (empty($rs)) { $missing = true; break; }
            }
            if ($missing) {
                continue;
            }
            // Check if index already exists
            $nameSafe = str_replace('`', '', $name);
            $existing = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$nameSafe}'");
            if (!empty($existing)) {
                continue;
            }
            $colList = implode(',', array_map(fn($c) => "`{$c}`", $cols));
            DB::statement("CREATE INDEX `{$name}` ON `{$table}` ({$colList})");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        foreach ($this->indexes as [$table, $name, $_cols]) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            $nameSafe = str_replace('`', '', $name);
            $existing = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$nameSafe}'");
            if (empty($existing)) {
                continue;
            }
            DB::statement("DROP INDEX `{$name}` ON `{$table}`");
        }
    }
};
