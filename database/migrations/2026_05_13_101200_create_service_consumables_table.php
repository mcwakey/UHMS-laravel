<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('service_consumables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('service_catalog')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('default_quantity', 14, 4)->default(1);
            $table->boolean('is_required')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['service_id', 'product_id']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_consumables');
    }
};
