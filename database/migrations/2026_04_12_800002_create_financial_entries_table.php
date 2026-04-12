<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entry_number')->unique();
            $table->foreignId('category_id')->constrained('account_categories');
            $table->string('type'); // income, expense
            $table->decimal('amount', 12, 2);
            $table->string('payment_method')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('receipt_number')->nullable();
            $table->text('description');
            $table->date('entry_date');
            $table->foreignId('recorded_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'entry_date']);
            $table->index(['category_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_entries');
    }
};
