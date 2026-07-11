<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Front Desk Operations (Phase 18A) — incoming / outgoing call logs handled by
 * reception. Patient link is optional; sensitive free-text notes are not shown
 * in list views. No clinical/billing records are created from these logs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('front_desk_call_logs', function (Blueprint $table) {
            $table->id();

            // incoming | outgoing (App\Enums\FrontDesk\CallDirection)
            $table->string('direction')->default('incoming')->index();
            $table->string('caller_name')->nullable();
            $table->string('recipient_name')->nullable();
            $table->string('phone_number')->nullable();

            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('related_patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->foreignId('related_visit_id')->nullable()->constrained('visits')->nullOnDelete();

            // App\Enums\FrontDesk\CallCategory
            $table->string('category')->default('general_enquiry')->index();

            $table->foreignId('handled_by')->constrained('users')->cascadeOnDelete();

            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();

            // App\Enums\FrontDesk\CallOutcome
            $table->string('outcome')->default('answered')->index();

            $table->boolean('follow_up_required')->default(false)->index();
            // pending | completed | cancelled (nullable)
            $table->string('follow_up_status')->nullable();
            $table->foreignId('assigned_follow_up_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index('started_at');
            $table->index(['follow_up_required', 'follow_up_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('front_desk_call_logs');
    }
};
