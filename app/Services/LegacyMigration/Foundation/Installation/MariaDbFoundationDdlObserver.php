<?php

namespace App\Services\LegacyMigration\Foundation\Installation;

use Illuminate\Database\ConnectionInterface;
use RuntimeException;

final readonly class MariaDbFoundationDdlObserver
{
    public function __construct(
        private ConnectionInterface $connection,
        private MariaDbFoundationDdlManifestContract $manifest,
        private DdlDefinitionNormalizer $normalizer,
    ) {}

    /** @return list<DdlObjectObservation> */
    public function observe(): array
    {
        if (! in_array($this->connection->getDriverName(), ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('Foundation DDL inspection requires MariaDB/MySQL.');
        }
        $observed = [];
        foreach ($this->manifest->operations() as $operation) {
            $definition = match ($operation->type) {
                DdlObjectType::Table => $this->tableDefinition($operation->name),
                DdlObjectType::Trigger => $this->triggerDefinition($operation->name),
                DdlObjectType::MigrationLedger => $this->ledgerDefinition($operation->migration),
                DdlObjectType::Column => $this->columnDefinition($operation->name),
                default => null,
            };
            if ($definition !== null) {
                $observed[] = new DdlObjectObservation(
                    $operation->type, $operation->name, $this->normalizer->hash($definition),
                );
            }
        }

        return $observed;
    }

    private function tableDefinition(string $name): ?string
    {
        $present = (int) $this->connection->table('information_schema.TABLES')
            ->where('TABLE_SCHEMA', $this->connection->getDatabaseName())
            ->where('TABLE_NAME', $name)->count();
        if ($present === 0) {
            return null;
        }
        $row = (array) $this->connection->selectOne('SHOW CREATE TABLE `'.str_replace('`', '``', $name).'`');
        $definition = (string) array_values($row)[1];
        if ($this->manifest->version() === MariaDbFoundationDdlManifest::VERSION
            && $name === 'legacy_migration_atomic_intents') {
            $definition = (string) preg_replace(
                '/,\s*`transition_attempt_count`\s+int(?:\(\d+\))?\s+unsigned\s+NOT\s+NULL\s+DEFAULT\s+[\'\"]?0[\'\"]?/i',
                '',
                $definition,
            );
        }

        return $definition;
    }

    private function triggerDefinition(string $name): ?string
    {
        $row = $this->connection->table('information_schema.TRIGGERS')
            ->where('TRIGGER_SCHEMA', $this->connection->getDatabaseName())
            ->where('TRIGGER_NAME', $name)->first();
        if ($row === null) {
            return null;
        }
        $row = (array) $row;

        return 'CREATE TRIGGER `'.$row['TRIGGER_NAME'].'` '.$row['ACTION_TIMING'].' '.$row['EVENT_MANIPULATION'].' ON `'.$row['EVENT_OBJECT_TABLE'].'` FOR EACH ROW '.$row['ACTION_STATEMENT'];
    }

    private function ledgerDefinition(string $migration): ?string
    {
        return $this->connection->table('migrations')->where('migration', $migration)->exists()
            ? $migration
            : null;
    }

    private function columnDefinition(string $coordinate): ?string
    {
        [$table, $column] = array_pad(explode('.', $coordinate, 2), 2, null);
        if ($table === null || $column === null) {
            return null;
        }
        $row = $this->connection->table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', $this->connection->getDatabaseName())
            ->where('TABLE_NAME', $table)->where('COLUMN_NAME', $column)->first();
        if ($row === null) {
            return null;
        }
        $row = (array) $row;

        return implode('|', [$row['COLUMN_TYPE'], $row['IS_NULLABLE'], $row['COLUMN_DEFAULT'], $row['EXTRA'], 'after:attempt']);
    }
}
