<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The patient profile timeline could not surface module activity because the
 * patient/visit context lived only inside the `properties` longText (not
 * queryable on MariaDB 10.1, which lacks JSON_EXTRACT). Promote patient_id and
 * visit_id to real, indexed columns so the timeline can query them directly.
 *
 * Plain indexed columns (no FK) so logs survive regardless of patient/visit
 * lifecycle. Populated going forward by ActivityLog::saving + the context
 * resolver, and backfilled for existing rows by `php artisan logs:backfill-context`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->unsignedBigInteger('patient_id')->nullable()->after('subject_id');
            $table->unsignedBigInteger('visit_id')->nullable()->after('patient_id');
            $table->index('patient_id');
            $table->index('visit_id');
        });
    }

    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropIndex(['patient_id']);
            $table->dropIndex(['visit_id']);
            $table->dropColumn(['patient_id', 'visit_id']);
        });
    }
};
