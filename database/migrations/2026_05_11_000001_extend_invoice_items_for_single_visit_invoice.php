<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds source-tracking, per-line payment status, and insurance-aware pricing
 * snapshot fields to invoice_items. Uses raw SQL to avoid Laravel 12's
 * information_schema introspection (which queries `generation_expression`
 * unavailable on older MariaDB).
 */
return new class extends Migration {

    private function hasColumn(string $table, string $col): bool
    {
        try {
            return ! empty(DB::select("SHOW COLUMNS FROM `{$table}` LIKE '{$col}'"));
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function hasIndex(string $table, string $idx): bool
    {
        try {
            return ! empty(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$idx}'"));
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function exec(string $sql): void
    {
        try { DB::statement($sql); } catch (\Throwable $e) { /* idempotent */ }
    }

    public function up(): void
    {
        $cols = [
            'visit_id'             => "ALTER TABLE invoice_items ADD COLUMN visit_id BIGINT UNSIGNED NULL AFTER invoice_id",
            'patient_id'           => "ALTER TABLE invoice_items ADD COLUMN patient_id BIGINT UNSIGNED NULL AFTER visit_id",
            'department_id'        => "ALTER TABLE invoice_items ADD COLUMN department_id BIGINT UNSIGNED NULL AFTER service_catalog_id",
            'source_type'          => "ALTER TABLE invoice_items ADD COLUMN source_type VARCHAR(64) NULL AFTER department_id",
            'source_id'            => "ALTER TABLE invoice_items ADD COLUMN source_id BIGINT UNSIGNED NULL AFTER source_type",
            'insurance_price'      => "ALTER TABLE invoice_items ADD COLUMN insurance_price DECIMAL(12,2) NULL AFTER unit_price",
            'insurance_covered'    => "ALTER TABLE invoice_items ADD COLUMN insurance_covered DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER insurance_price",
            'patient_payable'      => "ALTER TABLE invoice_items ADD COLUMN patient_payable DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER insurance_covered",
            'paid_amount'          => "ALTER TABLE invoice_items ADD COLUMN paid_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER patient_payable",
            'balance'              => "ALTER TABLE invoice_items ADD COLUMN balance DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER paid_amount",
            'payment_status'       => "ALTER TABLE invoice_items ADD COLUMN payment_status VARCHAR(32) NOT NULL DEFAULT 'unpaid' AFTER balance",
            'patient_insurance_id' => "ALTER TABLE invoice_items ADD COLUMN patient_insurance_id BIGINT UNSIGNED NULL AFTER insurance_provider_id",
            'insurance_type'       => "ALTER TABLE invoice_items ADD COLUMN insurance_type VARCHAR(50) NULL AFTER patient_insurance_id",
            'created_by'           => "ALTER TABLE invoice_items ADD COLUMN created_by BIGINT UNSIGNED NULL AFTER insurance_type",
        ];
        foreach ($cols as $name => $sql) {
            if (! $this->hasColumn('invoice_items', $name)) {
                $this->exec($sql);
            }
        }

        $indexes = [
            'invoice_items_source_idx'         => "CREATE INDEX invoice_items_source_idx ON invoice_items (source_type, source_id)",
            'invoice_items_visit_id_idx'       => "CREATE INDEX invoice_items_visit_id_idx ON invoice_items (visit_id)",
            'invoice_items_patient_id_idx'     => "CREATE INDEX invoice_items_patient_id_idx ON invoice_items (patient_id)",
            'invoice_items_payment_status_idx' => "CREATE INDEX invoice_items_payment_status_idx ON invoice_items (payment_status)",
        ];
        foreach ($indexes as $name => $sql) {
            if (! $this->hasIndex('invoice_items', $name)) {
                $this->exec($sql);
            }
        }

        // Backfill
        $this->exec("
            UPDATE invoice_items ii
            JOIN invoices i ON i.id = ii.invoice_id
            SET ii.visit_id   = COALESCE(ii.visit_id, i.visit_id),
                ii.patient_id = COALESCE(ii.patient_id, i.patient_id)
        ");
        $this->exec("
            UPDATE invoice_items
            SET patient_payable   = GREATEST(0, COALESCE(total_price,0) - COALESCE(nhis_approved_amount,0)),
                balance           = GREATEST(0, COALESCE(total_price,0) - COALESCE(nhis_approved_amount,0)),
                insurance_covered = COALESCE(nhis_approved_amount,0)
            WHERE patient_payable = 0 AND balance = 0
        ");
        $this->exec("
            UPDATE invoice_items
            SET payment_status = 'paid'
            WHERE balance <= 0 AND total_price > 0
        ");
    }

    public function down(): void
    {
        foreach ([
            'invoice_items_source_idx',
            'invoice_items_visit_id_idx',
            'invoice_items_patient_id_idx',
            'invoice_items_payment_status_idx',
        ] as $idx) {
            try { DB::statement("DROP INDEX `{$idx}` ON invoice_items"); } catch (\Throwable $e) {}
        }
        foreach ([
            'created_by','insurance_type','patient_insurance_id','payment_status',
            'balance','paid_amount','patient_payable','insurance_covered','insurance_price',
            'source_id','source_type','department_id','patient_id','visit_id',
        ] as $col) {
            try { DB::statement("ALTER TABLE invoice_items DROP COLUMN `{$col}`"); } catch (\Throwable $e) {}
        }
    }
};
