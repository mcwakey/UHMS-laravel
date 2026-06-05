<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (! $this->columnExists('appointments', 'consultation_route_id')) {
                $table->foreignId('consultation_route_id')
                    ->nullable()
                    ->after('visit_id')
                    ->constrained('visit_consultation_routes')
                    ->nullOnDelete();
            }

            if (! $this->columnExists('appointments', 'medical_record_id')) {
                $table->foreignId('medical_record_id')
                    ->nullable()
                    ->after('consultation_route_id')
                    ->constrained('medical_records')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if ($this->columnExists('appointments', 'medical_record_id')) {
                $table->dropConstrainedForeignId('medical_record_id');
            }

            if ($this->columnExists('appointments', 'consultation_route_id')) {
                $table->dropConstrainedForeignId('consultation_route_id');
            }
        });
    }

    private function columnExists(string $table, string $column): bool
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return collect(DB::select('PRAGMA table_info('.$table.')'))
                ->contains(fn ($definition) => ($definition->name ?? null) === $column);
        }

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return Schema::hasColumn($table, $column);
        }

        $result = DB::selectOne(
            'select count(*) as aggregate from information_schema.columns where table_schema = database() and table_name = ? and column_name = ?',
            [$table, $column],
        );

        return (int) ($result->aggregate ?? 0) > 0;
    }
};
