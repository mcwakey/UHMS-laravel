<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_request_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lab_request_id')->constrained()->cascadeOnDelete();
            $table->text('result_value');
            $table->boolean('is_abnormal')->default(false);
            $table->text('remarks')->nullable();
            $table->foreignId('performed_by')->constrained('users');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('performed_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_results');
    }
};
