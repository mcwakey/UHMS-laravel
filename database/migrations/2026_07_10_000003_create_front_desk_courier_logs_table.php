<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Front Desk Operations (Phase 18A) — incoming / outgoing letters, parcels,
 * documents, reports, invoices, supplies, etc. Patient link is optional and the
 * clinical contents of any item are never exposed. No proof-of-delivery upload
 * in this phase (deferred to a later phase).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('front_desk_courier_logs', function (Blueprint $table) {
            $table->id();

            // incoming | outgoing (App\Enums\FrontDesk\CourierDirection)
            $table->string('direction')->default('incoming')->index();
            // App\Enums\FrontDesk\CourierType
            $table->string('courier_type')->default('letter')->index();

            $table->string('sender_name')->nullable();
            $table->string('sender_organization')->nullable();
            $table->string('recipient_name')->nullable();
            $table->foreignId('recipient_department_id')->nullable()->constrained('departments')->nullOnDelete();

            $table->foreignId('related_patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->foreignId('related_visit_id')->nullable()->constrained('visits')->nullOnDelete();

            $table->string('courier_company')->nullable();
            $table->string('messenger_name')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('reference_number')->nullable();

            $table->dateTime('received_or_sent_at');
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('delivered_to')->nullable();
            $table->dateTime('delivered_at')->nullable();

            // App\Enums\FrontDesk\CourierStatus
            $table->string('status')->default('received')->index();
            $table->boolean('signature_required')->default(false);

            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index('received_or_sent_at');
            $table->index(['direction', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('front_desk_courier_logs');
    }
};
