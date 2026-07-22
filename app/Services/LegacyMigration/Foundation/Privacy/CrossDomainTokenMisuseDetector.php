<?php

namespace App\Services\LegacyMigration\Foundation\Privacy;

final class CrossDomainTokenMisuseDetector implements Detector
{
    /** @var array<string, array{domain: string, path: string, line: int, column: int}> */
    private array $seen = [];

    /** @var list<PrivacyFinding> */
    private array $findings = [];

    public function id(): string
    {
        return 'cross_domain_token_misuse';
    }

    public function reset(): void
    {
        $this->seen = [];
        $this->findings = [];
    }

    public function scan(string $relativePath, string $content): array
    {
        $matches = [];
        preg_match_all('/\blmt1\.([A-Za-z0-9_-]+)\.([A-Za-z0-9_-]+)\b/', $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        foreach ($matches as $match) {
            $headerJson = self::base64UrlDecode($match[1][0]);
            $header = $headerJson === null ? null : json_decode($headerJson, true);
            $domain = is_array($header) ? ($header['domain'] ?? null) : null;
            if (! is_string($domain)) {
                continue;
            }

            $offset = $match[0][1];
            $before = substr($content, 0, $offset);
            $line = substr_count($before, "\n") + 1;
            $lastNewline = strrpos($before, "\n");
            $column = $offset - ($lastNewline === false ? -1 : $lastNewline);
            $digestFingerprint = hash('sha256', $match[2][0]);
            $prior = $this->seen[$digestFingerprint] ?? null;
            if ($prior !== null && ! hash_equals($prior['domain'], $domain)) {
                $this->findings[] = new PrivacyFinding($this->id(), $relativePath, $line, $column);
                $this->findings[] = new PrivacyFinding($this->id(), $prior['path'], $prior['line'], $prior['column']);
            } else {
                $this->seen[$digestFingerprint] = [
                    'domain' => $domain,
                    'path' => $relativePath,
                    'line' => $line,
                    'column' => $column,
                ];
            }
        }

        return [];
    }

    public function finish(): array
    {
        return $this->findings;
    }

    private static function base64UrlDecode(string $value): ?string
    {
        $padding = (4 - strlen($value) % 4) % 4;
        $decoded = base64_decode(strtr($value.str_repeat('=', $padding), '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
    }
}
