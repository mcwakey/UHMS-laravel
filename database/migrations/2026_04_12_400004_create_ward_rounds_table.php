<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ward_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->constrained('users');
            $table->dateTime('round_date');
            $table->text('notes');
            $table->text('instructions')->nullable();
            $table->timestamps();

            $table->index('round_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ward_rounds');
    }
};
