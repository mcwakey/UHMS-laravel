<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * External Integrations Phase 1 — SMS Gateway tables.
 *
 * MariaDB-10.1 safe: longText for payload/metadata, short named indexes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('language', 5)->default('en');
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('message_uuid')->unique();
            $table->foreignId('provider_id')->nullable()->constrained('integration_providers')->nullOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('sms_templates')->nullOnDelete();
            $table->string('sender_id')->nullable();
            $table->text('message_body');
            $table->string('message_type', 40)->default('manual');
            $table->string('status', 20)->default('draft');
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('failed_at')->nullable();
            $table->string('provider_batch_reference')->nullable();
            $table->string('error_code', 80)->nullable();
            $table->text('error_message')->nullable();
            $table->longText('metadata_snapshot')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at'], 'sms_msg_status_created_idx');
            $table->index(['message_type', 'status'], 'sms_msg_type_status_idx');
            $table->index('provider_batch_reference', 'sms_msg_batch_ref_idx');
        });

        Schema::create('sms_message_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sms_message_id')->constrained('sms_messages')->cascadeOnDelete();
            $table->string('recipient_type', 120)->nullable();
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->string('phone_number', 40);
            $table->string('normalized_phone_number', 40);
            $table->string('recipient_name')->nullable();
            $table->string('status', 20)->default('queued');
            $table->string('provider_message_id')->nullable();
            $table->string('provider_status', 60)->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->dateTime('failed_at')->nullable();
            $table->string('error_code', 80)->nullable();
            $table->text('error_message')->nullable();
            $table->longText('metadata_snapshot')->nullable();
            $table->timestamps();

            $table->index(['sms_message_id', 'status'], 'sms_rcpt_msg_status_idx');
            $table->index('provider_message_id', 'sms_rcpt_provider_msg_idx');
            $table->index('normalized_phone_number', 'sms_rcpt_norm_phone_idx');
            $table->index(['recipient_type', 'recipient_id'], 'sms_rcpt_owner_idx');
        });

        Schema::create('sms_delivery_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sms_message_recipient_id')->nullable()
                ->constrained('sms_message_recipients')->nullOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained('integration_providers')->nullOnDelete();
            $table->string('provider_message_id')->nullable();
            $table->string('provider_status', 60)->nullable();
            $table->string('status', 20)->default('delivered');
            $table->dateTime('reported_at')->nullable();
            $table->longText('raw_payload')->nullable();
            $table->timestamps();

            $table->index('provider_message_id', 'sms_dlr_provider_msg_idx');
        });

        Schema::create('sms_provider_callbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->nullable()->constrained('integration_providers')->nullOnDelete();
            $table->string('provider_code', 60)->nullable();
            $table->string('event_type', 60)->nullable();
            $table->string('provider_message_id')->nullable();
            $table->boolean('signature_valid')->default(false);
            $table->boolean('processed')->default(false);
            $table->dateTime('processed_at')->nullable();
            $table->text('processing_error')->nullable();
            $table->longText('raw_payload')->nullable();
            $table->longText('headers_snapshot')->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->timestamps();

            $table->index(['provider_code', 'processed'], 'sms_cb_code_processed_idx');
            $table->index('provider_message_id', 'sms_cb_provider_msg_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_provider_callbacks');
        Schema::dropIfExists('sms_delivery_reports');
        Schema::dropIfExists('sms_message_recipients');
        Schema::dropIfExists('sms_messages');
        Schema::dropIfExists('sms_templates');
    }
};
