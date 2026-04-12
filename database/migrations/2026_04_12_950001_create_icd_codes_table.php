<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('icd_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('description');
            $table->string('category')->nullable();
            $table->string('chapter')->nullable();
            $table->boolean('is_billable')->default(true);
            $table->timestamps();

            $table->index('code');
            $table->index('description');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('icd_codes');
    }
};
