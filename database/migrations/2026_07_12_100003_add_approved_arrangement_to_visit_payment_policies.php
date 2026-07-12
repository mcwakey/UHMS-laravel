<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive link from the observational visit-policy record to the current
 * approved arrangement (Payment Timing Policy Phase 7).
 *
 * `resolved_policy` (baseline) and `recommended_policy` (risk recommendation)
 * are UNCHANGED. `approved_policy_snapshot` is the administrative arrangement and
 * controls NO gate in Phase 7. Only a link + a light snapshot are stored — the
 * full arrangement record is not duplicated here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visit_payment_policies', function (Blueprint $table) {
            $table->unsignedBigInteger('current_approved_arrangement_id')->nullable()->after('last_refreshed_at');
            $table->string('approved_policy_snapshot')->nullable()->after('current_approved_arrangement_id');
            $table->timestamp('approved_arrangement_observed_at')->nullable()->after('approved_policy_snapshot');

            $table->foreign('current_approved_arrangement_id', 'vpp_current_arrangement_fk')
                ->references('id')->on('visit_payment_arrangements')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('visit_payment_policies', function (Blueprint $table) {
            $table->dropForeign('vpp_current_arrangement_fk');
            $table->dropColumn(['current_approved_arrangement_id', 'approved_policy_snapshot', 'approved_arrangement_observed_at']);
        });
    }
};
