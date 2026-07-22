<?php

namespace App\Services\LegacyMigration\Foundation\Privacy;

interface Detector
{
    public function id(): string;

    public function reset(): void;

    /** @return list<PrivacyFinding> */
    public function scan(string $relativePath, string $content): array;

    /** @return list<PrivacyFinding> */
    public function finish(): array;
}
