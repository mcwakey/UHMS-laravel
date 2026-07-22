<?php

namespace App\Services\LegacyMigration\Foundation\Environment;

final readonly class SourceAccountVerification
{
    /** @param array<int, string> $privileges */
    public function __construct(
        public array $privileges,
        public int $grantStatementCount,
        public int $privilegeRecordCount = 0,
        public array $inspectedSurfaces = [],
        public int $selectCoverageTableCount = 0,
        public string $selectCoverageMode = 'unverified',
    ) {}

    /** @return array<string, mixed> */
    public function safeReport(): array
    {
        return [
            'status' => 'approved_select_metadata_only',
            'scope' => 'exact_uuhms',
            'privileges' => $this->privileges,
            'grant_statement_count' => $this->grantStatementCount,
            'privilege_record_count' => $this->privilegeRecordCount,
            'inspected_surfaces' => $this->inspectedSurfaces,
            'select_coverage_table_count' => $this->selectCoverageTableCount,
            'select_coverage_mode' => $this->selectCoverageMode,
            'account_identity' => 'redacted',
            'credentials_included' => false,
        ];
    }
}
