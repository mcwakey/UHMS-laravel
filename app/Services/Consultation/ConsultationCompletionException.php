<?php

namespace App\Services\Consultation;

use RuntimeException;

class ConsultationCompletionException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ConsultationCompletionReadinessResult $result,
        public readonly int $status = 422,
        public readonly string $event = 'CONSULTATION_COMPLETION_BLOCKED',
    ) {
        parent::__construct($message);
    }
}
