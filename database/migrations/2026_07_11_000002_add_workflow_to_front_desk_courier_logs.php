<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 18C — courier dispatch / handover workflow. Additive columns on the
 * existing front_desk_courier_logs table. `status` remains the main courier
 * status (Phase 18A); `handover_status` is the workflow detail. The delivery
 * note is kept in metadata.delivery_note to match the module's privacy style.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('front_desk_courier_logs', function (Blueprint $table) {
            $table->string('handover_status')->nullable()->after('status')->index();
            $table->foreignId('dispatch_department_id')->nullable()->after('handover_status')->constrained('departments')->nullOnDelete();
            $table->dateTime('dispatched_at')->nullable()->after('dispatch_department_id');
            $table->foreignId('dispatched_by')->nullable()->after('dispatched_at')->constrained('users')->nullOnDelete();
            $table->foreignId('received_internally_by')->nullable()->after('dispatched_by')->constrained('users')->nullOnDelete();
            $table->string('proof_reference')->nullable()->after('received_internally_by');
        });
    }

    public function down(): void
    {
        Schema::table('front_desk_courier_logs', function (Blueprint $table) {
            $table->dropColumn('proof_reference');
            $table->dropConstrainedForeignId('received_internally_by');
            $table->dropConstrainedForeignId('dispatched_by');
            $table->dropColumn('dispatched_at');
            $table->dropConstrainedForeignId('dispatch_department_id');
            $table->dropColumn('handover_status');
        });
    }
};
