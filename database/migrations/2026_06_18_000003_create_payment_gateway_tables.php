<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * External Integrations Phase 1 — Payment Gateway tables.
 *
 * A payment_provider_transaction represents an online / mobile-money payment
 * request BEFORE it becomes a confirmed UHMS Payment. Only a verified, amount-
 * and currency-matched transaction may create/attach a UHMS payment.
 *
 * MariaDB-10.1 safe: longText for payload/metadata, short named indexes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_provider_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('transaction_uuid')->unique();
            $table->foreignId('provider_id')->nullable()->constrained('integration_providers')->nullOnDelete();
            $table->string('provider_code', 60)->nullable();
            $table->string('payment_reference', 80)->unique();      // our internal reference
            $table->string('provider_transaction_id')->nullable();  // provider's id
            $table->string('external_reference')->nullable();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->unsignedBigInteger('visit_id')->nullable();
            $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->string('payer_name')->nullable();
            $table->string('payer_phone', 40)->nullable();
            $table->string('payer_email')->nullable();
            $table->decimal('amount', 18, 2)->default(0);
            $table->string('currency', 3)->default('GHS');
            $table->string('payment_method', 40)->nullable();
            $table->string('status', 30)->default('draft');
            $table->string('provider_status', 60)->nullable();
            $table->dateTime('initiated_at')->nullable();
            $table->dateTime('authorized_at')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('failed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->dateTime('expired_at')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->foreignId('uhms_payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->unsignedBigInteger('accounting_posting_attempt_id')->nullable();
            $table->string('error_code', 80)->nullable();
            $table->text('error_message')->nullable();
            $table->longText('metadata_snapshot')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at'], 'pay_txn_status_created_idx');
            $table->index(['provider_code', 'status'], 'pay_txn_code_status_idx');
            $table->index('provider_transaction_id', 'pay_txn_provider_txn_idx');
            $table->index('invoice_id', 'pay_txn_invoice_idx');
        });

        Schema::create('payment_provider_callbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->nullable()->constrained('integration_providers')->nullOnDelete();
            $table->string('provider_code', 60)->nullable();
            $table->string('event_type', 60)->nullable();
            $table->string('provider_transaction_id')->nullable();
            $table->string('payment_reference', 80)->nullable();
            $table->boolean('signature_valid')->default(false);
            $table->boolean('processed')->default(false);
            $table->dateTime('processed_at')->nullable();
            $table->text('processing_error')->nullable();
            $table->longText('raw_payload')->nullable();
            $table->longText('headers_snapshot')->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->timestamps();

            $table->index(['provider_code', 'processed'], 'pay_cb_code_processed_idx');
            $table->index('provider_transaction_id', 'pay_cb_provider_txn_idx');
            $table->index('payment_reference', 'pay_cb_payment_ref_idx');
        });

        Schema::create('payment_provider_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_provider_transaction_id')->nullable()
                ->constrained('payment_provider_transactions', 'id', 'pay_att_txn_fk')->cascadeOnDelete();
            $table->foreignId('provider_id')->nullable()
                ->constrained('integration_providers', 'id', 'pay_att_provider_fk')->nullOnDelete();
            $table->string('attempt_type', 30);  // initiate|verify|status_check|callback_process|refund|cancel
            $table->string('status', 30)->default('started');
            $table->longText('request_payload_snapshot')->nullable();
            $table->longText('response_payload_snapshot')->nullable();
            $table->integer('http_status')->nullable();
            $table->string('error_code', 80)->nullable();
            $table->text('error_message')->nullable();
            $table->dateTime('started_at');
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->index(['payment_provider_transaction_id', 'attempt_type'], 'pay_att_txn_type_idx');
        });

        Schema::create('payment_provider_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_provider_transaction_id')->nullable()
                ->constrained('payment_provider_transactions', 'id', 'pay_ref_txn_fk')->cascadeOnDelete();
            $table->foreignId('provider_id')->nullable()
                ->constrained('integration_providers', 'id', 'pay_ref_provider_fk')->nullOnDelete();
            $table->string('refund_reference', 80)->unique();
            $table->string('provider_refund_id')->nullable();
            $table->decimal('amount', 18, 2)->default(0);
            $table->string('currency', 3)->default('GHS');
            $table->string('status', 30)->default('draft');
            $table->text('reason')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('requested_at')->nullable();
            $table->dateTime('processed_at')->nullable();
            $table->dateTime('failed_at')->nullable();
            $table->unsignedBigInteger('uhms_refund_id')->nullable();
            $table->longText('metadata_snapshot')->nullable();
            $table->timestamps();

            $table->index(['payment_provider_transaction_id', 'status'], 'pay_ref_txn_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_provider_refunds');
        Schema::dropIfExists('payment_provider_attempts');
        Schema::dropIfExists('payment_provider_callbacks');
        Schema::dropIfExists('payment_provider_transactions');
    }
};
