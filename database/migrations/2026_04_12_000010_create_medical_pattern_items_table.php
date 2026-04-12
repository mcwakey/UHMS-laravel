<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_pattern_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_pattern_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // complaint, diagnosis, treatment, prescription_item
            $table->longText('data'); // JSON stored as longText for MariaDB compat
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_pattern_items');
    }
};
