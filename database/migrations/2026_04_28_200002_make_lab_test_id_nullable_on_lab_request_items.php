<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $col = collect(DB::select('DESCRIBE lab_request_items'))->firstWhere('Field', 'lab_test_id');
        if ($col && $col->Null === 'YES') {
            return; // already nullable
        }
        Schema::table('lab_request_items', function (Blueprint $table) {
            // Make lab_test_id nullable so free-text (non-catalog) items can be saved
            $table->foreignId('lab_test_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('lab_request_items', function (Blueprint $table) {
            $table->foreignId('lab_test_id')->nullable(false)->change();
        });
    }
};
