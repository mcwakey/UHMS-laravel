<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('priority')->default('normal')->after('visit_type');
            $table->text('chief_complaint')->nullable()->after('priority');
            $table->string('consultation_mode')->default('in_person')->after('chief_complaint');
            $table->foreignId('visit_insurance_id')->nullable()->after('consultation_mode')
                ->constrained('patient_insurances')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['visit_insurance_id']);
            $table->dropColumn(['priority', 'chief_complaint', 'consultation_mode', 'visit_insurance_id']);
        });
    }
};
