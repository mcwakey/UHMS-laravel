<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 9.7 — optional per-department escalation routing. One primary supervisor and
 * one escalation user (both nullable). Multi-supervisor support is intentionally
 * deferred (see docs/journey/supervisor-routing.md) — these single fields keep the
 * department form simple while making escalation routing precise.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->unsignedBigInteger('supervisor_user_id')->nullable()->after('status')->index();
            $table->unsignedBigInteger('escalation_user_id')->nullable()->after('supervisor_user_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn(['supervisor_user_id', 'escalation_user_id']);
        });
    }
};
