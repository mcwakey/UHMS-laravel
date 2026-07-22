<?php

namespace App\Services\LegacyMigration\Foundation\Allocation;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

final readonly class LaravelTargetPatientNumberCollisionProbe implements PatientNumberCollisionProbe
{
    public function __construct(
        private PinnedCollisionEvidence $evidence,
        private ?string $connection = null,
    ) {}

    public function status(string $candidate): CollisionStatus
    {
        if ($candidate === '' || ! $this->evidence->isCurrent()) {
            return CollisionStatus::Unknown;
        }

        try {
            if (! $this->requiredNamespaceExists()) {
                return CollisionStatus::Unknown;
            }

            $collision = $this->db()->table('patients')->where('patient_number', $candidate)->exists()
                || $this->db()->table('archived_patients')->where('patient_number', $candidate)->exists()
                || $this->db()->table('patient_aliases')
                    ->whereIn('alias_type', ['patient_number', 'temporary_patient_number'])
                    ->where('normalized_alias_value', $this->normalizeAlias($candidate))
                    ->exists();

            return $collision ? CollisionStatus::Collision : CollisionStatus::Available;
        } catch (\Throwable) {
            return CollisionStatus::Unknown;
        }
    }

    private function requiredNamespaceExists(): bool
    {
        $schema = $this->db()->getSchemaBuilder();
        foreach ([
            'patients' => ['patient_number'],
            'archived_patients' => ['patient_number'],
            'patient_aliases' => ['alias_type', 'normalized_alias_value'],
        ] as $table => $columns) {
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

    private function normalizeAlias(string $value): string
    {
        return strtoupper(preg_replace('/\s+/', '', trim($value)) ?? '');
    }

    private function db(): ConnectionInterface
    {
        return DB::connection($this->connection ?? (string) config('database.default'));
    }
}
