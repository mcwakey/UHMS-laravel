<?php

namespace App\Services\LegacyMigration\Foundation\Installation;

use RuntimeException;

final class MariaDbFoundationDdlManifest implements MariaDbFoundationDdlManifestContract
{
    public const VERSION = 'P3B-DDL-1';

    public const PAYLOAD_HASH = 'cf8459db7744c9d9d64a7aa2c18b1c5fdede90d336358cdb9fc0b4a2ee9b2bfd';

    private const PAYLOAD = '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';

    public function version(): string
    {
        return self::VERSION;
    }

    /** @return list<MariaDbDdlOperation> */
    public function operations(): array
    {
        if (! hash_equals(self::PAYLOAD_HASH, hash('sha256', self::PAYLOAD))) {
            throw new RuntimeException('The immutable foundation DDL manifest integrity check failed.');
        }
        $compressed = base64_decode(self::PAYLOAD, true);
        $json = $compressed === false ? false : gzdecode($compressed);
        if (! is_string($json)) {
            throw new RuntimeException('The immutable foundation DDL manifest is invalid.');
        }
        $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($payload) || ($payload['version'] ?? null) !== self::VERSION || ! is_array($payload['operations'] ?? null)) {
            throw new RuntimeException('The immutable foundation DDL manifest version is invalid.');
        }

        $normalizer = new DdlDefinitionNormalizer;

        return array_map(static function (array $row) use ($normalizer): MariaDbDdlOperation {
            $type = DdlObjectType::tryFrom((string) ($row['type'] ?? ''));
            if ($type === null) {
                throw new RuntimeException('The immutable foundation DDL manifest contains an unknown object type.');
            }

            $definition = $type === DdlObjectType::MigrationLedger
                ? (string) $row['migration']
                : (string) ($row['sql'] ?? '');
            if (! hash_equals((string) ($row['definition_hash'] ?? ''), $normalizer->hash($definition))) {
                throw new RuntimeException('The immutable foundation DDL operation integrity check failed.');
            }

            return new MariaDbDdlOperation(
                (string) $row['migration'], $type, (string) $row['name'],
                isset($row['sql']) ? (string) $row['sql'] : null,
                (string) $row['definition_hash'], (string) $row['operation_id'],
            );
        }, $payload['operations']);
    }

    /** @return list<DdlObjectExpectation> */
    public function expectations(): array
    {
        return array_map(static fn (MariaDbDdlOperation $operation): DdlObjectExpectation => new DdlObjectExpectation(
            self::VERSION, $operation->type, $operation->name, $operation->definitionHash,
            $operation->operationId, $operation->type === DdlObjectType::MigrationLedger,
        ), $this->operations());
    }

    public function operation(string $operationId): MariaDbDdlOperation
    {
        foreach ($this->operations() as $operation) {
            if (hash_equals($operation->operationId, $operationId)) {
                return $operation;
            }
        }

        throw new RuntimeException('The requested DDL operation is not in the immutable manifest.');
    }

    public function payloadHash(): string
    {
        return hash('sha256', self::PAYLOAD);
    }
}
