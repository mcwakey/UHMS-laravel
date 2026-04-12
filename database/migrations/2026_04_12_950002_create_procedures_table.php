<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procedures', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('category')->default('other'); // surgical, diagnostic, therapeutic, other
            $table->text('description')->nullable();
            $table->decimal('default_price', 10, 2)->default(0);
            $table->decimal('nhis_price', 10, 2)->nullable();
            $table->boolean('requires_consent')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('department_id');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procedures');
    }
};
