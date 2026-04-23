<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->string('admission_type')->default('admission')->after('status'); // admission | detention
            $table->foreignId('admission_fee_service_id')
                ->nullable()
                ->after('admission_type')
                ->constrained('service_catalog')
                ->nullOnDelete()
                ->comment('Service mapped to one-time admission/detention fee');
            $table->foreignId('consumable_fee_service_id')
                ->nullable()
                ->after('admission_fee_service_id')
                ->constrained('service_catalog')
                ->nullOnDelete()
                ->comment('Service mapped to daily consumable fee');
        });
    }

    public function down(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->dropForeign(['admission_fee_service_id']);
            $table->dropForeign(['consumable_fee_service_id']);
            $table->dropColumn(['admission_type', 'admission_fee_service_id', 'consumable_fee_service_id']);
        });
    }
};
