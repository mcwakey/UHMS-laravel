<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vitals', function (Blueprint $table) {
            $table->foreignId('triage_id')
                ->nullable()
                ->unique()
                ->after('admission_id')
                ->constrained('triages')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vitals', function (Blueprint $table) {
            $table->dropForeign(['triage_id']);
            $table->dropUnique(['triage_id']);
            $table->dropColumn('triage_id');
        });
    }
};