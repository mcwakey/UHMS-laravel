<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            // Current department the patient is physically at
            $table->foreignId('current_department_id')
                ->nullable()
                ->constrained('departments')
                ->nullOnDelete();

            // Triage score set after triage assessment
            $table->string('triage_score')->nullable();

            $table->index('triage_score');
        });
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropForeign(['current_department_id']);
            $table->dropColumn(['current_department_id', 'triage_score']);
        });
    }
};
