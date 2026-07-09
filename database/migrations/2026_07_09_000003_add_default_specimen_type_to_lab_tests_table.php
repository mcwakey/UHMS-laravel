<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_tests', function (Blueprint $table) {
            // Specimen type this test is normally drawn on. Drives grouping of a
            // request's items into samples (one sample per specimen type).
            $table->string('default_specimen_type', 50)->nullable()->after('unit');
        });
    }

    public function down(): void
    {
        Schema::table('lab_tests', function (Blueprint $table) {
            $table->dropColumn('default_specimen_type');
        });
    }
};
