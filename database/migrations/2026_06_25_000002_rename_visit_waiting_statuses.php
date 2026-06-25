<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Renames two visit statuses to separate "where in the workflow" from the older
 * conflated vocabulary:
 *   - 'waiting'              (old triage-queue state)  -> 'queued'
 *   - 'waiting_consultation' (old post-triage wait)    -> 'waiting'
 *
 * Order matters: 'waiting' -> 'queued' MUST run before
 * 'waiting_consultation' -> 'waiting', otherwise the second update would also
 * catch the freshly-renamed rows. NOTE: queue_entries.status is a DIFFERENT
 * domain and is intentionally left untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        // visits.status
        DB::table('visits')->where('status', 'waiting')->update(['status' => 'queued']);
        DB::table('visits')->where('status', 'waiting_consultation')->update(['status' => 'waiting']);

        // visit_status_logs.from_status / to_status
        foreach (['from_status', 'to_status'] as $col) {
            DB::table('visit_status_logs')->where($col, 'waiting')->update([$col => 'queued']);
            DB::table('visit_status_logs')->where($col, 'waiting_consultation')->update([$col => 'waiting']);
        }
    }

    public function down(): void
    {
        // Reverse order: free 'waiting' first, then reclaim it from 'queued'.
        DB::table('visits')->where('status', 'waiting')->update(['status' => 'waiting_consultation']);
        DB::table('visits')->where('status', 'queued')->update(['status' => 'waiting']);

        foreach (['from_status', 'to_status'] as $col) {
            DB::table('visit_status_logs')->where($col, 'waiting')->update([$col => 'waiting_consultation']);
            DB::table('visit_status_logs')->where($col, 'queued')->update([$col => 'waiting']);
        }
    }
};
