<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('lab_test_categories')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('normal_range')->nullable();
            $table->string('unit')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_tests');
    }
};
