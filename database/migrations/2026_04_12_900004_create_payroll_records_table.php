<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('pay_period');
            $table->decimal('basic_salary', 10, 2);
            $table->decimal('allowances', 10, 2)->default(0);
            $table->decimal('gross_pay', 10, 2);
            $table->decimal('ssnit_employee', 10, 2)->default(0);
            $table->decimal('ssnit_employer', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('other_deductions', 10, 2)->default(0);
            $table->decimal('net_pay', 10, 2);
            $table->string('status')->default('draft');
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->datetime('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'pay_period']);
            $table->index('pay_period');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_records');
    }
};
