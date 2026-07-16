<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Immutable history of per-visit payment-arrangement transitions (Payment Timing
 * Policy Phase 7). Append-only — never edited or deleted through the
 * application. old_values/new_values store only material arrangement fields;
 * never patient contact/clinical data or free-text risk details.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_payment_arrangement_history', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('visit_payment_arrangement_id');
            $table->foreign('visit_payment_arrangement_id', 'vpa_history_arrangement_fk')
                ->references('id')->on('visit_payment_arrangements')->cascadeOnDelete();
            $table->index('visit_payment_arrangement_id', 'vpa_history_arrangement_idx');

            $table->foreignId('visit_id')->constrained('visits')->cascadeOnDelete();

            // App\Enums\VisitPaymentArrangementEvent
            $table->string('event_type')->index();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('reason_code')->nullable();

            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('performed_at');

            $table->timestamps();

            $table->index(['visit_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_payment_arrangement_history');
    }
};
