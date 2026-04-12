<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('gender')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('phone');
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('position');
            $table->date('hire_date');
            $table->decimal('basic_salary', 10, 2)->default(0);
            $table->string('bank_name')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('bank_branch')->nullable();
            $table->string('ssnit_number')->nullable();
            $table->string('tin_number')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index('department_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
