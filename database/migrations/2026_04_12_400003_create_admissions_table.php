<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admissions', function (Blueprint $table) {
            $table->id();
            $table->string('admission_number')->unique();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bed_id')->constrained();
            $table->foreignId('admitted_by')->constrained('users');
            $table->text('admitting_diagnosis')->nullable();
            $table->dateTime('admission_date');
            $table->date('expected_discharge_date')->nullable();
            $table->dateTime('actual_discharge_date')->nullable();
            $table->foreignId('discharged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('discharge_summary')->nullable();
            $table->text('discharge_instructions')->nullable();
            $table->string('status')->default('admitted');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('admission_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admissions');
    }
};
