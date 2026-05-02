<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_core')->default(false);
            $table->boolean('is_enabled')->default(true);
            $table->string('depends_on')->nullable(); // slug of required module
            $table->string('icon')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_enabled', 'is_core']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
