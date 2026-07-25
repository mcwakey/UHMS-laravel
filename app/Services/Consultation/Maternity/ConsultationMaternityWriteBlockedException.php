<?php

namespace App\Services\Consultation\Maternity;

use Illuminate\Validation\ValidationException;

/**
 * Phase 14R.4 — raised when a runtime write path attempts to persist a
 * maternity-owned specialty field while the source-of-truth guard applies.
 *
 * Extends ValidationException so the existing controller/form error handling
 * surfaces it naturally, while automation callers can still catch this
 * specific type and record a structured "blocked" result.
 */
class ConsultationMaternityWriteBlockedException extends ValidationException
{
    /** @var list<string> */
    public array $blockedFields = [];

    /**
     * @param  list<string>  $blocked
     */
    public static function forFields(array $blocked): self
    {
        $messages = app(ConsultationMaternitySpecialtyWriteGuard::class)
            ->validationMessages($blocked);

        $validator = validator([], []);
        foreach ($messages as $field => $message) {
            $validator->errors()->add($field, $message);
        }

        $exception = new self($validator);
        $exception->blockedFields = $blocked;

        return $exception;
    }
}
