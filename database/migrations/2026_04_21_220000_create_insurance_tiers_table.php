<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insurance_provider_id')->constrained()->cascadeOnDelete();

            $table->string('name');                        // Gold, Silver, Standard, Basic …
            $table->string('code', 30)->nullable();        // Short code e.g. "GLD"
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false); // Default tier for new enrolments
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);

            // ── Base constraints (apply to ALL members unless holder/beneficiary overrides set) ──
            $table->decimal('coverage_percentage', 5, 2)->default(100);
            $table->decimal('per_visit_limit', 12, 2)->nullable();
            $table->decimal('annual_limit', 12, 2)->nullable();
            $table->decimal('max_per_month', 12, 2)->nullable();
            $table->unsignedSmallInteger('max_visits_per_month')->nullable();

            // ── Family & interval settings ────────────────────────────────────────────────────
            $table->unsignedSmallInteger('min_visit_interval_days')->nullable(); // Min days between covered visits
            $table->unsignedSmallInteger('max_beneficiaries')->nullable();       // Max beneficiaries per card holder (null = unlimited)

            // ── Card-holder specific overrides (null = fall back to base) ─────────────────────
            $table->decimal('holder_per_visit_limit', 12, 2)->nullable();
            $table->decimal('holder_annual_limit', 12, 2)->nullable();
            $table->decimal('holder_max_per_month', 12, 2)->nullable();
            $table->unsignedSmallInteger('holder_max_visits_per_month')->nullable();

            // ── Beneficiary specific overrides (null = fall back to base) ─────────────────────
            $table->decimal('beneficiary_per_visit_limit', 12, 2)->nullable();
            $table->decimal('beneficiary_annual_limit', 12, 2)->nullable();
            $table->decimal('beneficiary_max_per_month', 12, 2)->nullable();
            $table->unsignedSmallInteger('beneficiary_max_visits_per_month')->nullable();

            $table->timestamps();
        });

        // ── Data migration: create a 'Standard' tier for every existing provider ──────────────
        // Copies the existing limit values so nothing is lost.
        DB::table('insurance_providers')->get()->each(function ($provider) {
            DB::table('insurance_tiers')->insert([
                'insurance_provider_id' => $provider->id,
                'name'                  => 'Standard',
                'code'                  => 'STD',
                'is_default'            => true,
                'is_active'             => true,
                'sort_order'            => 0,
                'coverage_percentage'   => $provider->coverage_percentage ?? 100,
                'per_visit_limit'       => $provider->per_visit_limit       ?? null,
                'annual_limit'          => $provider->annual_limit           ?? null,
                'max_per_month'         => $provider->max_per_month          ?? null,
                'max_visits_per_month'  => $provider->max_visits_per_month   ?? null,
                'created_at'            => now(),
                'updated_at'            => now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_tiers');
    }
};
