<?php

namespace App\Services\LegacyMigration\Foundation\Installation;

use RuntimeException;

final class MariaDbFoundationDdlExtensionManifest implements MariaDbFoundationDdlManifestContract
{
    public const VERSION = 'P3B-DDL-2';

    private const PAYLOAD = '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';

    public function version(): string
    {
        return self::VERSION;
    }

    public function operations(): array
    {
        $json = gzdecode((string) base64_decode(self::PAYLOAD, true));
        $payload = json_decode((string) $json, true, 512, JSON_THROW_ON_ERROR);
        if (($payload['version'] ?? null) !== self::VERSION) {
            throw new RuntimeException('DDL extension manifest version mismatch.');
        }
        $normalizer = new DdlDefinitionNormalizer;

        return array_map(static function (array $row) use ($normalizer): MariaDbDdlOperation {
            $type = DdlObjectType::from($row['type']);
            $definition = $type === DdlObjectType::MigrationLedger ? $row['migration'] : ($row['definition'] ?? $row['sql']);
            if (! hash_equals($row['definition_hash'], $normalizer->hash($definition))) {
                throw new RuntimeException('DDL extension manifest integrity mismatch.');
            }

            return new MariaDbDdlOperation($row['migration'], $type, $row['name'], $row['sql'], $row['definition_hash'], $row['operation_id']);
        }, $payload['operations']);
    }

    public function expectations(): array
    {
        return array_map(fn (MariaDbDdlOperation $op) => new DdlObjectExpectation(self::VERSION, $op->type, $op->name, $op->definitionHash, $op->operationId, $op->type === DdlObjectType::MigrationLedger), $this->operations());
    }

    public function operation(string $operationId): MariaDbDdlOperation
    {
        foreach ($this->operations() as $operation) {
            if (hash_equals($operation->operationId, $operationId)) {
                return $operation;
            }
        }
        throw new RuntimeException('DDL extension operation not approved.');
    }

    public function payloadHash(): string
    {
        return hash('sha256', self::PAYLOAD);
    }
}
