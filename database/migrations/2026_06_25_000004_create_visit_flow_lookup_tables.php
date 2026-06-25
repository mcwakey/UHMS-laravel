<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dynamic lookup tables backing the visit-flow vocabulary. Privileged users can
 * extend these at runtime; the `code` column is what the visits row stores.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['visit_sources', 'attendance_classes'] as $tableName) {
            if (Schema::hasTable($tableName)) {
                continue;
            }

            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->string('description')->nullable();
                $table->string('color')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_classes');
        Schema::dropIfExists('visit_sources');
    }
};
