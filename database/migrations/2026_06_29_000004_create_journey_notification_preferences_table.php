<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 9.7 — per-user, per-event journey notification preferences. The existing
 * NotificationPreference is per-module (channels) only; this adds finer per-event
 * control. Rows are optional — sensible defaults apply when none exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journey_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('event', 50);
            $table->boolean('in_app_enabled')->default(true);
            $table->boolean('email_enabled')->default(false);
            $table->boolean('sms_enabled')->default(false);
            $table->boolean('digest_enabled')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'event']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journey_notification_preferences');
    }
};
