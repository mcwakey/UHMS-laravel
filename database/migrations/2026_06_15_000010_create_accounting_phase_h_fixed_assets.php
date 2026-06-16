<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('depreciation_method', 32)->default('straight_line');
            $table->unsignedInteger('useful_life_months')->default(60);
            $table->decimal('default_residual_rate', 8, 4)->default(0);
            $table->foreignId('asset_cost_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('accumulated_depreciation_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('depreciation_expense_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('disposal_gain_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('disposal_loss_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('asset_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_number')->unique();
            $table->foreignId('asset_category_id')->constrained('asset_categories')->restrictOnDelete();
            $table->foreignId('asset_location_id')->nullable()->constrained('asset_locations')->nullOnDelete();
            $table->foreignId('custodian_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('acquisition_date');
            $table->date('placed_in_service_date')->nullable();
            $table->decimal('cost', 18, 2);
            $table->decimal('residual_value', 18, 2)->default(0);
            $table->unsignedInteger('useful_life_months');
            $table->string('depreciation_method', 32)->default('straight_line');
            $table->decimal('accumulated_depreciation', 18, 2)->default(0);
            $table->decimal('impairment_amount', 18, 2)->default(0);
            $table->string('status', 32)->default('draft');
            $table->foreignId('capitalization_journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->dateTime('capitalized_at')->nullable();
            $table->foreignId('capitalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('disposed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'placed_in_service_date'], 'fixed_asset_status_service_idx');
            $table->index(['asset_category_id', 'status'], 'fixed_asset_category_status_idx');
        });

        Schema::create('asset_acquisitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixed_asset_id')->constrained('fixed_assets')->cascadeOnDelete();
            $table->string('source_type', 120)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_reference')->nullable();
            $table->decimal('amount', 18, 2);
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->timestamps();

            $table->index(['source_type', 'source_id'], 'asset_acq_source_idx');
        });

        Schema::create('asset_custody_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixed_asset_id')->constrained('fixed_assets')->cascadeOnDelete();
            $table->foreignId('custodian_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('asset_location_id')->nullable()->constrained('asset_locations')->nullOnDelete();
            $table->date('assigned_at');
            $table->date('released_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('asset_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixed_asset_id')->constrained('fixed_assets')->cascadeOnDelete();
            $table->foreignId('from_location_id')->nullable()->constrained('asset_locations')->nullOnDelete();
            $table->foreignId('to_location_id')->nullable()->constrained('asset_locations')->nullOnDelete();
            $table->foreignId('from_custodian_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('to_custodian_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('transfer_date');
            $table->text('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('asset_depreciation_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accounting_period_id')->constrained('accounting_periods')->restrictOnDelete();
            $table->string('run_number')->unique();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status', 24)->default('posted');
            $table->decimal('total_depreciation', 18, 2)->default(0);
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('accounting_period_id', 'asset_depr_run_period_unique');
        });

        Schema::create('asset_depreciation_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_depreciation_run_id')->constrained('asset_depreciation_runs')->cascadeOnDelete();
            $table->foreignId('fixed_asset_id')->constrained('fixed_assets')->cascadeOnDelete();
            $table->decimal('depreciable_amount', 18, 2);
            $table->decimal('depreciation_amount', 18, 2);
            $table->decimal('accumulated_after', 18, 2);
            $table->timestamps();

            $table->unique(['asset_depreciation_run_id', 'fixed_asset_id'], 'asset_depr_line_run_asset_unique');
        });

        Schema::create('asset_impairments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixed_asset_id')->constrained('fixed_assets')->cascadeOnDelete();
            $table->date('impairment_date');
            $table->decimal('amount', 18, 2);
            $table->text('reason');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('asset_disposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixed_asset_id')->constrained('fixed_assets')->cascadeOnDelete();
            $table->date('disposal_date');
            $table->decimal('proceeds_amount', 18, 2)->default(0);
            $table->decimal('carrying_amount', 18, 2);
            $table->decimal('gain_amount', 18, 2)->default(0);
            $table->decimal('loss_amount', 18, 2)->default(0);
            $table->foreignId('proceeds_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('asset_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixed_asset_id')->constrained('fixed_assets')->cascadeOnDelete();
            $table->date('verification_date');
            $table->string('condition_status', 40)->default('good');
            $table->text('notes')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_verifications');
        Schema::dropIfExists('asset_disposals');
        Schema::dropIfExists('asset_impairments');
        Schema::dropIfExists('asset_depreciation_lines');
        Schema::dropIfExists('asset_depreciation_runs');
        Schema::dropIfExists('asset_transfers');
        Schema::dropIfExists('asset_custody_assignments');
        Schema::dropIfExists('asset_acquisitions');
        Schema::dropIfExists('fixed_assets');
        Schema::dropIfExists('asset_locations');
        Schema::dropIfExists('asset_categories');
    }
};
