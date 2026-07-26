<?php

namespace App\Enums;

/**
 * Phase 14R.6 — how a clinical act that BOTH a consultation specialty mapping
 * and a maternity event mapping could charge for should be billed.
 *
 * Closed set. The policy is advisory in this phase: it decides which source
 * WOULD be allowed once Phase 14.2 posting exists. Nothing here posts,
 * suppresses or reverses a charge today.
 */
enum MaternityBillingDeduplicationPolicy: string
{
    /** The consultation charge stands; no maternity event charge. */
    case CONSULTATION_ONLY = 'consultation_only';

    /** The maternity event owns the charge; the event-specific
     *  consultation specialty charge is suppressed. */
    case MATERNITY_EVENT_ONLY = 'maternity_event_only';

    /** Genuinely separate services — both may charge. Disabled by default. */
    case BOTH_WHEN_CONFIGURED = 'both_when_configured';

    /** Disputed/edge case: a human decides. Disabled by default. */
    case MANUAL_SELECTION = 'manual_selection';

    public function label(): string
    {
        return __('maternity_billing_policy.policies.'.$this->value);
    }

    /** Whether this policy needs an explicit opt-in flag to be usable. */
    public function requiresConfiguration(): bool
    {
        return in_array($this, [self::BOTH_WHEN_CONFIGURED, self::MANUAL_SELECTION], true);
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
