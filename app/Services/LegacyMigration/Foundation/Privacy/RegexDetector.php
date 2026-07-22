<?php

namespace App\Services\LegacyMigration\Foundation\Privacy;

use RuntimeException;

final class RegexDetector implements Detector
{
    public function __construct(
        private readonly string $detectorId,
        private readonly string $pattern,
        private readonly ?string $pathPattern = null,
    ) {}

    public function id(): string
    {
        return $this->detectorId;
    }

    public function reset(): void {}

    public function scan(string $relativePath, string $content): array
    {
        if ($this->pathPattern !== null && preg_match($this->pathPattern, $relativePath) !== 1) {
            return [];
        }

        $matches = [];
        $result = preg_match_all($this->pattern, $content, $matches, PREG_OFFSET_CAPTURE);
        if ($result === false) {
            throw new RuntimeException('Privacy detector failed safely [LM-PRIV-DETECTOR-001].');
        }

        $findings = [];
        foreach ($matches[0] ?? [] as $match) {
            $offset = $match[1];
            [$line, $column] = self::location($content, $offset);
            $findings[] = new PrivacyFinding($this->detectorId, $relativePath, $line, $column);
        }

        return $findings;
    }

    public function finish(): array
    {
        return [];
    }

    /** @return array{int, int} */
    private static function location(string $content, int $offset): array
    {
        $before = substr($content, 0, $offset);
        $line = substr_count($before, "\n") + 1;
        $lastNewline = strrpos($before, "\n");
        $column = $offset - ($lastNewline === false ? -1 : $lastNewline);

        return [$line, $column];
    }
}
