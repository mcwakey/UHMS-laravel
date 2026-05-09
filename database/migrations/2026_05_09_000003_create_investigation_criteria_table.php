<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investigation_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('service_catalog')->cascadeOnDelete();
            $table->foreignId('header_id')->nullable()->constrained('investigation_headers')->nullOnDelete();
            $table->string('name');
            $table->string('unit', 50)->nullable();
            $table->string('reference_range', 191)->nullable();
            $table->string('default_value', 191)->nullable();
            // input_type: text | number | select | textarea | boolean
            $table->string('input_type', 30)->default('text');
            $table->text('options')->nullable(); // JSON-encoded; cast to array on the model. (MariaDB-compatible)
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['service_id', 'header_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investigation_criteria');
    }
};
