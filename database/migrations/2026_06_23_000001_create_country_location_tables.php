<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('iso2', 2)->unique();
            $table->string('iso3', 3)->nullable()->unique();
            $table->string('dial_code', 10)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('country_regions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained('countries')->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 20)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['country_id', 'name']);
            $table->unique(['country_id', 'code']);
            $table->index(['country_id', 'is_active']);
        });

        Schema::create('country_cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_region_id')->constrained('country_regions')->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 40)->default('city');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['country_region_id', 'name']);
            $table->index(['country_region_id', 'type', 'is_active']);
        });

        Schema::create('country_towns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_city_id')->constrained('country_cities')->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['country_city_id', 'name']);
            $table->index(['country_city_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('country_towns');
        Schema::dropIfExists('country_cities');
        Schema::dropIfExists('country_regions');
        Schema::dropIfExists('countries');
    }
};
