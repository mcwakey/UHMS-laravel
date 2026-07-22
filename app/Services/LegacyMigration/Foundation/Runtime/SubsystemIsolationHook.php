<?php

namespace App\Services\LegacyMigration\Foundation\Runtime;

interface SubsystemIsolationHook
{
    public function proofReference(): string;

    public function capture(): mixed;

    public function isolate(): void;

    public function isolated(): bool;

    public function restore(mixed $state): void;
}
