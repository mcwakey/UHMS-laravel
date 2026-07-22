<?php

namespace App\Services\LegacyMigration\Foundation\Privacy;

use JsonSerializable;

final class PrivacyScanResult implements JsonSerializable
{
    public const FINDING_HANDLING = 'redacted detector/path/location diagnostics only; never matched content';

    public const CONTAINMENT = 'Stop release; contain and purge affected artifacts; rotate affected credentials or keys; then run a complete privacy rescan.';

    /**
     * @param  list<string>  $patternClasses
     * @param  list<string>  $structuredChecks
     * @param  list<PrivacyFinding>  $findings
     */
    public function __construct(
        public readonly string $manifestHash,
        public readonly int $fileCount,
        public readonly int $coverageDifference,
        public readonly array $patternClasses,
        public readonly array $structuredChecks,
        public readonly array $findings,
    ) {}

    public function unallowlistedFindingCount(): int
    {
        return count(array_filter($this->findings, static fn (PrivacyFinding $finding): bool => ! $finding->allowlisted));
    }

    public function releaseBlocked(): bool
    {
        return $this->coverageDifference !== 0 || $this->unallowlistedFindingCount() !== 0;
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'scan_scope_manifest_hash' => $this->manifestHash,
            'scanner_version' => StructuredDetectorRegistry::SCANNER_VERSION,
            'allowlist_version' => SyntheticFixtureAllowList::VERSION,
            'file_count' => $this->fileCount,
            'coverage_difference' => $this->coverageDifference,
            'pattern_classes' => $this->patternClasses,
            'structured_checks' => $this->structuredChecks,
            'finding_count' => count($this->findings),
            'unallowlisted_finding_count' => $this->unallowlistedFindingCount(),
            'finding_handling' => self::FINDING_HANDLING,
            'release_blocked' => $this->releaseBlocked(),
            'containment_instructions' => $this->releaseBlocked() ? self::CONTAINMENT : null,
            'diagnostics' => array_values(array_filter(
                $this->findings,
                static fn (PrivacyFinding $finding): bool => ! $finding->allowlisted,
            )),
        ];
    }
}
