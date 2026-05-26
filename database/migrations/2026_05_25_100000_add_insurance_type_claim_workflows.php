<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('insurance_types')) {
            Schema::create('insurance_types', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code', 40)->unique();
                $table->text('description')->nullable();
                $table->string('claim_workflow', 40)->nullable();
                $table->boolean('requires_claim_submission')->default(false);
                $table->boolean('requires_verification_code')->default(false);
                $table->string('verification_code_label', 80)->nullable();
                $table->boolean('requires_diagnosis')->default(false);
                $table->boolean('requires_doctor')->default(false);
                $table->string('default_claim_export_format', 20)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        $this->seedInsuranceTypes();

        // --- insurance_providers additions (raw DDL — avoids generation_expression introspection on MariaDB 10.1) ---
        $this->ensureColumn('insurance_providers', 'insurance_type_id',
            'ALTER TABLE insurance_providers ADD COLUMN insurance_type_id BIGINT UNSIGNED NULL');
        $this->ensureForeignKey('insurance_providers', 'insurance_providers_insurance_type_id_foreign',
            'ALTER TABLE insurance_providers ADD CONSTRAINT insurance_providers_insurance_type_id_foreign '
            .'FOREIGN KEY (insurance_type_id) REFERENCES insurance_types(id) ON DELETE SET NULL');
        $this->ensureColumn('insurance_providers', 'code',
            'ALTER TABLE insurance_providers ADD COLUMN code VARCHAR(60) NULL');
        $this->ensureIndex('insurance_providers', 'insurance_providers_code_index',
            'CREATE INDEX insurance_providers_code_index ON insurance_providers (code)');
        $this->ensureColumn('insurance_providers', 'description',
            'ALTER TABLE insurance_providers ADD COLUMN description TEXT NULL');
        $this->ensureColumn('insurance_providers', 'requires_claim_submission',
            'ALTER TABLE insurance_providers ADD COLUMN requires_claim_submission TINYINT(1) NULL');
        $this->ensureColumn('insurance_providers', 'requires_verification_code',
            'ALTER TABLE insurance_providers ADD COLUMN requires_verification_code TINYINT(1) NULL');
        $this->ensureColumn('insurance_providers', 'verification_code_label',
            'ALTER TABLE insurance_providers ADD COLUMN verification_code_label VARCHAR(80) NULL');
        $this->ensureColumn('insurance_providers', 'claim_workflow_override',
            'ALTER TABLE insurance_providers ADD COLUMN claim_workflow_override VARCHAR(40) NULL');
        $this->ensureColumn('insurance_providers', 'claim_export_format_override',
            'ALTER TABLE insurance_providers ADD COLUMN claim_export_format_override VARCHAR(20) NULL');

        $this->backfillProviderTypes();

        // --- claims additions (raw DDL) ---
        $this->ensureColumn('claims', 'claim_type_code',
            'ALTER TABLE claims ADD COLUMN claim_type_code VARCHAR(40) NULL');
        $this->ensureIndex('claims', 'claims_claim_type_code_index',
            'CREATE INDEX claims_claim_type_code_index ON claims (claim_type_code)');
        $this->ensureColumn('claims', 'claim_workflow_code',
            'ALTER TABLE claims ADD COLUMN claim_workflow_code VARCHAR(40) NULL');
        $this->ensureIndex('claims', 'claims_claim_workflow_code_index',
            'CREATE INDEX claims_claim_workflow_code_index ON claims (claim_workflow_code)');
        $this->ensureColumn('claims', 'insurance_type_id',
            'ALTER TABLE claims ADD COLUMN insurance_type_id BIGINT UNSIGNED NULL');
        $this->ensureForeignKey('claims', 'claims_insurance_type_id_foreign',
            'ALTER TABLE claims ADD CONSTRAINT claims_insurance_type_id_foreign '
            .'FOREIGN KEY (insurance_type_id) REFERENCES insurance_types(id) ON DELETE SET NULL');
        $this->ensureColumn('claims', 'patient_insurance_id',
            'ALTER TABLE claims ADD COLUMN patient_insurance_id BIGINT UNSIGNED NULL');
        $this->ensureForeignKey('claims', 'claims_patient_insurance_id_foreign',
            'ALTER TABLE claims ADD CONSTRAINT claims_patient_insurance_id_foreign '
            .'FOREIGN KEY (patient_insurance_id) REFERENCES patient_insurances(id) ON DELETE SET NULL');
        $this->ensureColumn('claims', 'membership_number',
            'ALTER TABLE claims ADD COLUMN membership_number VARCHAR(120) NULL');
        $this->ensureColumn('claims', 'verification_code',
            'ALTER TABLE claims ADD COLUMN verification_code VARCHAR(120) NULL');
        $this->ensureColumn('claims', 'authorization_code',
            'ALTER TABLE claims ADD COLUMN authorization_code VARCHAR(120) NULL');
        $this->ensureColumn('claims', 'claim_period_start',
            'ALTER TABLE claims ADD COLUMN claim_period_start DATE NULL');
        $this->ensureColumn('claims', 'claim_period_end',
            'ALTER TABLE claims ADD COLUMN claim_period_end DATE NULL');
        $this->ensureColumn('claims', 'submitted_by',
            'ALTER TABLE claims ADD COLUMN submitted_by BIGINT UNSIGNED NULL');
        $this->ensureForeignKey('claims', 'claims_submitted_by_foreign',
            'ALTER TABLE claims ADD CONSTRAINT claims_submitted_by_foreign '
            .'FOREIGN KEY (submitted_by) REFERENCES users(id) ON DELETE SET NULL');
        $this->ensureColumn('claims', 'prepared_by',
            'ALTER TABLE claims ADD COLUMN prepared_by BIGINT UNSIGNED NULL');
        $this->ensureForeignKey('claims', 'claims_prepared_by_foreign',
            'ALTER TABLE claims ADD CONSTRAINT claims_prepared_by_foreign '
            .'FOREIGN KEY (prepared_by) REFERENCES users(id) ON DELETE SET NULL');
        $this->ensureColumn('claims', 'reviewed_by',
            'ALTER TABLE claims ADD COLUMN reviewed_by BIGINT UNSIGNED NULL');
        $this->ensureForeignKey('claims', 'claims_reviewed_by_foreign',
            'ALTER TABLE claims ADD CONSTRAINT claims_reviewed_by_foreign '
            .'FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL');
        $this->ensureColumn('claims', 'approved_by',
            'ALTER TABLE claims ADD COLUMN approved_by BIGINT UNSIGNED NULL');
        $this->ensureForeignKey('claims', 'claims_approved_by_foreign',
            'ALTER TABLE claims ADD CONSTRAINT claims_approved_by_foreign '
            .'FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL');
        $this->ensureColumn('claims', 'submission_mode',
            'ALTER TABLE claims ADD COLUMN submission_mode VARCHAR(40) NULL');
        $this->ensureColumn('claims', 'submission_reference',
            'ALTER TABLE claims ADD COLUMN submission_reference VARCHAR(120) NULL');
        $this->ensureColumn('claims', 'export_file_path',
            'ALTER TABLE claims ADD COLUMN export_file_path VARCHAR(255) NULL');
        $this->ensureColumn('claims', 'total_claim_amount',
            'ALTER TABLE claims ADD COLUMN total_claim_amount DECIMAL(12,2) NOT NULL DEFAULT 0');
        $this->ensureColumn('claims', 'rejected_amount',
            'ALTER TABLE claims ADD COLUMN rejected_amount DECIMAL(12,2) NOT NULL DEFAULT 0');
        $this->ensureColumn('claims', 'paid_amount',
            'ALTER TABLE claims ADD COLUMN paid_amount DECIMAL(12,2) NOT NULL DEFAULT 0');
        $this->ensureColumn('claims', 'rejection_reason',
            'ALTER TABLE claims ADD COLUMN rejection_reason TEXT NULL');
        $this->ensureColumn('claims', 'notes',
            'ALTER TABLE claims ADD COLUMN notes TEXT NULL');

        // --- claim_items additions (raw DDL) ---
        $this->ensureColumn('claim_items', 'invoice_item_id',
            'ALTER TABLE claim_items ADD COLUMN invoice_item_id BIGINT UNSIGNED NULL');
        $this->ensureForeignKey('claim_items', 'claim_items_invoice_item_id_foreign',
            'ALTER TABLE claim_items ADD CONSTRAINT claim_items_invoice_item_id_foreign '
            .'FOREIGN KEY (invoice_item_id) REFERENCES invoice_items(id) ON DELETE SET NULL');
        $this->ensureColumn('claim_items', 'visit_id',
            'ALTER TABLE claim_items ADD COLUMN visit_id BIGINT UNSIGNED NULL');
        $this->ensureForeignKey('claim_items', 'claim_items_visit_id_foreign',
            'ALTER TABLE claim_items ADD CONSTRAINT claim_items_visit_id_foreign '
            .'FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE SET NULL');
        $this->ensureColumn('claim_items', 'patient_id',
            'ALTER TABLE claim_items ADD COLUMN patient_id BIGINT UNSIGNED NULL');
        $this->ensureForeignKey('claim_items', 'claim_items_patient_id_foreign',
            'ALTER TABLE claim_items ADD CONSTRAINT claim_items_patient_id_foreign '
            .'FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL');
        $this->ensureColumn('claim_items', 'service_id',
            'ALTER TABLE claim_items ADD COLUMN service_id BIGINT UNSIGNED NULL');
        $this->ensureForeignKey('claim_items', 'claim_items_service_id_foreign',
            'ALTER TABLE claim_items ADD CONSTRAINT claim_items_service_id_foreign '
            .'FOREIGN KEY (service_id) REFERENCES service_catalog(id) ON DELETE SET NULL');
        $this->ensureColumn('claim_items', 'product_id',
            'ALTER TABLE claim_items ADD COLUMN product_id BIGINT UNSIGNED NULL');
        $this->ensureForeignKey('claim_items', 'claim_items_product_id_foreign',
            'ALTER TABLE claim_items ADD CONSTRAINT claim_items_product_id_foreign '
            .'FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL');
        $this->ensureColumn('claim_items', 'department_id',
            'ALTER TABLE claim_items ADD COLUMN department_id BIGINT UNSIGNED NULL');
        $this->ensureForeignKey('claim_items', 'claim_items_department_id_foreign',
            'ALTER TABLE claim_items ADD CONSTRAINT claim_items_department_id_foreign '
            .'FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL');
        $this->ensureColumn('claim_items', 'description',
            'ALTER TABLE claim_items ADD COLUMN description VARCHAR(255) NULL');
        $this->ensureColumn('claim_items', 'item_type',
            'ALTER TABLE claim_items ADD COLUMN item_type VARCHAR(60) NULL');
        $this->ensureColumn('claim_items', 'cash_price',
            'ALTER TABLE claim_items ADD COLUMN cash_price DECIMAL(12,2) NULL');
        $this->ensureColumn('claim_items', 'insurance_price',
            'ALTER TABLE claim_items ADD COLUMN insurance_price DECIMAL(12,2) NULL');
        $this->ensureColumn('claim_items', 'selected_price',
            'ALTER TABLE claim_items ADD COLUMN selected_price DECIMAL(12,2) NULL');
        $this->ensureColumn('claim_items', 'claim_amount',
            'ALTER TABLE claim_items ADD COLUMN claim_amount DECIMAL(12,2) NOT NULL DEFAULT 0');
        $this->ensureColumn('claim_items', 'rejected_amount',
            'ALTER TABLE claim_items ADD COLUMN rejected_amount DECIMAL(12,2) NULL');
        $this->ensureColumn('claim_items', 'metadata',
            'ALTER TABLE claim_items ADD COLUMN metadata LONGTEXT NULL');

        if (! Schema::hasTable('claim_status_logs')) {
            Schema::create('claim_status_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('claim_id')->constrained('claims')->cascadeOnDelete();
                $table->string('from_status', 60)->nullable();
                $table->string('to_status', 60);
                $table->text('notes')->nullable();
                $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('claim_payments')) {
            Schema::create('claim_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('claim_id')->constrained('claims')->cascadeOnDelete();
                $table->foreignId('insurance_type_id')->nullable()->constrained('insurance_types')->nullOnDelete();
                $table->foreignId('insurance_provider_id')->nullable()->constrained('insurance_providers')->nullOnDelete();
                $table->date('payment_date');
                $table->decimal('amount', 12, 2);
                $table->string('payment_reference', 120)->nullable();
                $table->string('payment_method', 80)->nullable();
                $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        $this->backfillClaims();
        $this->backfillClaimItems();
    }

    public function down(): void
    {
        Schema::dropIfExists('claim_payments');
        Schema::dropIfExists('claim_status_logs');
    }

    private function seedInsuranceTypes(): void
    {
        $now = now();

        foreach ([
            [
                'name' => 'National Health Insurance Authority',
                'code' => 'NHIA',
                'description' => 'Ghana national health insurance authority claim workflow.',
                'claim_workflow' => 'NHIA',
                'requires_claim_submission' => true,
                'requires_verification_code' => true,
                'verification_code_label' => 'CCC Code',
                'requires_diagnosis' => true,
                'requires_doctor' => true,
                'default_claim_export_format' => 'CSV',
                'is_active' => true,
            ],
            [
                'name' => 'Private Insurance',
                'code' => 'PRIVATE',
                'description' => 'Generic private insurance workflow.',
                'claim_workflow' => 'GENERIC',
                'requires_claim_submission' => true,
                'requires_verification_code' => false,
                'verification_code_label' => 'Verification Code',
                'requires_diagnosis' => false,
                'requires_doctor' => false,
                'default_claim_export_format' => 'CSV',
                'is_active' => true,
            ],
            [
                'name' => 'Corporate Insurance',
                'code' => 'CORPORATE',
                'description' => 'Generic corporate insurance workflow.',
                'claim_workflow' => 'GENERIC',
                'requires_claim_submission' => true,
                'requires_verification_code' => false,
                'verification_code_label' => 'Authorization Code',
                'requires_diagnosis' => false,
                'requires_doctor' => false,
                'default_claim_export_format' => 'CSV',
                'is_active' => true,
            ],
            [
                'name' => 'Self Sponsored',
                'code' => 'SELF',
                'description' => 'Cash and carry billing; claims are not normally submitted.',
                'claim_workflow' => null,
                'requires_claim_submission' => false,
                'requires_verification_code' => false,
                'verification_code_label' => null,
                'requires_diagnosis' => false,
                'requires_doctor' => false,
                'default_claim_export_format' => null,
                'is_active' => true,
            ],
        ] as $row) {
            DB::table('insurance_types')->updateOrInsert(
                ['code' => $row['code']],
                array_merge($row, ['updated_at' => $now, 'created_at' => $now])
            );
        }
    }

    private function backfillProviderTypes(): void
    {
        $typeIds = DB::table('insurance_types')->pluck('id', 'code');

        DB::table('insurance_providers')
            ->where('short_name', 'NHIA')
            ->orWhere('name', 'National Health Insurance Authority')
            ->update([
                'name' => 'National Health Insurance Scheme',
                'short_name' => 'NHIS',
                'code' => 'NHIS',
            ]);

        DB::table('insurance_providers')
            ->select(['id', 'short_name', 'type', 'code', 'insurance_type_id'])
            ->orderBy('id')
            ->get()
            ->each(function ($provider) use ($typeIds) {
                $legacyType = strtoupper((string) $provider->type);
                $code = $provider->code ?: ($provider->short_name ?: null);
                $typeCode = match ($legacyType) {
                    'NHIA', 'NHIS', 'PUBLIC' => 'NHIA',
                    'CORPORATE' => 'CORPORATE',
                    'SELF' => 'SELF',
                    default => 'PRIVATE',
                };

                DB::table('insurance_providers')
                    ->where('id', $provider->id)
                    ->update([
                        'code' => $code,
                        'insurance_type_id' => $provider->insurance_type_id ?: ($typeIds[$typeCode] ?? null),
                    ]);
            });
    }

    private function backfillClaims(): void
    {
        if (! $this->columnExists('claims', 'insurance_type_id')) {
            return;
        }

        $providers = DB::table('insurance_providers')->get()->keyBy('id');
        $types = DB::table('insurance_types')->get()->keyBy('id');

        DB::table('claims')
            ->select(['id', 'insurance_provider_id', 'insurance_type_id', 'claim_type_code', 'total_amount', 'period_from', 'period_to'])
            ->orderBy('id')
            ->get()
            ->each(function ($claim) use ($providers, $types) {
                $provider = $providers->get($claim->insurance_provider_id);
                $typeId = $claim->insurance_type_id ?: $provider?->insurance_type_id;
                $type = $typeId ? $types->get($typeId) : null;

                DB::table('claims')->where('id', $claim->id)->update([
                    'insurance_type_id' => $typeId,
                    'claim_type_code' => $claim->claim_type_code ?: $type?->code,
                    'claim_workflow_code' => $type?->claim_workflow ?: ($provider?->type === 'nhia' ? 'NHIA' : 'GENERIC'),
                    'total_claim_amount' => $claim->total_amount ?: 0,
                    'claim_period_start' => $claim->period_from,
                    'claim_period_end' => $claim->period_to,
                ]);
            });
    }

    private function backfillClaimItems(): void
    {
        if (! $this->columnExists('claim_items', 'claim_amount')) {
            return;
        }

        DB::table('claim_items')
            ->whereNull('description')
            ->update([
                'description' => DB::raw('service_name'),
                'item_type' => DB::raw('service_type'),
                'selected_price' => DB::raw('unit_price'),
                'claim_amount' => DB::raw('COALESCE(total_price, 0)'),
            ]);
    }

    // ── Raw-DDL helpers (bypass Blueprint introspection on MariaDB 10.1) ──────

    private function columnExists(string $table, string $column): bool
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return Schema::hasColumn($table, $column);
        }

        $row = DB::selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.columns '
            .'WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
            [$table, $column]
        );

        return (int) ($row->c ?? 0) > 0;
    }

    private function indexExists(string $table, string $index): bool
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return collect(Schema::getIndexes($table))->contains(fn ($item) => ($item['name'] ?? null) === $index);
        }

        $row = DB::selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.statistics '
            .'WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
            [$table, $index]
        );

        return (int) ($row->c ?? 0) > 0;
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return false;
        }

        $row = DB::selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.table_constraints '
            .'WHERE table_schema = DATABASE() AND table_name = ? AND constraint_name = ? '
            .'AND constraint_type = "FOREIGN KEY"',
            [$table, $constraint]
        );

        return (int) ($row->c ?? 0) > 0;
    }

    private function ensureColumn(string $table, string $column, string $sql): void
    {
        if (! $this->columnExists($table, $column)) {
            DB::statement($sql);
        }
    }

    private function ensureIndex(string $table, string $index, string $sql): void
    {
        if (! $this->indexExists($table, $index)) {
            DB::statement($sql);
        }
    }

    private function ensureForeignKey(string $table, string $constraint, string $sql): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        if (! $this->foreignKeyExists($table, $constraint)) {
            DB::statement($sql);
        }
    }
};
