<?php

namespace App\Services\Consultation;

class ReopenEligibilityResult
{
    public function __construct(
        public readonly bool $allowed,
        public readonly string $code,
        public readonly string $message,
        public readonly ?string $auditEvent = null,
    ) {}

    public static function allow(string $code, string $message, string $auditEvent): self
    {
        return new self(true, $code, $message, $auditEvent);
    }

    public static function deny(string $code, string $message, ?string $auditEvent = null): self
    {
        return new self(false, $code, $message, $auditEvent);
    }
}
