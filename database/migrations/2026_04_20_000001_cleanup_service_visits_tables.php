<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add department_type to service_catalog, remove nhis_price and is_nhis_covered
        try {
            Schema::table('service_catalog', function (Blueprint $table) {
                $table->string('department_type')->nullable()->after('department_id');
            });
        } catch (\Exception $e) { /* already exists */ }

        try {
            Schema::table('service_catalog', function (Blueprint $table) {
                $table->dropColumn('nhis_price');
            });
        } catch (\Exception $e) { /* column missing */ }

        try {
            Schema::table('service_catalog', function (Blueprint $table) {
                $table->dropColumn('is_nhis_covered');
            });
        } catch (\Exception $e) { /* column missing */ }

        // 2. Remove department_id from visits
        try {
            Schema::table('visits', function (Blueprint $table) {
                $table->dropForeign(['department_id']);
            });
        } catch (\Exception $e) { /* constraint missing */ }

        try {
            Schema::table('visits', function (Blueprint $table) {
                $table->dropColumn('department_id');
            });
        } catch (\Exception $e) { /* column missing */ }

        // 3. Ensure visit_services has insurance_covered and patient_payable columns
        // Using try/catch per-column to handle MariaDB's lack of generation_expression
        try {
            Schema::table('visit_services', function (Blueprint $table) {
                $table->decimal('insurance_covered', 10, 2)->default(0)->after('total_price');
            });
        } catch (\Exception $e) { /* already exists */ }

        try {
            Schema::table('visit_services', function (Blueprint $table) {
                $table->decimal('patient_payable', 10, 2)->default(0)->after('insurance_covered');
            });
        } catch (\Exception $e) { /* already exists */ }
    }

    public function down(): void
    {
        // Restore nhis_price and is_nhis_covered in service_catalog
        try {
            Schema::table('service_catalog', function (Blueprint $table) {
                $table->decimal('nhis_price', 10, 2)->nullable();
                $table->boolean('is_nhis_covered')->default(false);
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('service_catalog', function (Blueprint $table) {
                $table->dropColumn('department_type');
            });
        } catch (\Exception $e) {}

        // Restore department_id in visits
        try {
            Schema::table('visits', function (Blueprint $table) {
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            });
        } catch (\Exception $e) {}
    }
};
