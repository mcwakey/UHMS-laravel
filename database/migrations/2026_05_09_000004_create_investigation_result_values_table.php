<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investigation_result_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_result_id')->constrained('lab_results')->cascadeOnDelete();
            $table->foreignId('criteria_id')->nullable()->constrained('investigation_criteria')->nullOnDelete();
            // Snapshot fields (to preserve criteria definition at time of result entry)
            $table->string('name');
            $table->string('value', 500)->nullable();
            $table->string('unit', 50)->nullable();
            $table->string('reference_range', 191)->nullable();
            $table->string('flag', 30)->nullable(); // normal | high | low | abnormal
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['lab_result_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investigation_result_values');
    }
};
