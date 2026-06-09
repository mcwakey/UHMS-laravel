<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            if (Schema::hasTable('invoice_receivables')) {
                return;
            }

            Schema::create('invoice_receivables', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
                $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
                $table->foreignId('visit_id')->nullable()->constrained('visits')->nullOnDelete();
                $table->string('payer_type', 30);
                $table->unsignedBigInteger('payer_id')->nullable();
                $table->decimal('original_amount', 14, 2)->default(0);
                $table->decimal('allocated_amount', 14, 2)->default(0);
                $table->decimal('paid_amount', 14, 2)->default(0);
                $table->decimal('discount_amount', 14, 2)->default(0);
                $table->decimal('credit_note_amount', 14, 2)->default(0);
                $table->decimal('write_off_amount', 14, 2)->default(0);
                $table->decimal('refund_amount', 14, 2)->default(0);
                $table->decimal('balance', 14, 2)->default(0);
                $table->date('aging_start_date');
                $table->date('due_date')->nullable();
                $table->string('status', 30)->default('pending');
                $table->foreignId('insurance_provider_id')->nullable()->constrained('insurance_providers')->nullOnDelete();
                $table->foreignId('sponsor_id')->nullable()->constrained('sponsors')->nullOnDelete();
                $table->foreignId('corporate_client_id')->nullable()->constrained('corporate_clients')->nullOnDelete();
                $table->foreignId('claim_id')->nullable()->constrained('claims')->nullOnDelete();
                $table->foreignId('sponsor_authorization_id')->nullable()->constrained('sponsor_authorizations')->nullOnDelete();
                $table->unsignedBigInteger('corporate_account_id')->nullable();
                $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
                $table->string('accounting_status', 20)->nullable()->index();
                $table->timestamp('accounting_posted_at')->nullable();
                $table->text('accounting_error')->nullable();
                $table->string('allocation_source', 30)->default('system');
                $table->json('metadata')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['invoice_id', 'payer_type']);
                $table->index(['payer_type', 'payer_id']);
                $table->index(['status', 'due_date']);
                $table->index('aging_start_date');
            });

            return;
        }

        if ($this->tableExists('invoice_receivables')) {
            return;
        }

        DB::statement("
            CREATE TABLE invoice_receivables (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                invoice_id BIGINT UNSIGNED NOT NULL,
                patient_id BIGINT UNSIGNED NULL,
                visit_id BIGINT UNSIGNED NULL,
                payer_type VARCHAR(30) NOT NULL,
                payer_id BIGINT UNSIGNED NULL,
                original_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
                allocated_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
                paid_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
                discount_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
                credit_note_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
                write_off_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
                refund_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
                balance DECIMAL(14,2) NOT NULL DEFAULT 0,
                aging_start_date DATE NOT NULL,
                due_date DATE NULL,
                status VARCHAR(30) NOT NULL DEFAULT 'pending',
                insurance_provider_id BIGINT UNSIGNED NULL,
                sponsor_id BIGINT UNSIGNED NULL,
                corporate_client_id BIGINT UNSIGNED NULL,
                claim_id BIGINT UNSIGNED NULL,
                sponsor_authorization_id BIGINT UNSIGNED NULL,
                corporate_account_id BIGINT UNSIGNED NULL,
                journal_entry_id BIGINT UNSIGNED NULL,
                accounting_status VARCHAR(20) NULL,
                accounting_posted_at TIMESTAMP NULL DEFAULT NULL,
                accounting_error TEXT NULL,
                allocation_source VARCHAR(30) NOT NULL DEFAULT 'system',
                metadata LONGTEXT NULL,
                created_by BIGINT UNSIGNED NULL,
                updated_by BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NULL DEFAULT NULL,
                updated_at TIMESTAMP NULL DEFAULT NULL,
                INDEX invoice_receivables_invoice_payer_index (invoice_id, payer_type),
                INDEX invoice_receivables_payer_index (payer_type, payer_id),
                INDEX invoice_receivables_status_due_index (status, due_date),
                INDEX invoice_receivables_aging_start_date_index (aging_start_date),
                INDEX invoice_receivables_accounting_status_index (accounting_status),
                CONSTRAINT invoice_receivables_invoice_id_foreign FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
                CONSTRAINT invoice_receivables_patient_id_foreign FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL,
                CONSTRAINT invoice_receivables_visit_id_foreign FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE SET NULL,
                CONSTRAINT invoice_receivables_insurance_provider_id_foreign FOREIGN KEY (insurance_provider_id) REFERENCES insurance_providers(id) ON DELETE SET NULL,
                CONSTRAINT invoice_receivables_sponsor_id_foreign FOREIGN KEY (sponsor_id) REFERENCES sponsors(id) ON DELETE SET NULL,
                CONSTRAINT invoice_receivables_corporate_client_id_foreign FOREIGN KEY (corporate_client_id) REFERENCES corporate_clients(id) ON DELETE SET NULL,
                CONSTRAINT invoice_receivables_claim_id_foreign FOREIGN KEY (claim_id) REFERENCES claims(id) ON DELETE SET NULL,
                CONSTRAINT invoice_receivables_sponsor_authorization_id_foreign FOREIGN KEY (sponsor_authorization_id) REFERENCES sponsor_authorizations(id) ON DELETE SET NULL,
                CONSTRAINT invoice_receivables_journal_entry_id_foreign FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(id) ON DELETE SET NULL,
                CONSTRAINT invoice_receivables_created_by_foreign FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
                CONSTRAINT invoice_receivables_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS invoice_receivables');
    }

    private function tableExists(string $table): bool
    {
        $row = DB::selectOne(
            "SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
            [$table]
        );

        return $row && (int) $row->cnt > 0;
    }
};
