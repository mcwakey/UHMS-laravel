<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 60)->nullable()->unique();
            $table->string('product_type', 30);            // ProductType enum
            $table->string('unit', 30)->default('unit');
            $table->text('description')->nullable();
            $table->decimal('reorder_level', 14, 4)->nullable();
            $table->decimal('default_cost', 12, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('product_type');
            $table->index('is_active');
        });

        Schema::create('product_department', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['product_id', 'department_id']);
            $table->index('department_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_department');
        Schema::dropIfExists('products');
    }
};
