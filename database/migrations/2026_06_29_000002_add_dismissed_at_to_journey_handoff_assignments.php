<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 9.6 — explicit auto-dismiss timestamp so a stale handoff cleared by the
 * scheduled command is distinguishable from a manually resolved one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journey_handoff_assignments', function (Blueprint $table) {
            $table->timestamp('dismissed_at')->nullable()->after('resolved_at');
        });
    }

    public function down(): void
    {
        Schema::table('journey_handoff_assignments', function (Blueprint $table) {
            $table->dropColumn('dismissed_at');
        });
    }
};
