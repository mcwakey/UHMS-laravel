<?php

namespace App\Services\Consultation;

use RuntimeException;

class PrescriptionSafetyException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly PrescriptionSafetyResult $result,
        public readonly int $status = 422,
        public readonly bool $requiresOverride = false,
        public readonly string $event = 'PRESCRIPTION_SAFETY_BLOCKED',
    ) {
        parent::__construct($message);
    }
}
