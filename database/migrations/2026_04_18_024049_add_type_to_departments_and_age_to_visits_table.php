<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        try {
            Schema::table('departments', function (Blueprint $table) {
                $table->string('type')->nullable()->after('description');
            });
        } catch (\Exception $e) {
            // Column may already exist
        }

        try {
            Schema::table('visits', function (Blueprint $table) {
                $table->integer('patient_age')->nullable()->after('patient_id');
            });
        } catch (\Exception $e) {
            // Column may already exist
        }

        // Add insurance_covered and patient_payable to visit_services if missing
        try {
            Schema::table('visit_services', function (Blueprint $table) {
                $table->decimal('insurance_covered', 10, 2)->default(0)->after('total_price');
                $table->decimal('patient_payable', 10, 2)->default(0)->after('insurance_covered');
            });
        } catch (\Exception $e) {
            // Columns may already exist
        }
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('visits', function (Blueprint $table) {
            $table->dropColumn('patient_age');
        });

        if (Schema::hasColumn('visit_services', 'insurance_covered')) {
            try {
                Schema::table('visit_services', function (Blueprint $table) {
                    $table->dropColumn(['insurance_covered', 'patient_payable']);
                });
            } catch (\Exception $e) {
                // ignore
            }
        }
    }
};
