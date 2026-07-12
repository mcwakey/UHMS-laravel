<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Immutable history of visit-payment-policy materialisations/refreshes (Payment
 * Timing Policy Phase 6). Append-only — never edited or deleted through the
 * application. old_values/new_values store only material policy + risk-snapshot
 * fields; never patient snapshots or free-text risk details.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_payment_policy_history', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('visit_payment_policy_id');
            $table->foreign('visit_payment_policy_id', 'vpp_history_policy_fk')
                ->references('id')->on('visit_payment_policies')->cascadeOnDelete();
            $table->index('visit_payment_policy_id', 'vpp_history_policy_idx');

            $table->foreignId('visit_id')->constrained('visits')->cascadeOnDelete();

            // App\Enums\VisitPaymentPolicyEvent
            $table->string('event_type')->index();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('reason_code')->nullable();

            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('performed_at');

            $table->timestamps();

            $table->index(['visit_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_payment_policy_history');
    }
};
