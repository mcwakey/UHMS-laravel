<?php

namespace App\Enums;

/**
 * Generic, provider-agnostic verification outcome.
 * No driver is hardcoded here — drivers map their own results to these states.
 */
enum VerificationStatus: string
{
    case PENDING = 'pending';        // verification started but awaiting input/response
    case VALID = 'valid';            // membership confirmed for this visit
    case INVALID = 'invalid';        // membership rejected by provider
    case EXPIRED = 'expired';        // local record shows expiry
    case NOT_REQUIRED = 'not_required'; // provider has no verification driver
    case ERROR = 'error';            // driver/transport failure
    case MANUAL_OVERRIDE = 'manual_override'; // operator vouched without provider call

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::VALID => 'Valid',
            self::INVALID => 'Invalid',
            self::EXPIRED => 'Expired',
            self::NOT_REQUIRED => 'Not Required',
            self::ERROR => 'Error',
            self::MANUAL_OVERRIDE => 'Manual Override',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::VALID, self::NOT_REQUIRED => 'success',
            self::PENDING => 'info',
            self::MANUAL_OVERRIDE => 'warning',
            self::INVALID, self::EXPIRED => 'danger',
            self::ERROR => 'secondary',
        };
    }

    public function isAcceptable(): bool
    {
        return in_array($this, [self::VALID, self::NOT_REQUIRED, self::MANUAL_OVERRIDE], true);
    }
}
