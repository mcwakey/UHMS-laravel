<?php

namespace App\Enums;

enum EmergencyCaseStatus: string
{
    case REGISTERED          = 'registered';
    case TRIAGED             = 'triaged';
    case IN_TREATMENT        = 'in_treatment';
    case IN_OBSERVATION      = 'in_observation';
    case AWAITING_ADMISSION  = 'awaiting_admission';
    case AWAITING_TRANSFER   = 'awaiting_transfer';
    case DISPOSED            = 'disposed';
    case CLOSED              = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::REGISTERED         => 'Registered',
            self::TRIAGED            => 'Triaged',
            self::IN_TREATMENT       => 'In Treatment',
            self::IN_OBSERVATION     => 'In Observation',
            self::AWAITING_ADMISSION => 'Awaiting Admission',
            self::AWAITING_TRANSFER  => 'Awaiting Transfer',
            self::DISPOSED           => 'Disposed',
            self::CLOSED             => 'Closed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::REGISTERED         => 'secondary',
            self::TRIAGED            => 'info',
            self::IN_TREATMENT       => 'primary',
            self::IN_OBSERVATION     => 'warning',
            self::AWAITING_ADMISSION => 'teal',
            self::AWAITING_TRANSFER  => 'purple',
            self::DISPOSED           => 'success',
            self::CLOSED             => 'dark',
        };
    }

    public function isOpen(): bool
    {
        return ! in_array($this, [self::DISPOSED, self::CLOSED], true);
    }
}
