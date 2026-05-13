<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('type')->default('store'); // store, pharmacy, ward, lab, etc.
            $table->foreignId('department_id')->nullable()
                ->constrained('departments')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Seed two default locations.
        DB::table('stock_locations')->insert([
            [
                'name'       => 'Main Store',
                'type'       => 'store',
                'is_active'  => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'Pharmacy',
                'type'       => 'pharmacy',
                'is_active'  => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_locations');
    }
};
