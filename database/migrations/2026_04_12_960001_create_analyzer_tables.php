<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analyzers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('model')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('protocol', 20)->default('hl7'); // hl7, astm
            $table->string('connection_type', 20)->default('tcp'); // tcp, serial
            $table->string('ip_address', 45)->nullable();
            $table->unsignedInteger('port')->nullable();
            $table->string('com_port', 20)->nullable();
            $table->unsignedInteger('baud_rate')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamp('last_connected_at')->nullable();
            $table->timestamps();
        });

        Schema::create('analyzer_test_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analyzer_id')->constrained()->cascadeOnDelete();
            $table->string('analyzer_test_code', 50);
            $table->foreignId('lab_test_id')->constrained()->cascadeOnDelete();
            $table->decimal('unit_conversion_factor', 10, 4)->nullable();
            $table->timestamps();

            $table->unique(['analyzer_id', 'analyzer_test_code'], 'analyzer_test_code_unique');
        });

        Schema::create('analyzer_raw_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analyzer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('protocol', 20);
            $table->string('direction', 10)->default('inbound'); // inbound, outbound
            $table->longText('content');
            $table->string('content_hash', 64)->index();
            $table->string('processing_status', 20)->default('received'); // received, processing, processed, failed, duplicate
            $table->unsignedTinyInteger('processing_attempts')->default(0);
            $table->text('error_message')->nullable();
            $table->string('sample_id', 100)->nullable()->index();
            $table->timestamp('received_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['analyzer_id', 'processing_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analyzer_raw_messages');
        Schema::dropIfExists('analyzer_test_mappings');
        Schema::dropIfExists('analyzers');
    }
};
