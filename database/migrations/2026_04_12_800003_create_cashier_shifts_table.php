<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashier_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->date('shift_date');
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->decimal('opening_balance', 10, 2)->default(0);
            $table->decimal('expected_closing', 10, 2)->nullable();
            $table->decimal('actual_closing', 10, 2)->nullable();
            $table->decimal('variance', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('open'); // open, closed, verified
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['user_id', 'shift_date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashier_shifts');
    }
};
