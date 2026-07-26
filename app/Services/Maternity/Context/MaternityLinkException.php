<?php

namespace App\Services\Maternity\Context;

use RuntimeException;

/**
 * Phase 14R.5 — domain failure while linking an Emergency case, Admission
 * Request or Admission to a maternity record.
 *
 * Uses the same stable codes as the 14R.2 consultation bridge so tests and
 * callers branch on a code rather than translated text.
 */
class MaternityLinkException extends RuntimeException
{
    public const UNSUPPORTED_TARGET = 'unsupported_target';
    public const INVALID_TARGET = 'invalid_target';
    public const PATIENT_MISMATCH = 'patient_mismatch';
    public const INCONSISTENT_CONTEXT = 'inconsistent_context';
    public const RELINK_REQUIRED = 'relink_required';
    public const REASON_REQUIRED = 'reason_required';
    public const LINK_NOT_FOUND = 'link_not_found';

    public function __construct(
        public readonly string $errorCode,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function fromTargetFailure(MaternityContextTargetException $e): self
    {
        return match ($e->errorCode) {
            MaternityContextTargetException::UNSUPPORTED_TARGET
                => self::unsupportedTarget($e->targetClass ?? 'target'),
            MaternityContextTargetException::INVALID_TARGET => self::invalidTarget(),
            MaternityContextTargetException::PATIENT_MISMATCH => self::patientMismatch(),
            default => self::inconsistentContext(),
        };
    }

    public static function unsupportedTarget(string $class): self
    {
        return new self(
            self::UNSUPPORTED_TARGET,
            __('consultation_maternity.errors.unsupported_target', ['target' => class_basename($class)]),
        );
    }

    public static function invalidTarget(): self
    {
        return new self(self::INVALID_TARGET, __('consultation_maternity.errors.invalid_target'));
    }

    public static function patientMismatch(): self
    {
        return new self(self::PATIENT_MISMATCH, __('consultation_maternity.errors.patient_mismatch'));
    }

    public static function inconsistentContext(): self
    {
        return new self(self::INCONSISTENT_CONTEXT, __('consultation_maternity.errors.inconsistent_context'));
    }

    public static function relinkRequired(): self
    {
        return new self(self::RELINK_REQUIRED, __('consultation_maternity.errors.relink_required'));
    }

    public static function reasonRequired(): self
    {
        return new self(self::REASON_REQUIRED, __('consultation_maternity.errors.reason_required'));
    }

    public static function linkNotFound(): self
    {
        return new self(self::LINK_NOT_FOUND, __('consultation_maternity.errors.link_not_found'));
    }
}
