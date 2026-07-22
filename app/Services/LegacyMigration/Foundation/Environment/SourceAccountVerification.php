<?php

namespace App\Services\LegacyMigration\Foundation\Environment;

final readonly class SourceAccountVerification
{
    /** @param array<int, string> $privileges */
    public function __construct(
        public array $privileges,
        public int $grantStatementCount,
    ) {}

    /** @return array<string, mixed> */
    public function safeReport(): array
    {
        return [
            'status' => 'approved_select_metadata_only',
            'scope' => 'exact_uuhms',
            'privileges' => $this->privileges,
            'grant_statement_count' => $this->grantStatementCount,
            'account_identity' => 'redacted',
            'credentials_included' => false,
        ];
    }
}
