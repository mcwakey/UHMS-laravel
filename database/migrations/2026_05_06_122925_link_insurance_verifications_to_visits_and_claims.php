<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->foreignId('insurance_verification_id')
                ->nullable()
                ->after('visit_insurance_id')
                ->constrained('insurance_verifications')
                ->nullOnDelete();
        });

        Schema::table('claims', function (Blueprint $table) {
            $table->foreignId('insurance_verification_id')
                ->nullable()
                ->after('invoice_id')
                ->constrained('insurance_verifications')
                ->nullOnDelete();
            // Denormalized snapshot for legibility on submitted claim documents.
            $table->string('verification_reference', 80)->nullable()->after('insurance_verification_id');
        });
    }

    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropForeign(['insurance_verification_id']);
            $table->dropColumn(['insurance_verification_id', 'verification_reference']);
        });

        Schema::table('visits', function (Blueprint $table) {
            $table->dropForeign(['insurance_verification_id']);
            $table->dropColumn('insurance_verification_id');
        });
    }
};
