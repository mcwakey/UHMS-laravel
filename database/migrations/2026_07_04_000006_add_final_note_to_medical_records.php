<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medical_records', function (Blueprint $table) {
            if (! Schema::hasColumn('medical_records', 'final_note')) {
                $table->longText('final_note')->nullable()->after('consultation_route_id');
            }
            if (! Schema::hasColumn('medical_records', 'final_note_updated_by')) {
                $table->foreignId('final_note_updated_by')->nullable()->after('final_note')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('medical_records', 'final_note_updated_at')) {
                $table->timestamp('final_note_updated_at')->nullable()->after('final_note_updated_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('medical_records', function (Blueprint $table) {
            if (Schema::hasColumn('medical_records', 'final_note_updated_by')) {
                $table->dropForeign(['final_note_updated_by']);
                $table->dropColumn('final_note_updated_by');
            }

            foreach (['final_note_updated_at', 'final_note'] as $column) {
                if (Schema::hasColumn('medical_records', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
