<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archived_patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->nullable()->unique()->constrained('patients')->nullOnDelete();
            $table->string('patient_number')->index();
            $table->string('full_name')->index();
            $table->string('status_before_archive', 30)->nullable();
            $table->timestamp('last_activity_at')->nullable()->index();
            $table->dateTime('archived_at')->index();
            $table->string('reason')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archived_patients');
    }
};
