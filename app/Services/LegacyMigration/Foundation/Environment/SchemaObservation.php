<?php

namespace App\Services\LegacyMigration\Foundation\Environment;

final readonly class SchemaObservation
{
    public function __construct(
        public string $connection,
        public string $database,
        public string $databaseVersion,
        public string $fingerprint,
        public int $tableCount,
        public int $columnCount,
    ) {}

    /** @return array<string, int|string> */
    public function safeSummary(): array
    {
        return [
            'connection_role' => $this->connection === GuardConfiguration::SOURCE_CONNECTION ? 'classic_source' : 'renewed_target',
            'table_count' => $this->tableCount,
            'column_count' => $this->columnCount,
            'fingerprint_status' => 'observed',
        ];
    }
}
