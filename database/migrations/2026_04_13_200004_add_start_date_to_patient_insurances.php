<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add start_date to patient_insurances for validity period tracking
        Schema::table('patient_insurances', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('policy_number');
        });

        // Add SELF type support to insurance_providers
        // (InsuranceType enum will be updated to include SELF)
    }

    public function down(): void
    {
        Schema::table('patient_insurances', function (Blueprint $table) {
            $table->dropColumn('start_date');
        });
    }
};
