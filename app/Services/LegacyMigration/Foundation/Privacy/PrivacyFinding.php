<?php

namespace App\Services\LegacyMigration\Foundation\Privacy;

use JsonSerializable;

final class PrivacyFinding implements JsonSerializable
{
    public function __construct(
        public readonly string $detectorId,
        public readonly string $path,
        public readonly int $line,
        public readonly int $column,
        public readonly bool $allowlisted = false,
    ) {}

    public function allowlisted(): self
    {
        return new self($this->detectorId, $this->path, $this->line, $this->column, true);
    }

    /** @return array{detector_id: string, path: string, location: string, allowlisted: bool} */
    public function jsonSerialize(): array
    {
        return [
            'detector_id' => $this->detectorId,
            'path' => $this->path,
            'location' => "line:{$this->line}:column:{$this->column}:redacted",
            'allowlisted' => $this->allowlisted,
        ];
    }
}
