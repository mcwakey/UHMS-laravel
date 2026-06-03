<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Authorised exceptions to the default billing payment gate — chiefly the
 * "deferred OPD settlement" override (render the whole visit, pay once at the
 * end) plus payment-gate bypass / credit / management approvals.
 *
 * MariaDB 10.1 safe: plain string/text/timestamp columns only (no json).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_billing_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();

            // DEFERRED_OPD_SETTLEMENT | PAYMENT_GATE_BYPASS | CREDIT_APPROVAL
            // | MANAGEMENT_APPROVAL | INSURANCE_AUTHORIZATION_PENDING
            $table->string('override_type', 40);

            // VISIT | DEPARTMENT | SERVICE | INVOICE_ITEM
            $table->string('scope', 20)->default('VISIT');
            $table->string('scope_type', 60)->nullable(); // model class / source_type when scoped
            $table->unsignedBigInteger('scope_id')->nullable();

            $table->text('reason');
            $table->foreignId('authorized_by')->constrained('users')->cascadeOnDelete();

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            // ACTIVE | REVOKED | EXPIRED | COMPLETED
            $table->string('status', 20)->default('ACTIVE');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->text('revoke_reason')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index(['visit_id', 'status']);
            $table->index(['override_type', 'status']);
            $table->index(['scope', 'scope_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_billing_overrides');
    }
};
