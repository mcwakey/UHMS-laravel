<?php

namespace App\Services\Consultation;

use RuntimeException;

class ConsultationActionException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $status = 422,
        public readonly string $event = 'CONSULTATION_ACTION_BLOCKED',
    ) {
        parent::__construct($message);
    }
}

