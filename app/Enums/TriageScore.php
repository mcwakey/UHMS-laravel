<?php

namespace App\Enums;

enum TriageScore: string
{
    case ROUTINE   = 'routine';
    case URGENT    = 'urgent';
    case EMERGENCY = 'emergency';

    public function label(): string
    {
        return match ($this) {
            self::ROUTINE   => 'Routine',
            self::URGENT    => 'Urgent',
            self::EMERGENCY => 'Emergency',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ROUTINE   => 'success',
            self::URGENT    => 'warning',
            self::EMERGENCY => 'danger',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ROUTINE   => 'ti-circle-check',
            self::URGENT    => 'ti-alert-triangle',
            self::EMERGENCY => 'ti-urgent',
        };
    }

    /**
     * Compute triage score from vitals.
     *
     * Simple NEWS-2 inspired scoring:
     *  - SpO2 < 92 or Temp < 35 or > 39.5 or HR < 40 or > 130 or RR < 8 or > 25 → EMERGENCY
     *  - SpO2 < 95 or Temp < 36 or > 38.5 or HR < 50 or > 110 or RR < 12 or > 20 → URGENT
     *  - otherwise → ROUTINE
     */
    public static function compute(array $vitals): self
    {
        $temp  = isset($vitals['temperature']) ? (float) $vitals['temperature'] : null;
        $hr    = isset($vitals['heart_rate']) ? (int) $vitals['heart_rate'] : null;
        $rr    = isset($vitals['respiratory_rate']) ? (int) $vitals['respiratory_rate'] : null;
        $spo2  = isset($vitals['spo2']) ? (int) $vitals['spo2'] : null;
        $sbp   = isset($vitals['blood_pressure_systolic']) ? (int) $vitals['blood_pressure_systolic'] : null;

        // Emergency thresholds
        if (
            ($spo2  !== null && $spo2  < 92) ||
            ($temp  !== null && ($temp < 35.0 || $temp > 39.5)) ||
            ($hr    !== null && ($hr   < 40   || $hr   > 130)) ||
            ($rr    !== null && ($rr   < 8    || $rr   > 25)) ||
            ($sbp   !== null && ($sbp  < 80   || $sbp  > 200))
        ) {
            return self::EMERGENCY;
        }

        // Urgent thresholds
        if (
            ($spo2  !== null && $spo2  < 95) ||
            ($temp  !== null && ($temp < 36.0 || $temp > 38.5)) ||
            ($hr    !== null && ($hr   < 50   || $hr   > 110)) ||
            ($rr    !== null && ($rr   < 12   || $rr   > 20)) ||
            ($sbp   !== null && ($sbp  < 90   || $sbp  > 180))
        ) {
            return self::URGENT;
        }

        return self::ROUTINE;
    }
}
