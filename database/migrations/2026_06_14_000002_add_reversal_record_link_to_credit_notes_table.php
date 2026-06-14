<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_notes', function (Blueprint $table) {
            $table->boolean('is_reversal')->default(false)->after('status');
            $table->foreignId('reverses_credit_note_id')
                ->nullable()
                ->after('is_reversal')
                ->constrained('credit_notes')
                ->nullOnDelete();
            $table->unique('reverses_credit_note_id');
        });
    }

    public function down(): void
    {
        Schema::table('credit_notes', function (Blueprint $table) {
            $table->dropUnique(['reverses_credit_note_id']);
            $table->dropForeign(['reverses_credit_note_id']);
            $table->dropColumn(['is_reversal', 'reverses_credit_note_id']);
        });
    }
};
