<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $cols = DB::getDriverName() === 'sqlite'
            ? Schema::getColumnListing('lab_request_items')
            : collect(DB::select('SHOW COLUMNS FROM lab_request_items'))->pluck('Field')->all();

        Schema::table('lab_request_items', function (Blueprint $table) use ($cols) {
            if (!in_array('service_id', $cols, true)) {
                $table->foreignId('service_id')
                    ->nullable()
                    ->after('lab_test_id')
                    ->constrained('service_catalog')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('lab_request_items', function (Blueprint $table) {
            try { $table->dropForeign(['service_id']); } catch (\Throwable $e) {}
            try { $table->dropColumn('service_id'); } catch (\Throwable $e) {}
        });
    }
};
