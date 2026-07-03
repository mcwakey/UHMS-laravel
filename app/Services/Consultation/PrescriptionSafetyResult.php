<?php

namespace App\Services\Consultation;

class PrescriptionSafetyResult
{
    public function __construct(
        private readonly array $warnings = [],
        private readonly array $blockingErrors = [],
    ) {}

    public function warnings(): array
    {
        return $this->warnings;
    }

    public function blockingErrors(): array
    {
        return $this->blockingErrors;
    }

    public function hasWarnings(): bool
    {
        return $this->warnings !== [];
    }

    public function hasBlockingErrors(): bool
    {
        return $this->blockingErrors !== [];
    }

    public function passed(): bool
    {
        return ! $this->hasWarnings() && ! $this->hasBlockingErrors();
    }

    public function toArray(): array
    {
        return [
            'warnings' => $this->warnings,
            'blocking_errors' => $this->blockingErrors,
            'passed' => $this->passed(),
        ];
    }
}
