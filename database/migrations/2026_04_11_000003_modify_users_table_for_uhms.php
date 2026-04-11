<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop the default 'name' column and replace with first/last name
            $table->dropColumn('name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->after('id');
            $table->string('last_name')->after('first_name');
            $table->string('phone')->nullable()->after('email');
            $table->string('avatar')->nullable()->after('password');
            $table->string('gender')->nullable()->after('avatar'); // male, female
            $table->date('date_of_birth')->nullable()->after('gender');
            $table->string('status')->default('active')->after('date_of_birth'); // active, inactive, suspended
            $table->string('employee_id')->nullable()->unique()->after('status');
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete()->after('employee_id');
            $table->foreignId('designation_id')->nullable()->constrained()->nullOnDelete()->after('department_id');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropForeign(['department_id']);
            $table->dropForeign(['designation_id']);
            $table->dropColumn([
                'first_name',
                'last_name',
                'phone',
                'avatar',
                'gender',
                'date_of_birth',
                'status',
                'employee_id',
                'department_id',
                'designation_id',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->after('id');
        });
    }
};
