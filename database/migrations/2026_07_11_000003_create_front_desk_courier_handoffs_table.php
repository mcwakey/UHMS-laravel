<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 18C — courier handover trail: a clean timeline of custody events for a
 * courier item (received / dispatched / handed over / delivered / returned /
 * cancelled / note). Non-clinical; carries no patient content.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('front_desk_courier_handoffs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('courier_log_id')->constrained('front_desk_courier_logs')->cascadeOnDelete();
            $table->foreignId('from_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('from_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('to_department_id')->nullable()->constrained('departments')->nullOnDelete();
            // App\Enums\FrontDesk\CourierHandoffAction
            $table->string('action');
            $table->dateTime('action_at');
            $table->text('note')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['courier_log_id', 'action_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('front_desk_courier_handoffs');
    }
};
