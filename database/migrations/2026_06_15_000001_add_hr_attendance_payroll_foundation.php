<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_policy_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->string('value_type')->default('string');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('hr_shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedInteger('grace_minutes')->default(0);
            $table->unsignedInteger('break_minutes')->default(0);
            $table->boolean('is_night_shift')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('employee_shift_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained('hr_shifts')->cascadeOnDelete();
            $table->string('assignment_type')->default('fixed');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->unsignedTinyInteger('priority')->default(10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['employee_id', 'effective_from', 'effective_to'], 'employee_shift_dates_idx');
            $table->index(['department_id', 'effective_from', 'effective_to'], 'department_shift_dates_idx');
        });

        Schema::table('employee_attendance', function (Blueprint $table) {
            $table->foreignId('shift_id')->nullable()->after('employee_id')->constrained('hr_shifts')->nullOnDelete();
            $table->string('source')->default('manual')->after('hours_worked');
            $table->unsignedInteger('late_minutes')->default(0)->after('status');
            $table->unsignedInteger('early_exit_minutes')->default(0)->after('late_minutes');
            $table->unsignedInteger('overtime_minutes')->default(0)->after('early_exit_minutes');
            $table->string('review_status')->default('pending')->after('overtime_minutes');
            $table->foreignId('reviewed_by')->nullable()->after('review_status')->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable()->after('reviewed_by');
            $table->index(['date', 'review_status'], 'attendance_review_idx');
        });

        Schema::create('attendance_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_attendance_id')->constrained('employee_attendance')->cascadeOnDelete();
            $table->string('exception_type');
            $table->text('message');
            $table->string('status')->default('open');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();
            $table->unique(['employee_attendance_id', 'exception_type'], 'attendance_exception_unique');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->string('salary_type')->default('monthly')->after('basic_salary');
            $table->string('payment_method')->default('bank')->after('salary_type');
            $table->string('mobile_money_number')->nullable()->after('bank_account');
            $table->string('tax_identification_number')->nullable()->after('tin_number');
            $table->string('tax_residency_status')->default('resident')->after('tax_identification_number');
            $table->boolean('paye_exempt')->default(false)->after('tax_residency_status');
            $table->decimal('tax_relief_amount', 14, 2)->default(0)->after('paye_exempt');
            $table->decimal('employee_ssnit_rate', 7, 4)->default(5.5)->after('ssnit_number');
            $table->decimal('employer_ssnit_rate', 7, 4)->default(13)->after('employee_ssnit_rate');
            $table->string('pension_scheme')->nullable()->after('employer_ssnit_rate');
            $table->longText('default_allowances')->nullable()->after('pension_scheme');
            $table->longText('default_deductions')->nullable()->after('default_allowances');
        });

        Schema::create('payroll_tax_tables', function (Blueprint $table) {
            $table->id();
            $table->string('country_code', 2);
            $table->string('name');
            $table->string('tax_type')->default('paye');
            $table->string('period_basis');
            $table->string('resident_type');
            $table->string('currency', 3)->default('GHS');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->decimal('flat_rate_percent', 7, 4)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['country_code', 'tax_type', 'period_basis', 'resident_type'], 'tax_table_lookup_idx');
        });

        Schema::create('payroll_tax_bands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_tax_table_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('band_order');
            $table->string('band_label');
            $table->decimal('lower_bound', 16, 2)->default(0);
            $table->decimal('upper_bound', 16, 2)->nullable();
            $table->decimal('band_amount', 16, 2)->nullable();
            $table->decimal('rate_percent', 7, 4);
            $table->decimal('fixed_tax_amount', 16, 2)->default(0);
            $table->decimal('cumulative_tax', 16, 2)->default(0);
            $table->boolean('is_excess_band')->default(false);
            $table->timestamps();
            $table->unique(['payroll_tax_table_id', 'band_order'], 'tax_band_order_unique');
        });

        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->string('pay_period')->unique();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status')->default('draft');
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::table('payroll_records', function (Blueprint $table) {
            $table->foreignId('payroll_run_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->decimal('taxable_allowances', 14, 2)->default(0)->after('allowances');
            $table->decimal('non_taxable_allowances', 14, 2)->default(0)->after('taxable_allowances');
            $table->decimal('gross_taxable_income', 14, 2)->default(0)->after('gross_pay');
            $table->decimal('other_pre_tax_deductions', 14, 2)->default(0)->after('ssnit_employer');
            $table->decimal('tax_reliefs', 14, 2)->default(0)->after('other_pre_tax_deductions');
            $table->decimal('chargeable_income', 14, 2)->default(0)->after('tax_reliefs');
            $table->decimal('total_deductions', 14, 2)->default(0)->after('other_deductions');
            $table->decimal('overtime_pay', 14, 2)->default(0)->after('basic_salary');
            $table->decimal('attendance_deductions', 14, 2)->default(0)->after('other_deductions');
            $table->longText('calculation_snapshot')->nullable()->after('net_pay');
        });

        Schema::create('payroll_payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_record_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('generated');
            $table->dateTime('generated_at');
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('released_at')->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->longText('snapshot_json');
            $table->timestamps();
        });

        Schema::create('payroll_tax_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payroll_payslip_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payroll_record_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_tax_table_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('gross_taxable_income', 16, 2);
            $table->decimal('pre_tax_deductions', 16, 2)->default(0);
            $table->decimal('reliefs_total', 16, 2)->default(0);
            $table->decimal('chargeable_income', 16, 2);
            $table->decimal('tax_amount', 16, 2);
            $table->longText('calculation_snapshot_json');
            $table->dateTime('calculated_at');
            $table->foreignId('calculated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_tax_calculations');
        Schema::dropIfExists('payroll_payslips');
        Schema::table('payroll_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payroll_run_id');
            $table->dropColumn(['taxable_allowances', 'non_taxable_allowances', 'gross_taxable_income', 'other_pre_tax_deductions', 'tax_reliefs', 'chargeable_income', 'total_deductions', 'overtime_pay', 'attendance_deductions', 'calculation_snapshot']);
        });
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('payroll_tax_bands');
        Schema::dropIfExists('payroll_tax_tables');
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['salary_type', 'payment_method', 'mobile_money_number', 'tax_identification_number', 'tax_residency_status', 'paye_exempt', 'tax_relief_amount', 'employee_ssnit_rate', 'employer_ssnit_rate', 'pension_scheme', 'default_allowances', 'default_deductions']);
        });
        Schema::dropIfExists('attendance_exceptions');
        Schema::table('employee_attendance', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shift_id');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['source', 'late_minutes', 'early_exit_minutes', 'overtime_minutes', 'review_status', 'reviewed_at']);
        });
        Schema::dropIfExists('employee_shift_assignments');
        Schema::dropIfExists('hr_shifts');
        Schema::dropIfExists('hr_policy_settings');
    }
};
