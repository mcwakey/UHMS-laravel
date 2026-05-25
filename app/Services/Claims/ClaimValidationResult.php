<?php

namespace App\Services\Claims;

class ClaimValidationResult
{
    /**
     * @param  array<int, string>  $errors
     * @param  array<int, string>  $warnings
     */
    public function __construct(
        public readonly bool $valid,
        public readonly array $errors = [],
        public readonly array $warnings = [],
    ) {}

    /**
     * @param  array<int, string>  $errors
     * @param  array<int, string>  $warnings
     */
    public static function make(array $errors = [], array $warnings = []): self
    {
        return new self($errors === [], array_values($errors), array_values($warnings));
    }

    public function toArray(): array
    {
        return [
            'valid' => $this->valid,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
        ];
    }
}
