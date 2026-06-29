<?php

namespace App\Services\Journey;

use App\Enums\JourneyDelayCause;

/**
 * Evaluates cross-department handoff SLAs from config/journey.php — query-free, so
 * it is safe to call in both the light aggregation path and the full resolver.
 */
class JourneySlaService
{
    public const WITHIN = 'within';

    public const NEAR_BREACH = 'near_breach';

    public const BREACHED = 'breached';

    public const CRITICAL_BREACH = 'critical_breach';

    /** Target minutes for resolving a delay cause. */
    public function slaFor(JourneyDelayCause $cause): int
    {
        return (int) config('journey.cause_sla.'.$cause->value, config('journey.default_cause_sla', 90));
    }

    /**
     * @return array{sla_minutes:int,sla_status:string,minutes_to_breach:int}
     */
    public function evaluate(JourneyDelayCause $cause, int $elapsedMinutes): array
    {
        $sla = $this->slaFor($cause);
        $nearRatio = (float) config('journey.sla.near_breach_ratio', 0.8);
        $criticalMultiplier = (float) config('journey.sla.critical_breach_multiplier', 2);

        $status = match (true) {
            $elapsedMinutes >= $sla * $criticalMultiplier => self::CRITICAL_BREACH,
            $elapsedMinutes >= $sla => self::BREACHED,
            $elapsedMinutes >= $sla * $nearRatio => self::NEAR_BREACH,
            default => self::WITHIN,
        };

        return [
            'sla_minutes' => $sla,
            'sla_status' => $status,
            'minutes_to_breach' => $sla - $elapsedMinutes, // negative once overdue
        ];
    }

    /** Sort weight: worse SLA states first. */
    public function rank(string $slaStatus): int
    {
        return match ($slaStatus) {
            self::CRITICAL_BREACH => 4,
            self::BREACHED => 3,
            self::NEAR_BREACH => 2,
            default => 1,
        };
    }

    public function isBreached(string $slaStatus): bool
    {
        return in_array($slaStatus, [self::BREACHED, self::CRITICAL_BREACH], true);
    }

    /** Bootstrap badge variant for a SLA status. */
    public function variant(string $slaStatus): string
    {
        return match ($slaStatus) {
            self::CRITICAL_BREACH, self::BREACHED => 'danger',
            self::NEAR_BREACH => 'warning',
            default => 'success',
        };
    }
}
