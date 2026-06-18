<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * External Integrations Phase 2 — additive schema.
 *
 * Adds queue/retry + status-reconciliation fields to SMS tables, webhook
 * signature + provider-health fields to integration_providers, and two new
 * tables: sms_notification_events (automatic-event dedup) and
 * payment_request_links (secure payment request foundation).
 *
 * Additive only. MariaDB-10.1 safe (longText for JSON, short named indexes).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_messages', function (Blueprint $table) {
            $table->dateTime('queued_at')->nullable()->after('scheduled_at');
            $table->unsignedInteger('retry_count')->default(0)->after('error_message');
            $table->unsignedInteger('max_retries')->default(3)->after('retry_count');
            $table->dateTime('last_retry_at')->nullable()->after('max_retries');
            $table->dateTime('next_retry_at')->nullable()->after('last_retry_at');
            $table->dateTime('provider_status_checked_at')->nullable()->after('next_retry_at');
        });

        Schema::table('sms_message_recipients', function (Blueprint $table) {
            $table->unsignedInteger('retry_count')->default(0)->after('error_message');
            $table->dateTime('last_retry_at')->nullable()->after('retry_count');
            $table->dateTime('next_retry_at')->nullable()->after('last_retry_at');
            $table->dateTime('provider_status_checked_at')->nullable()->after('next_retry_at');
        });

        Schema::table('integration_providers', function (Blueprint $table) {
            $table->boolean('require_signature')->default(false)->after('webhook_secret_hint');
            $table->boolean('allow_unsigned_sandbox_callbacks')->default(true)->after('require_signature');
            $table->string('signature_header')->nullable()->after('allow_unsigned_sandbox_callbacks');
            $table->dateTime('last_success_at')->nullable()->after('last_test_message');
            $table->dateTime('last_failure_at')->nullable()->after('last_success_at');
            $table->text('last_error_message')->nullable()->after('last_failure_at');
        });

        Schema::create('sms_notification_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 50);
            $table->string('source_type', 120)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('template_id')->nullable()->constrained('sms_templates')->nullOnDelete();
            $table->foreignId('sms_message_id')->nullable()->constrained('sms_messages')->nullOnDelete();
            $table->string('recipient_phone', 40)->nullable();
            $table->string('status', 20)->default('pending'); // pending|sent|failed|skipped
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('triggered_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('failed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->longText('metadata_snapshot')->nullable();
            $table->timestamps();

            $table->index(['event_type', 'source_type', 'source_id'], 'sms_evt_type_source_idx');
            $table->index(['status', 'created_at'], 'sms_evt_status_created_idx');
        });

        Schema::create('payment_request_links', function (Blueprint $table) {
            $table->id();
            $table->uuid('link_uuid')->unique();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->unsignedBigInteger('visit_id')->nullable();
            $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->string('payer_type', 30)->nullable();
            $table->unsignedBigInteger('payer_id')->nullable();
            $table->decimal('amount', 18, 2)->default(0);
            $table->string('currency', 3)->default('GHS');
            $table->string('status', 20)->default('active'); // active|used|expired|cancelled
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('used_at')->nullable();
            $table->foreignId('payment_provider_transaction_id')->nullable()
                ->constrained('payment_provider_transactions', 'id', 'prl_txn_fk')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->longText('metadata_snapshot')->nullable();
            $table->timestamps();

            $table->index(['status', 'expires_at'], 'prl_status_expires_idx');
            $table->index('invoice_id', 'prl_invoice_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_request_links');
        Schema::dropIfExists('sms_notification_events');

        Schema::table('integration_providers', function (Blueprint $table) {
            $table->dropColumn([
                'require_signature', 'allow_unsigned_sandbox_callbacks', 'signature_header',
                'last_success_at', 'last_failure_at', 'last_error_message',
            ]);
        });

        Schema::table('sms_message_recipients', function (Blueprint $table) {
            $table->dropColumn(['retry_count', 'last_retry_at', 'next_retry_at', 'provider_status_checked_at']);
        });

        Schema::table('sms_messages', function (Blueprint $table) {
            $table->dropColumn([
                'queued_at', 'retry_count', 'max_retries', 'last_retry_at',
                'next_retry_at', 'provider_status_checked_at',
            ]);
        });
    }
};
