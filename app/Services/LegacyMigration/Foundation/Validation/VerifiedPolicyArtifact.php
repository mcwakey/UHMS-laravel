<?php

namespace App\Services\LegacyMigration\Foundation\Validation;

final readonly class VerifiedPolicyArtifact
{
    /** @param list<string> $contractIds */
    public function __construct(
        public string $path,
        public string $sha256,
        public ?string $specificationVersion,
        public array $contractIds,
        public string $approvalReference,
        public mixed $document,
    ) {}
}
