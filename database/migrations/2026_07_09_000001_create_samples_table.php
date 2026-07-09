<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('samples', function (Blueprint $table) {
            $table->id();
            $table->string('sample_number', 50)->unique();
            $table->string('barcode', 100)->nullable()->index();

            $table->foreignId('lab_request_id')->constrained()->cascadeOnDelete();

            $table->string('specimen_type', 50);      // config('specimens.types') code
            $table->string('container', 100)->nullable();
            $table->string('status', 20)->default('pending')->index();

            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('collected_at')->nullable();

            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();

            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->string('rejection_reason')->nullable();

            $table->foreignId('disposed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('disposed_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['lab_request_id', 'specimen_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('samples');
    }
};
