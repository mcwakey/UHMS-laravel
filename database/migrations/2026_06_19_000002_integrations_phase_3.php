<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * External Integrations Phase 3 — public payment portal lifecycle, refund/
 * credit-note linkage and the provider go-live checklist.
 *
 * Additive only. MariaDB-10.1 safe (longText for JSON, short named indexes/FKs).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_request_links', function (Blueprint $table) {
            $table->string('public_token', 80)->nullable()->unique()->after('link_uuid');
            $table->string('token_hash', 80)->nullable()->after('public_token');
            $table->unsignedInteger('initiated_count')->default(0)->after('status');
            $table->dateTime('last_initiated_at')->nullable()->after('initiated_count');
            $table->dateTime('last_viewed_at')->nullable()->after('last_initiated_at');
            $table->foreignId('cancelled_by')->nullable()->after('used_at')->constrained('users', 'id', 'prl_cancelled_by_fk')->nullOnDelete();
            $table->dateTime('cancelled_at')->nullable()->after('cancelled_by');
            $table->string('cancel_reason')->nullable()->after('cancelled_at');
            $table->string('success_redirect_url')->nullable()->after('cancel_reason');
            $table->string('failure_redirect_url')->nullable()->after('success_redirect_url');
        });

        Schema::table('payment_provider_refunds', function (Blueprint $table) {
            $table->unsignedBigInteger('uhms_credit_note_id')->nullable()->after('uhms_refund_id');
            $table->index('uhms_credit_note_id', 'pay_ref_credit_note_idx');
        });

        Schema::create('integration_provider_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integration_provider_id')
                ->constrained('integration_providers', 'id', 'ipc_provider_fk')->cascadeOnDelete();
            $table->string('status', 20)->default('pending'); // pending|in_progress|ready|blocked
            $table->boolean('live_ready')->default(false);
            $table->foreignId('finance_signoff_by')->nullable()->constrained('users', 'id', 'ipc_finance_fk')->nullOnDelete();
            $table->dateTime('finance_signoff_at')->nullable();
            $table->foreignId('it_signoff_by')->nullable()->constrained('users', 'id', 'ipc_it_fk')->nullOnDelete();
            $table->dateTime('it_signoff_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users', 'id', 'ipc_approved_fk')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->longText('metadata_snapshot')->nullable();
            $table->timestamps();

            $table->unique('integration_provider_id', 'ipc_provider_unique');
        });

        Schema::create('integration_provider_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_id')
                ->constrained('integration_provider_checklists', 'id', 'ipci_checklist_fk')->cascadeOnDelete();
            $table->string('item_key', 60);
            $table->string('status', 20)->default('pending'); // pending|passed|failed|not_applicable|waived
            $table->boolean('is_required')->default(true);
            $table->string('evidence_reference')->nullable();
            $table->text('notes')->nullable();
            $table->string('waiver_reason')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users', 'id', 'ipci_recorded_fk')->nullOnDelete();
            $table->dateTime('recorded_at')->nullable();
            $table->timestamps();

            $table->unique(['checklist_id', 'item_key'], 'ipci_checklist_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_provider_checklist_items');
        Schema::dropIfExists('integration_provider_checklists');

        Schema::table('payment_provider_refunds', function (Blueprint $table) {
            $table->dropIndex('pay_ref_credit_note_idx');
            $table->dropColumn('uhms_credit_note_id');
        });

        Schema::table('payment_request_links', function (Blueprint $table) {
            $table->dropConstrainedForeignKey('cancelled_by');
            $table->dropColumn([
                'public_token', 'token_hash', 'initiated_count', 'last_initiated_at',
                'last_viewed_at', 'cancelled_at', 'cancel_reason',
                'success_redirect_url', 'failure_redirect_url',
            ]);
        });
    }
};
