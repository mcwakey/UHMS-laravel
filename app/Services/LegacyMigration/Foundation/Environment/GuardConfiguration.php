<?php

namespace App\Services\LegacyMigration\Foundation\Environment;

final readonly class GuardConfiguration
{
    public const SOURCE_CONNECTION = 'legacy_uhms';
    public const SOURCE_DATABASE = 'uuhms';
    public const SOURCE_VERSION = '10.4.32-MariaDB';
    public const SOURCE_FINGERPRINT = '150fcf4783fcb8bdc25f7e17fe0ece5050955f0c68e7dd03651ee8bd58498977';
    public const SOURCE_TABLE_COUNT = 55;
    public const SOURCE_COLUMN_COUNT = 479;

    /**
     * @param array<int, string> $approvedEnvironments
     * @param array<int, string> $targetConnectionAllowList
     */
    public function __construct(
        public bool $enabled,
        public bool $rejectProduction,
        public bool $dryRunOnly,
        public bool $commitAuthorized,
        public bool $cohortSelectionEnabled,
        public bool $importerExecutionEnabled,
        public bool $productionEnabled,
        public array $approvedEnvironments,
        public string $sourceConnection,
        public string $sourceDatabase,
        public string $sourceVersion,
        public string $sourceFingerprint,
        public int $sourceTableCount,
        public int $sourceColumnCount,
        public string $targetConnection,
        public array $targetConnectionAllowList,
        public string $targetDatabase,
        public string $targetVersion,
        public string $targetFingerprint,
        public int $targetTableCount,
        public int $targetColumnCount,
    ) {}

    /** @param array<string, mixed> $configuration */
    public static function fromArray(array $configuration, string $environment): self
    {
        $guards = self::arrayValue($configuration, 'guards', $configuration);
        $source = self::arrayValue($guards, 'source');
        $targets = self::arrayValue($guards, 'targets', self::arrayValue($guards, 'target'));
        $target = self::targetForEnvironment($targets, $environment);
        $approvedEnvironments = self::stringList(
            $guards['approved_environments'] ?? $configuration['allowed_environments'] ?? []
        );
        $targetConnection = self::requiredString($target, 'connection');
        $targetAllowList = self::stringList($target['connection_allow_list'] ?? $target['allowed_connections'] ?? [$targetConnection]);
        $execution = self::arrayValue($configuration, 'execution');

        return new self(
            enabled: ($configuration['enabled'] ?? false) === true,
            rejectProduction: ($configuration['reject_production'] ?? false) === true,
            dryRunOnly: ($execution['dry_run_only'] ?? false) === true,
            commitAuthorized: ($execution['commit_authorized'] ?? true) === true,
            cohortSelectionEnabled: ($execution['cohort_b_selection_enabled'] ?? true) === true,
            importerExecutionEnabled: ($execution['importer_execution_enabled'] ?? true) === true,
            productionEnabled: ($execution['production_enabled'] ?? true) === true,
            approvedEnvironments: $approvedEnvironments,
            sourceConnection: self::requiredString($source, 'connection'),
            sourceDatabase: self::requiredString($source, 'database'),
            sourceVersion: self::requiredStringAlias($source, 'version', 'expected_version'),
            sourceFingerprint: self::requiredDigestAlias($source, 'fingerprint', 'expected_fingerprint'),
            sourceTableCount: self::requiredPositiveIntAlias($source, 'table_count', 'expected_table_count'),
            sourceColumnCount: self::requiredPositiveIntAlias($source, 'column_count', 'expected_column_count'),
            targetConnection: $targetConnection,
            targetConnectionAllowList: $targetAllowList,
            targetDatabase: self::requiredString($target, 'database'),
            targetVersion: self::requiredStringAlias($target, 'version', 'expected_version'),
            targetFingerprint: self::requiredDigestAlias($target, 'fingerprint', 'expected_fingerprint'),
            targetTableCount: self::requiredPositiveIntAlias($target, 'table_count', 'expected_table_count'),
            targetColumnCount: self::requiredPositiveIntAlias($target, 'column_count', 'expected_column_count'),
        );
    }

    /** @return array<string, scalar|array<int, string>> */
    public function fingerprintMaterial(string $environment): array
    {
        return [
            'enabled' => $this->enabled,
            'reject_production' => $this->rejectProduction,
            'dry_run_only' => $this->dryRunOnly,
            'commit_authorized' => $this->commitAuthorized,
            'cohort_b_selection_enabled' => $this->cohortSelectionEnabled,
            'importer_execution_enabled' => $this->importerExecutionEnabled,
            'production_enabled' => $this->productionEnabled,
            'environment' => $environment,
            'approved_environments' => $this->approvedEnvironments,
            'source_connection' => $this->sourceConnection,
            'source_database' => $this->sourceDatabase,
            'source_version' => $this->sourceVersion,
            'source_fingerprint' => $this->sourceFingerprint,
            'source_table_count' => $this->sourceTableCount,
            'source_column_count' => $this->sourceColumnCount,
            'target_connection' => $this->targetConnection,
            'target_connection_allow_list' => $this->targetConnectionAllowList,
            'target_database' => $this->targetDatabase,
            'target_version' => $this->targetVersion,
            'target_fingerprint' => $this->targetFingerprint,
            'target_table_count' => $this->targetTableCount,
            'target_column_count' => $this->targetColumnCount,
        ];
    }

    /** @param array<string, mixed> $values @return array<string, mixed> */
    private static function arrayValue(array $values, string $key, array $default = []): array
    {
        $value = $values[$key] ?? $default;

        return is_array($value) ? $value : [];
    }

    /** @param array<string, mixed> $targets @return array<string, mixed> */
    private static function targetForEnvironment(array $targets, string $environment): array
    {
        if (isset($targets['environments']) && is_array($targets['environments'])) {
            $target = $targets['environments'][$environment] ?? null;

            return is_array($target) ? array_replace($targets, $target) : [];
        }
        if (isset($targets[$environment]) && is_array($targets[$environment])) {
            return $targets[$environment];
        }

        return $targets;
    }

    /** @param array<string, mixed> $values */
    private static function requiredString(array $values, string $key): string
    {
        $value = $values[$key] ?? null;
        if (! is_string($value) || trim($value) === '') {
            throw new FoundationGuardException('FOUNDATION_CONFIG_MISSING', 'A mandatory migration guard setting is missing.');
        }

        return trim($value);
    }

    /** @param array<string, mixed> $values */
    private static function requiredStringAlias(array $values, string $key, string $alias): string
    {
        if (! array_key_exists($key, $values) && array_key_exists($alias, $values)) {
            $values[$key] = $values[$alias];
        }

        return self::requiredString($values, $key);
    }

    /** @param array<string, mixed> $values */
    private static function requiredDigest(array $values, string $key): string
    {
        $value = strtolower(self::requiredString($values, $key));
        if (preg_match('/\A[a-f0-9]{64}\z/', $value) !== 1) {
            throw new FoundationGuardException('FOUNDATION_CONFIG_INVALID', 'A migration guard fingerprint is invalid.');
        }

        return $value;
    }

    /** @param array<string, mixed> $values */
    private static function requiredDigestAlias(array $values, string $key, string $alias): string
    {
        if (! array_key_exists($key, $values) && array_key_exists($alias, $values)) {
            $values[$key] = $values[$alias];
        }

        return self::requiredDigest($values, $key);
    }

    /** @param array<string, mixed> $values */
    private static function requiredPositiveInt(array $values, string $key): int
    {
        $value = $values[$key] ?? null;
        if (is_string($value) && ctype_digit($value)) {
            $value = (int) $value;
        }
        if (! is_int($value) || $value < 1) {
            throw new FoundationGuardException('FOUNDATION_CONFIG_INVALID', 'A migration guard shape value is invalid.');
        }

        return $value;
    }

    /** @param array<string, mixed> $values */
    private static function requiredPositiveIntAlias(array $values, string $key, string $alias): int
    {
        if (! array_key_exists($key, $values) && array_key_exists($alias, $values)) {
            $values[$key] = $values[$alias];
        }

        return self::requiredPositiveInt($values, $key);
    }

    /** @return array<int, string> */
    private static function stringList(mixed $value): array
    {
        if (! is_array($value) || $value === []) {
            throw new FoundationGuardException('FOUNDATION_CONFIG_MISSING', 'A mandatory migration allow-list is missing.');
        }
        $result = [];
        foreach ($value as $item) {
            if (! is_string($item) || trim($item) === '') {
                throw new FoundationGuardException('FOUNDATION_CONFIG_INVALID', 'A migration allow-list is invalid.');
            }
            $result[] = trim($item);
        }

        return array_values(array_unique($result));
    }
}
