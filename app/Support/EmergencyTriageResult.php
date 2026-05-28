<?php

namespace App\Support;

use Illuminate\Contracts\Support\Arrayable;

class EmergencyTriageResult implements Arrayable
{
    public function __construct(
        public readonly int $score,
        public readonly string $category,
        public readonly array $reasons = [],
        public readonly array $warnings = [],
    ) {}

    public function toArray(): array
    {
        return [
            'score' => $this->score,
            'category' => $this->category,
            'reasons' => $this->reasons,
            'warnings' => $this->warnings,
        ];
    }
}