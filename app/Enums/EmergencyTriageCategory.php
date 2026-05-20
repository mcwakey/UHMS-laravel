<?php

namespace App\Enums;

/**
 * Emergency triage acuity categories (5-tier ESI / South African Triage Scale style).
 *
 * Maps cleanly onto the existing {@see \App\Enums\TriageScore} and
 * {@see \App\Enums\Priority} enums so Emergency cases participate in the
 * same prioritised queues as OPD without divergence.
 */
enum EmergencyTriageCategory: string
{
    case RED    = 'red';     // Immediate / resuscitation
    case ORANGE = 'orange';  // Very urgent (≤10 min)
    case YELLOW = 'yellow';  // Urgent (≤60 min)
    case GREEN  = 'green';   // Standard / non-urgent
    case BLACK  = 'black';   // Deceased / no resuscitation

    public function label(): string
    {
        return match ($this) {
            self::RED    => 'Red — Resuscitation',
            self::ORANGE => 'Orange — Very Urgent',
            self::YELLOW => 'Yellow — Urgent',
            self::GREEN  => 'Green — Non-Urgent',
            self::BLACK  => 'Black — Deceased',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::RED    => 'danger',
            self::ORANGE => 'warning',
            self::YELLOW => 'info',
            self::GREEN  => 'success',
            self::BLACK  => 'dark',
        };
    }

    /** Target time-to-be-seen in minutes. */
    public function targetMinutes(): ?int
    {
        return match ($this) {
            self::RED    => 0,
            self::ORANGE => 10,
            self::YELLOW => 60,
            self::GREEN  => 240,
            self::BLACK  => null,
        };
    }

    /** Sort weight (lower = higher priority). */
    public function weight(): int
    {
        return match ($this) {
            self::RED    => 1,
            self::ORANGE => 2,
            self::YELLOW => 3,
            self::GREEN  => 4,
            self::BLACK  => 5,
        };
    }

    public function toPriority(): Priority
    {
        return match ($this) {
            self::RED, self::ORANGE => Priority::EMERGENCY,
            self::YELLOW            => Priority::URGENT,
            self::GREEN, self::BLACK => Priority::NORMAL,
        };
    }

    public function toTriageScore(): TriageScore
    {
        return match ($this) {
            self::RED, self::ORANGE => TriageScore::EMERGENCY,
            self::YELLOW            => TriageScore::URGENT,
            self::GREEN, self::BLACK => TriageScore::ROUTINE,
        };
    }
}
