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
            if (! Schema::hasTable('corporate_clients')) {
                Schema::create('corporate_clients', function (Blueprint $table) {
                    $table->id();
                    $table->string('code')->unique();
                    $table->string('name');
                    $table->string('contact_person')->nullable();
                    $table->string('email')->nullable();
                    $table->string('phone')->nullable();
                    $table->string('address')->nullable();
                    $table->decimal('credit_limit', 14, 2)->nullable();
                    $table->unsignedInteger('default_due_days')->default(30);
                    $table->boolean('is_active')->default(true);
                    $table->text('notes')->nullable();
                    $table->timestamps();
                    $table->softDeletes();
                    $table->index('is_active');
                });
            }

            if (! Schema::hasTable('sponsor_authorizations')) {
                Schema::create('sponsor_authorizations', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('sponsor_id')->constrained('sponsors')->cascadeOnDelete();
                    $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
                    $table->foreignId('visit_id')->nullable()->constrained('visits')->nullOnDelete();
                    $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
                    $table->string('authorization_number')->nullable();
                    $table->decimal('authorized_amount', 14, 2);
                    $table->decimal('used_amount', 14, 2)->default(0);
                    $table->date('valid_from')->nullable();
                    $table->date('valid_until')->nullable();
                    $table->string('status', 30)->default('active');
                    $table->text('notes')->nullable();
                    $table->foreignId('authorized_by')->nullable()->constrained('users')->nullOnDelete();
                    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                    $table->timestamps();
                    $table->softDeletes();
                    $table->index(['sponsor_id', 'status']);
                    $table->index(['patient_id', 'status']);
                });
            }

            if (! Schema::hasColumn('invoices', 'corporate_client_id')) {
                Schema::table('invoices', function (Blueprint $table) {
                    $table->foreignId('corporate_client_id')->nullable()->after('sponsor_id')->constrained('corporate_clients')->nullOnDelete();
                });
            }

            return;
        }

        if (! $this->tableExists('corporate_clients')) {
            DB::statement("
                CREATE TABLE corporate_clients (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    code VARCHAR(50) NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    contact_person VARCHAR(255) NULL,
                    email VARCHAR(255) NULL,
                    phone VARCHAR(50) NULL,
                    address VARCHAR(500) NULL,
                    credit_limit DECIMAL(14,2) NULL,
                    default_due_days INT UNSIGNED NOT NULL DEFAULT 30,
                    is_active TINYINT(1) NOT NULL DEFAULT 1,
                    notes TEXT NULL,
                    created_at TIMESTAMP NULL DEFAULT NULL,
                    updated_at TIMESTAMP NULL DEFAULT NULL,
                    deleted_at TIMESTAMP NULL DEFAULT NULL,
                    UNIQUE KEY corporate_clients_code_unique (code),
                    INDEX corporate_clients_is_active_index (is_active)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (! $this->tableExists('sponsor_authorizations')) {
            DB::statement("
                CREATE TABLE sponsor_authorizations (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    sponsor_id BIGINT UNSIGNED NOT NULL,
                    patient_id BIGINT UNSIGNED NULL,
                    visit_id BIGINT UNSIGNED NULL,
                    invoice_id BIGINT UNSIGNED NULL,
                    authorization_number VARCHAR(100) NULL,
                    authorized_amount DECIMAL(14,2) NOT NULL,
                    used_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
                    valid_from DATE NULL,
                    valid_until DATE NULL,
                    status VARCHAR(30) NOT NULL DEFAULT 'active',
                    notes TEXT NULL,
                    authorized_by BIGINT UNSIGNED NULL,
                    created_by BIGINT UNSIGNED NULL,
                    created_at TIMESTAMP NULL DEFAULT NULL,
                    updated_at TIMESTAMP NULL DEFAULT NULL,
                    deleted_at TIMESTAMP NULL DEFAULT NULL,
                    INDEX sponsor_authorizations_sponsor_status_index (sponsor_id, status),
                    INDEX sponsor_authorizations_patient_status_index (patient_id, status),
                    CONSTRAINT sponsor_authorizations_sponsor_id_foreign FOREIGN KEY (sponsor_id) REFERENCES sponsors(id) ON DELETE CASCADE,
                    CONSTRAINT sponsor_authorizations_patient_id_foreign FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL,
                    CONSTRAINT sponsor_authorizations_visit_id_foreign FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE SET NULL,
                    CONSTRAINT sponsor_authorizations_invoice_id_foreign FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL,
                    CONSTRAINT sponsor_authorizations_authorized_by_foreign FOREIGN KEY (authorized_by) REFERENCES users(id) ON DELETE SET NULL,
                    CONSTRAINT sponsor_authorizations_created_by_foreign FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (! $this->columnExists('invoices', 'corporate_client_id')) {
            DB::statement("ALTER TABLE invoices ADD COLUMN corporate_client_id BIGINT UNSIGNED NULL AFTER sponsor_id");
            DB::statement("CREATE INDEX invoices_corporate_client_id_index ON invoices (corporate_client_id)");
            DB::statement("ALTER TABLE invoices ADD CONSTRAINT invoices_corporate_client_id_foreign FOREIGN KEY (corporate_client_id) REFERENCES corporate_clients(id) ON DELETE SET NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('invoices', function (Blueprint $table) {
                if (Schema::hasColumn('invoices', 'corporate_client_id')) {
                    $table->dropForeign(['corporate_client_id']);
                    $table->dropColumn('corporate_client_id');
                }
            });
            Schema::dropIfExists('sponsor_authorizations');
            Schema::dropIfExists('corporate_clients');

            return;
        }

        if ($this->columnExists('invoices', 'corporate_client_id')) {
            try { DB::statement('ALTER TABLE invoices DROP FOREIGN KEY invoices_corporate_client_id_foreign'); } catch (Throwable) {}
            try { DB::statement('DROP INDEX invoices_corporate_client_id_index ON invoices'); } catch (Throwable) {}
            DB::statement('ALTER TABLE invoices DROP COLUMN corporate_client_id');
        }

        DB::statement('DROP TABLE IF EXISTS sponsor_authorizations');
        DB::statement('DROP TABLE IF EXISTS corporate_clients');
    }

    private function tableExists(string $table): bool
    {
        $row = DB::selectOne(
            "SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
            [$table]
        );

        return $row && (int) $row->cnt > 0;
    }

    private function columnExists(string $table, string $column): bool
    {
        $row = DB::selectOne(
            "SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
            [$table, $column]
        );

        return $row && (int) $row->cnt > 0;
    }
};
