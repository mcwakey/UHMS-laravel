<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $exists = count(DB::select("SHOW COLUMNS FROM `lab_requests` LIKE 'target_department_id'")) > 0;
        if ($exists) {
            return; // already exists
        }
        Schema::table('lab_requests', function (Blueprint $table) {
            $table->foreignId('target_department_id')
                ->nullable()
                ->after('department_id')
                ->constrained('departments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lab_requests', function (Blueprint $table) {
            $table->dropForeign(['target_department_id']);
            $table->dropColumn('target_department_id');
        });
    }
};
