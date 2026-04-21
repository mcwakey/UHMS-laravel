<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_insurances', function (Blueprint $table) {
            // Tier link — nullable so existing rows aren't broken before data migration
            $table->foreignId('insurance_tier_id')
                ->nullable()
                ->after('insurance_provider_id')
                ->constrained('insurance_tiers')
                ->nullOnDelete();

            // Member type: holder (card owner) or beneficiary (family member)
            $table->enum('member_type', ['holder', 'beneficiary'])
                ->default('holder')
                ->after('insurance_tier_id');

            // Self-referencing FK: for beneficiaries, points to the card holder's patient_insurance row
            $table->unsignedBigInteger('card_holder_insurance_id')
                ->nullable()
                ->after('member_type');

            $table->foreign('card_holder_insurance_id')
                ->references('id')
                ->on('patient_insurances')
                ->nullOnDelete();
        });

        // ── Data migration: point each existing patient_insurance to its provider's Standard tier ──
        DB::table('patient_insurances')->get()->each(function ($pi) {
            $tier = DB::table('insurance_tiers')
                ->where('insurance_provider_id', $pi->insurance_provider_id)
                ->where('is_default', true)
                ->first();

            if ($tier) {
                DB::table('patient_insurances')
                    ->where('id', $pi->id)
                    ->update(['insurance_tier_id' => $tier->id]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('patient_insurances', function (Blueprint $table) {
            $table->dropForeign(['card_holder_insurance_id']);
            $table->dropForeign(['insurance_tier_id']);
            $table->dropColumn(['insurance_tier_id', 'member_type', 'card_holder_insurance_id']);
        });
    }
};
