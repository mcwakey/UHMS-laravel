<?php

namespace Database\Seeders\ManualTesting;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

abstract class ManualTestingSeederBase extends Seeder
{
    protected const PROFILE = 'manual_test';

    protected const PREFIX = 'MT-';

    protected function scale(): string
    {
        $scale = (string) (config('uhms.manual_testing.scale') ?: env('UHMS_MANUAL_TEST_SCALE', 'small'));

        return in_array($scale, ['small', 'medium', 'large'], true) ? $scale : 'small';
    }

    protected function target(string $key): int
    {
        $targets = [
            'small' => [
                'departments' => 18,
                'users' => 30,
                'patients' => 200,
                'visits' => 300,
                'appointments' => 120,
                'invoices' => 150,
                'lab_requests' => 100,
                'prescriptions' => 60,
                'admissions' => 20,
                'emergency_cases' => 20,
                'employees' => 30,
                'payroll_periods' => 2,
                'audit_logs' => 120,
            ],
            'medium' => [
                'departments' => 28,
                'users' => 80,
                'patients' => 1000,
                'visits' => 2500,
                'appointments' => 900,
                'invoices' => 1500,
                'lab_requests' => 600,
                'prescriptions' => 400,
                'admissions' => 150,
                'emergency_cases' => 150,
                'employees' => 80,
                'payroll_periods' => 3,
                'audit_logs' => 600,
            ],
            'large' => [
                'departments' => 44,
                'users' => 200,
                'patients' => 5000,
                'visits' => 15000,
                'appointments' => 3000,
                'invoices' => 8000,
                'lab_requests' => 3000,
                'prescriptions' => 2000,
                'admissions' => 600,
                'emergency_cases' => 700,
                'employees' => 200,
                'payroll_periods' => 12,
                'audit_logs' => 3000,
            ],
        ];

        return $targets[$this->scale()][$key] ?? 0;
    }

    protected function hasTable(string $table): bool
    {
        return Schema::hasTable($table);
    }

    protected function hasColumn(string $table, string $column): bool
    {
        return $this->hasTable($table) && Schema::hasColumn($table, $column);
    }

    protected function insert(string $table, array $rows, int $chunk = 500): void
    {
        if (! $this->hasTable($table) || empty($rows)) {
            return;
        }

        $columns = Schema::getColumnListing($table);
        foreach (array_chunk($rows, $chunk) as $batch) {
            $filtered = array_map(fn (array $row) => array_intersect_key($row, array_flip($columns)), $batch);
            DB::table($table)->insert($filtered);
        }
    }

    protected function updateOrInsert(string $table, array $keys, array $values): void
    {
        if (! $this->hasTable($table)) {
            return;
        }

        $columns = Schema::getColumnListing($table);
        DB::table($table)->updateOrInsert(
            array_intersect_key($keys, array_flip($columns)),
            array_intersect_key($values, array_flip($columns)),
        );
    }

    protected function countManual(string $table, string $column): int
    {
        if (! $this->hasColumn($table, $column)) {
            return 0;
        }

        return (int) DB::table($table)->where($column, 'like', self::PREFIX.'%')->count();
    }

    protected function existingIds(string $table, string $column, int $limit = 0): array
    {
        if (! $this->hasColumn($table, $column)) {
            return [];
        }

        $query = DB::table($table)->where($column, 'like', self::PREFIX.'%')->orderBy('id');

        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->pluck('id')->all();
    }

    protected function anyIds(string $table, int $limit = 0): array
    {
        if (! $this->hasTable($table)) {
            return [];
        }

        $query = DB::table($table)->orderBy('id');
        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->pluck('id')->all();
    }

    protected function metadata(array $extra = []): string
    {
        return json_encode(array_merge([
            'seed_profile' => self::PROFILE,
            'seed_scale' => $this->scale(),
        ], $extra), JSON_THROW_ON_ERROR);
    }

    protected function now(): Carbon
    {
        return now();
    }

    protected function pastDate(int $index, int $maxDays = 370): Carbon
    {
        return today()->subDays(($index % $maxDays) + 1);
    }

    protected function ref(string $kind, int $index, int $pad = 6): string
    {
        return self::PREFIX.$kind.'-'.str_pad((string) $index, $pad, '0', STR_PAD_LEFT);
    }

    protected function slugCode(string $value, int $max = 16): string
    {
        return strtoupper(substr((string) Str::of($value)->ascii()->slug('-'), 0, $max));
    }
}
