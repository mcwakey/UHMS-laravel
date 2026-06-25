<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Separates the conflated visit.status field into independent concepts:
 *   - visit_source       : how the visit started (direct, appointment, ...)
 *   - attendance_class   : statistical attendance category (auto-computed)
 * Plus dedicated workflow timestamps so reporting can be precise.
 *
 * Values are stored as plain "code" strings that reference the visit_sources /
 * attendance_classes lookup tables (created in a sibling migration), keeping
 * statistics queries fast while the labels/config stay dynamic.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            if (! Schema::hasColumn('visits', 'visit_source')) {
                $table->string('visit_source')->nullable()->after('visit_type')->index();
            }
            if (! Schema::hasColumn('visits', 'attendance_class')) {
                $table->string('attendance_class')->nullable()->after('visit_source')->index();
            }
            if (! Schema::hasColumn('visits', 'arrived_at')) {
                $table->timestamp('arrived_at')->nullable()->after('checked_in_at');
            }
            if (! Schema::hasColumn('visits', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('checked_out_at');
            }
            if (! Schema::hasColumn('visits', 'no_show_at')) {
                $table->timestamp('no_show_at')->nullable()->after('cancelled_at');
            }
            if (! Schema::hasColumn('visits', 'abandoned_at')) {
                $table->timestamp('abandoned_at')->nullable()->after('no_show_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            foreach (['visit_source', 'attendance_class', 'arrived_at', 'cancelled_at', 'no_show_at', 'abandoned_at'] as $col) {
                if (Schema::hasColumn('visits', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
