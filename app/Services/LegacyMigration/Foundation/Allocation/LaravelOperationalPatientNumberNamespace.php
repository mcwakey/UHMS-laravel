<?php

namespace App\Services\LegacyMigration\Foundation\Allocation;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

final readonly class LaravelOperationalPatientNumberNamespace implements PatientNumberCollisionNamespace
{
    public function __construct(private ?string $connection = null) {}

    public function available(): bool
    {
        $schema = $this->db()->getSchemaBuilder();
        foreach (['patients' => ['patient_number'], 'archived_patients' => ['patient_number'], 'patient_aliases' => ['alias_type', 'normalized_alias_value']] as $table => $columns) {
            if (! $schema->hasTable($table)) {
                return false;
            }
            foreach ($columns as $column) {
                if (! $schema->hasColumn($table, $column)) {
                    return false;
                }
            }
        }

        return true;
    }

    public function collides(string $candidate): bool
    {
        return $this->db()->table('patients')->where('patient_number', $candidate)->exists()
            || $this->db()->table('archived_patients')->where('patient_number', $candidate)->exists()
            || $this->db()->table('patient_aliases')
                ->whereIn('alias_type', ['patient_number', 'temporary_patient_number'])
                ->where('normalized_alias_value', strtoupper(preg_replace('/\s+/', '', trim($candidate)) ?? ''))
                ->exists();
    }

    private function db(): ConnectionInterface
    {
        return DB::connection($this->connection ?? (string) config('database.default'));
    }
}
