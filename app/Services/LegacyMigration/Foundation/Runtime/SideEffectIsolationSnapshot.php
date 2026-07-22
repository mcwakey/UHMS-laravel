<?php

namespace App\Services\LegacyMigration\Foundation\Runtime;

final readonly class SideEffectIsolationSnapshot
{
    /** @param array<string, bool> $isolated */
    public function __construct(public array $isolated) {}
}
