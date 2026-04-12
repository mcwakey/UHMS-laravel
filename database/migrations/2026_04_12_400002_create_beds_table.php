<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ward_id')->constrained()->cascadeOnDelete();
            $table->string('bed_number');
            $table->string('bed_type')->default('standard');
            $table->string('status')->default('available');
            $table->decimal('daily_rate', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['ward_id', 'bed_number']);
            $table->index('status');
            $table->index('bed_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beds');
    }
};
