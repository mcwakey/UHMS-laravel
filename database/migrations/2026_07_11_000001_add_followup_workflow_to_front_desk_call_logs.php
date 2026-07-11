<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 18C — call follow-up queue workflow. Additive columns on the existing
 * front_desk_call_logs table (reuses the Phase 18A follow_up_required /
 * follow_up_status / assigned_follow_up_user_id fields). Follow-up notes stay in
 * metadata (follow_up_note / completion_note / cancellation_reason).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('front_desk_call_logs', function (Blueprint $table) {
            $table->dateTime('follow_up_due_at')->nullable()->after('follow_up_status')->index();
            $table->dateTime('follow_up_completed_at')->nullable()->after('follow_up_due_at');
            $table->foreignId('follow_up_completed_by')->nullable()->after('follow_up_completed_at')->constrained('users')->nullOnDelete();
            $table->dateTime('follow_up_cancelled_at')->nullable()->after('follow_up_completed_by');
            $table->foreignId('follow_up_cancelled_by')->nullable()->after('follow_up_cancelled_at')->constrained('users')->nullOnDelete();
            $table->foreignId('transfer_department_id')->nullable()->after('follow_up_cancelled_by')->constrained('departments')->nullOnDelete();
            $table->foreignId('transferred_to_user_id')->nullable()->after('transfer_department_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('front_desk_call_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('transferred_to_user_id');
            $table->dropConstrainedForeignId('transfer_department_id');
            $table->dropConstrainedForeignId('follow_up_cancelled_by');
            $table->dropColumn('follow_up_cancelled_at');
            $table->dropConstrainedForeignId('follow_up_completed_by');
            $table->dropColumn(['follow_up_due_at', 'follow_up_completed_at']);
        });
    }
};
