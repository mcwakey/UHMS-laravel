<?php

namespace App\Enums;

/**
 * Phase 9.9 — explainable breach-risk level for an active handoff.
 *  LOW      unlikely to breach soon
 *  MEDIUM   nearing SLA or a slow historical path
 *  HIGH     likely to breach without action
 *  CRITICAL already breached or likely critical breach soon
 */
enum JourneyRiskLevel: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';

    public function translatedLabel(): string
    {
        return __('journey.risk.level.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::LOW => 'success',
            self::MEDIUM => 'info',
            self::HIGH => 'warning',
            self::CRITICAL => 'danger',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::LOW => 'ti-shield-check',
            self::MEDIUM => 'ti-alert-circle',
            self::HIGH => 'ti-alert-triangle',
            self::CRITICAL => 'ti-flame',
        };
    }

    public function priorityRank(): int
    {
        return match ($this) {
            self::LOW => 0,
            self::MEDIUM => 1,
            self::HIGH => 2,
            self::CRITICAL => 3,
        };
    }

    /** routine | watch | prioritize | urgent */
    public function recommendedPriority(): string
    {
        return match ($this) {
            self::LOW => 'routine',
            self::MEDIUM => 'watch',
            self::HIGH => 'prioritize',
            self::CRITICAL => 'urgent',
        };
    }

    /** Map a 0–100 score to a level (callers may upgrade for already-breached SLA). */
    public static function fromScore(int $score): self
    {
        return match (true) {
            $score >= 80 => self::CRITICAL,
            $score >= 55 => self::HIGH,
            $score >= 30 => self::MEDIUM,
            default => self::LOW,
        };
    }
}
