<?php

namespace App\Exceptions;

use App\Enums\PatientFinancialRiskStatus;
use RuntimeException;

/**
 * Thrown when a patient financial-risk profile is asked to make a status
 * transition that is not permitted (Payment Timing Policy Phase 5).
 */
class InvalidFinancialRiskTransitionException extends RuntimeException
{
    public static function between(PatientFinancialRiskStatus $from, PatientFinancialRiskStatus $to): self
    {
        return new self("Cannot transition financial-risk profile from '{$from->value}' to '{$to->value}'.");
    }

    public static function activeSlotOccupied(): self
    {
        return new self('The patient already has an active financial-risk profile.');
    }
}
