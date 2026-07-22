<?php

namespace App\Services\LegacyMigration\Foundation\Installation;

final readonly class DdlInspectionResult
{
    public function __construct(
        public DdlObjectExpectation $expected,
        public DdlInspectionState $state,
        public ?string $observedDefinitionHash,
    ) {}
}
