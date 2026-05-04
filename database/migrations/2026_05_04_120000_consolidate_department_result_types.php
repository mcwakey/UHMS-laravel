<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The result types `image` and `document` are deprecated as primary
     * department result types. File uploads are now always available on the
     * result page regardless of result type, so existing departments using
     * those values are normalised to `richtext`.
     */
    public function up(): void
    {
        try {
            DB::table('departments')
                ->whereIn('result_type', ['image', 'document'])
                ->update(['result_type' => 'richtext']);
        } catch (\Throwable $e) {
            // result_type column may not exist on legacy DBs; ignore.
        }
    }

    public function down(): void
    {
        // No safe inverse — the original distinction is lost.
    }
};
