<?php

namespace App\Services\LegacyMigration\Foundation\Privacy;

use Throwable;

final class SafeDiagnosticEncoder
{
    /** @return array{detector_id: string, path: string, location: string} */
    public function finding(PrivacyFinding $finding): array
    {
        return [
            'detector_id' => $finding->detectorId,
            'path' => str_replace('\\', '/', $finding->path),
            'location' => "line:{$finding->line}:column:{$finding->column}:redacted",
        ];
    }

    /** @return array{error_code: string, detail: string} */
    public function exception(Throwable $exception): array
    {
        return [
            'error_code' => 'LM-PRIV-SCAN-FAILED-001',
            'detail' => 'Privacy scan failed closed; sensitive exception detail was suppressed.',
        ];
    }

    /** @param array<string, mixed> $payload */
    public function json(array $payload): string
    {
        return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }
}
