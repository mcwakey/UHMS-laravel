<?php

namespace App\Services\Maternity\Context;

use RuntimeException;

/**
 * Phase 14R.5 — shared derivation failure.
 *
 * Carries the SAME stable codes the 14R.2 consultation bridge already used, so
 * ConsultationMaternityLinkService can translate one-to-one without changing a
 * single existing error code or message.
 */
class MaternityContextTargetException extends RuntimeException
{
    public const UNSUPPORTED_TARGET = 'unsupported_target';
    public const INVALID_TARGET = 'invalid_target';
    public const PATIENT_MISMATCH = 'patient_mismatch';
    public const INCONSISTENT_CONTEXT = 'inconsistent_context';

    public function __construct(
        public readonly string $errorCode,
        public readonly ?string $targetClass = null,
        string $message = '',
    ) {
        parent::__construct($message !== '' ? $message : $errorCode);
    }

    public static function unsupportedTarget(string $class): self
    {
        return new self(self::UNSUPPORTED_TARGET, $class);
    }

    public static function invalidTarget(): self
    {
        return new self(self::INVALID_TARGET);
    }

    public static function patientMismatch(): self
    {
        return new self(self::PATIENT_MISMATCH);
    }

    public static function inconsistentContext(): self
    {
        return new self(self::INCONSISTENT_CONTEXT);
    }
}
