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
            Schema::table('payments', function (Blueprint $table) {
                if (! Schema::hasColumn('payments', 'invoice_receivable_id')) {
                    $table->foreignId('invoice_receivable_id')->nullable()->after('invoice_id')->constrained('invoice_receivables')->nullOnDelete();
                }
                if (! Schema::hasColumn('payments', 'payer_type')) {
                    $table->string('payer_type', 30)->default('patient')->after('patient_id');
                }
                if (! Schema::hasColumn('payments', 'payer_id')) {
                    $table->unsignedBigInteger('payer_id')->nullable()->after('payer_type');
                }
                if (! Schema::hasColumn('payments', 'insurance_provider_id')) {
                    $table->foreignId('insurance_provider_id')->nullable()->after('payer_id')->constrained('insurance_providers')->nullOnDelete();
                }
                if (! Schema::hasColumn('payments', 'sponsor_id')) {
                    $table->foreignId('sponsor_id')->nullable()->after('insurance_provider_id')->constrained('sponsors')->nullOnDelete();
                }
                if (! Schema::hasColumn('payments', 'corporate_client_id')) {
                    $table->foreignId('corporate_client_id')->nullable()->after('sponsor_id')->constrained('corporate_clients')->nullOnDelete();
                }
                if (! Schema::hasColumn('payments', 'claim_id')) {
                    $table->foreignId('claim_id')->nullable()->after('corporate_client_id')->constrained('claims')->nullOnDelete();
                }
            });

            return;
        }

        $columns = [
            'invoice_receivable_id' => 'ADD COLUMN invoice_receivable_id BIGINT UNSIGNED NULL AFTER invoice_id',
            'payer_type' => "ADD COLUMN payer_type VARCHAR(30) NOT NULL DEFAULT 'patient' AFTER patient_id",
            'payer_id' => 'ADD COLUMN payer_id BIGINT UNSIGNED NULL AFTER payer_type',
            'insurance_provider_id' => 'ADD COLUMN insurance_provider_id BIGINT UNSIGNED NULL AFTER payer_id',
            'sponsor_id' => 'ADD COLUMN sponsor_id BIGINT UNSIGNED NULL AFTER insurance_provider_id',
            'corporate_client_id' => 'ADD COLUMN corporate_client_id BIGINT UNSIGNED NULL AFTER sponsor_id',
            'claim_id' => 'ADD COLUMN claim_id BIGINT UNSIGNED NULL AFTER corporate_client_id',
        ];

        foreach ($columns as $column => $definition) {
            if (! $this->columnExists('payments', $column)) {
                DB::statement("ALTER TABLE payments {$definition}");
            }
        }

        $this->index('payments_invoice_receivable_id_index', 'CREATE INDEX payments_invoice_receivable_id_index ON payments (invoice_receivable_id)');
        $this->index('payments_payer_index', 'CREATE INDEX payments_payer_index ON payments (payer_type, payer_id)');
        $this->index('payments_sponsor_id_index', 'CREATE INDEX payments_sponsor_id_index ON payments (sponsor_id)');
        $this->index('payments_insurance_provider_id_index', 'CREATE INDEX payments_insurance_provider_id_index ON payments (insurance_provider_id)');
        $this->index('payments_corporate_client_id_index', 'CREATE INDEX payments_corporate_client_id_index ON payments (corporate_client_id)');
        $this->index('payments_claim_id_index', 'CREATE INDEX payments_claim_id_index ON payments (claim_id)');

        $this->foreign('payments_invoice_receivable_id_foreign', 'ALTER TABLE payments ADD CONSTRAINT payments_invoice_receivable_id_foreign FOREIGN KEY (invoice_receivable_id) REFERENCES invoice_receivables(id) ON DELETE SET NULL');
        $this->foreign('payments_insurance_provider_id_foreign', 'ALTER TABLE payments ADD CONSTRAINT payments_insurance_provider_id_foreign FOREIGN KEY (insurance_provider_id) REFERENCES insurance_providers(id) ON DELETE SET NULL');
        $this->foreign('payments_sponsor_id_foreign', 'ALTER TABLE payments ADD CONSTRAINT payments_sponsor_id_foreign FOREIGN KEY (sponsor_id) REFERENCES sponsors(id) ON DELETE SET NULL');
        $this->foreign('payments_corporate_client_id_foreign', 'ALTER TABLE payments ADD CONSTRAINT payments_corporate_client_id_foreign FOREIGN KEY (corporate_client_id) REFERENCES corporate_clients(id) ON DELETE SET NULL');
        $this->foreign('payments_claim_id_foreign', 'ALTER TABLE payments ADD CONSTRAINT payments_claim_id_foreign FOREIGN KEY (claim_id) REFERENCES claims(id) ON DELETE SET NULL');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropForeign(['invoice_receivable_id']);
                $table->dropForeign(['insurance_provider_id']);
                $table->dropForeign(['sponsor_id']);
                $table->dropForeign(['corporate_client_id']);
                $table->dropForeign(['claim_id']);
                $table->dropColumn([
                    'invoice_receivable_id',
                    'payer_type',
                    'payer_id',
                    'insurance_provider_id',
                    'sponsor_id',
                    'corporate_client_id',
                    'claim_id',
                ]);
            });

            return;
        }

        foreach ([
            'payments_invoice_receivable_id_foreign',
            'payments_insurance_provider_id_foreign',
            'payments_sponsor_id_foreign',
            'payments_corporate_client_id_foreign',
            'payments_claim_id_foreign',
        ] as $foreign) {
            try { DB::statement("ALTER TABLE payments DROP FOREIGN KEY {$foreign}"); } catch (Throwable) {}
        }

        foreach ([
            'payments_invoice_receivable_id_index',
            'payments_payer_index',
            'payments_sponsor_id_index',
            'payments_insurance_provider_id_index',
            'payments_corporate_client_id_index',
            'payments_claim_id_index',
        ] as $index) {
            try { DB::statement("DROP INDEX {$index} ON payments"); } catch (Throwable) {}
        }

        foreach ([
            'claim_id',
            'corporate_client_id',
            'sponsor_id',
            'insurance_provider_id',
            'payer_id',
            'payer_type',
            'invoice_receivable_id',
        ] as $column) {
            if ($this->columnExists('payments', $column)) {
                DB::statement("ALTER TABLE payments DROP COLUMN {$column}");
            }
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        $row = DB::selectOne(
            "SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
            [$table, $column]
        );

        return $row && (int) $row->cnt > 0;
    }

    private function index(string $name, string $statement): void
    {
        if (! $this->indexExists($name)) {
            DB::statement($statement);
        }
    }

    private function foreign(string $name, string $statement): void
    {
        if (! $this->foreignExists($name)) {
            DB::statement($statement);
        }
    }

    private function indexExists(string $index): bool
    {
        $row = DB::selectOne(
            "SELECT COUNT(*) AS cnt FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND INDEX_NAME = ?",
            [$index]
        );

        return $row && (int) $row->cnt > 0;
    }

    private function foreignExists(string $foreign): bool
    {
        $row = DB::selectOne(
            "SELECT COUNT(*) AS cnt FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND CONSTRAINT_NAME = ?",
            [$foreign]
        );

        return $row && (int) $row->cnt > 0;
    }
};
