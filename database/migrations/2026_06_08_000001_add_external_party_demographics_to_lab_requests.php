<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Captures sex + age for an external / walk-in investigation recipient so lab
 * reports (which need age/sex for reference ranges) can show them. Additive.
 */
return new class extends Migration
{
    private function columnExists(string $table, string $column): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return Schema::hasColumn($table, $column);
        }

        return ! empty(DB::select(
            'select column_name from information_schema.columns where table_schema = database() and table_name = ? and column_name = ?',
            [$table, $column]
        ));
    }

    public function up(): void
    {
        Schema::table('lab_requests', function (Blueprint $table) {
            if (! $this->columnExists('lab_requests', 'external_party_sex')) {
                $table->string('external_party_sex')->nullable()->after('external_party_contact');
            }
            if (! $this->columnExists('lab_requests', 'external_party_age')) {
                $table->unsignedSmallInteger('external_party_age')->nullable()->after('external_party_sex');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lab_requests', function (Blueprint $table) {
            foreach (['external_party_sex', 'external_party_age'] as $c) {
                if ($this->columnExists('lab_requests', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
