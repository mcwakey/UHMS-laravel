<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Groups manual stock movements (adjustments, returns, transfers) that were
 * recorded together into one "batch", so the index pages can list a single row
 * per batch and reveal its line items on click.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_number')->nullable()->unique();
            $table->string('type', 30)->index();             // adjustment | return | transfer
            $table->unsignedBigInteger('source_location_id')->nullable();
            $table->unsignedBigInteger('dest_location_id')->nullable();
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('source_location_id')->references('id')->on('stock_locations')->nullOnDelete();
            $table->foreign('dest_location_id')->references('id')->on('stock_locations')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->unsignedBigInteger('stock_batch_id')->nullable()->after('id')->index();
            $table->foreign('stock_batch_id')->references('id')->on('stock_batches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['stock_batch_id']);
            $table->dropColumn('stock_batch_id');
        });

        Schema::dropIfExists('stock_batches');
    }
};
