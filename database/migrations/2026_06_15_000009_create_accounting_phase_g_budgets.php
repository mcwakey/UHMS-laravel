<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained('fiscal_years')->cascadeOnDelete();
            $table->string('name');
            $table->string('budget_type', 24)->default('annual');
            $table->string('status', 24)->default('draft');
            $table->string('enforcement_mode', 24)->default('warning');
            $table->unsignedInteger('version')->default(1);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('closed_at')->nullable();
            $table->timestamps();

            $table->index(['fiscal_year_id', 'status'], 'budget_year_status_idx');
            $table->index(['status', 'created_at'], 'budget_status_created_idx');
        });

        Schema::create('budget_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained('budgets')->cascadeOnDelete();
            $table->foreignId('accounting_period_id')->nullable()->constrained('accounting_periods')->nullOnDelete();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('amount', 18, 2)->default(0);
            $table->timestamps();

            $table->index(['budget_id', 'start_date', 'end_date'], 'budget_period_range_idx');
        });

        Schema::create('budget_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained('budgets')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->string('branch_code')->nullable();
            $table->string('project_code')->nullable();
            $table->string('grant_code')->nullable();
            $table->decimal('amount', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['budget_id', 'department_id', 'account_id', 'branch_code', 'project_code', 'grant_code'], 'budget_line_dimension_unique');
            $table->index(['department_id', 'account_id'], 'budget_line_dept_account_idx');
        });

        Schema::create('budget_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained('budgets')->cascadeOnDelete();
            $table->string('revision_number')->unique();
            $table->string('status', 24)->default('draft');
            $table->text('reason');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();

            $table->index(['budget_id', 'status'], 'budget_revision_status_idx');
        });

        Schema::create('budget_revision_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_revision_id')->constrained('budget_revisions')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->decimal('amount_delta', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['department_id', 'account_id'], 'budget_revision_line_dimension_idx');
        });

        Schema::create('budget_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained('budgets')->cascadeOnDelete();
            $table->string('transfer_number')->unique();
            $table->string('status', 24)->default('draft');
            $table->foreignId('from_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('from_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('to_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('to_account_id')->constrained('accounts')->restrictOnDelete();
            $table->decimal('amount', 18, 2);
            $table->text('reason');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();

            $table->index(['budget_id', 'status'], 'budget_transfer_status_idx');
        });

        Schema::create('budget_commitments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->nullable()->constrained('budgets')->nullOnDelete();
            $table->foreignId('fiscal_year_id')->constrained('fiscal_years')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->string('source_type', 120)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_reference')->nullable();
            $table->string('status', 24)->default('active');
            $table->decimal('original_amount', 18, 2);
            $table->decimal('remaining_amount', 18, 2);
            $table->boolean('is_over_budget')->default(false);
            $table->boolean('over_budget_acknowledged')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('released_at')->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['fiscal_year_id', 'department_id', 'account_id', 'status'], 'budget_commitment_available_idx');
            $table->index(['source_type', 'source_id'], 'budget_commitment_source_idx');
        });

        Schema::create('budget_commitment_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_commitment_id')->constrained('budget_commitments')->cascadeOnDelete();
            $table->string('movement_type', 24);
            $table->decimal('amount', 18, 2);
            $table->decimal('remaining_after', 18, 2);
            $table->string('source_type', 120)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['budget_commitment_id', 'movement_type'], 'budget_commitment_movement_type_idx');
        });

        Schema::create('budget_approval_limits', function (Blueprint $table) {
            $table->id();
            $table->string('approval_type', 40)->default('budget');
            $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('limit_amount', 18, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['approval_type', 'is_active'], 'budget_limit_type_active_idx');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('budget_department_id')->nullable()->after('supplier_id')->constrained('departments')->nullOnDelete();
            $table->foreignId('budget_account_id')->nullable()->after('budget_department_id')->constrained('accounts')->nullOnDelete();
            $table->foreignId('budget_commitment_id')->nullable()->after('budget_account_id')->constrained('budget_commitments')->nullOnDelete();
            $table->boolean('budget_overrun_acknowledged')->default(false)->after('budget_commitment_id');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('budget_department_id');
            $table->dropConstrainedForeignId('budget_account_id');
            $table->dropConstrainedForeignId('budget_commitment_id');
            $table->dropColumn('budget_overrun_acknowledged');
        });

        Schema::dropIfExists('budget_approval_limits');
        Schema::dropIfExists('budget_commitment_movements');
        Schema::dropIfExists('budget_commitments');
        Schema::dropIfExists('budget_transfers');
        Schema::dropIfExists('budget_revision_lines');
        Schema::dropIfExists('budget_revisions');
        Schema::dropIfExists('budget_lines');
        Schema::dropIfExists('budget_periods');
        Schema::dropIfExists('budgets');
    }
};
