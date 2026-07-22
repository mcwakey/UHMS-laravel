<?php

namespace App\Services\LegacyMigration\Foundation\Installation;

final class DdlDefinitionNormalizer
{
    public function normalize(string $sql): string
    {
        $withoutRuntimeSequence = preg_replace('/\s*AUTO_INCREMENT=\d+\b/i', '', $sql);

        return trim((string) preg_replace('/\s+/u', ' ', (string) $withoutRuntimeSequence));
    }

    public function hash(string $sql): string
    {
        return hash('sha256', $this->normalize($sql));
    }
}
