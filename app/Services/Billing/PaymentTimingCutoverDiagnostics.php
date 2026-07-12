<?php

namespace App\Services\Billing;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Bounded, non-sensitive runtime diagnostics for the payment-timing cutover
 * (Payment Timing Policy Phase 8). These are application-log events, NOT
 * activity logs — routine successful decisions are not logged. It excludes
 * patient names/contacts/diagnoses/free-text and never affects the gate: any
 * logging failure is swallowed. Repeated events are de-duplicated per request.
 */
class PaymentTimingCutoverDiagnostics
{
    public const OBSERVED = 'payment_timing_typed_decision_observed';
    public const APPLIED = 'payment_timing_typed_decision_applied';
    public const LEGACY_FALLBACK = 'payment_timing_legacy_fallback';
    public const ARRANGEMENT_INELIGIBLE = 'payment_timing_arrangement_ineligible';
    public const OVERRIDE_CONFLICT = 'payment_timing_override_conflict';
    public const EMERGENCY_SCOPE_FALLBACK = 'payment_timing_emergency_scope_fallback';

    /** @var array<string, true> de-dup keys for this request */
    private array $seen = [];

    /**
     * @param  array<string, mixed>  $context  bounded, non-sensitive keys only
     */
    public function record(string $event, array $context = []): void
    {
        try {
            $key = $event.'|'.($context['visit_id'] ?? '').'|'.($context['operation'] ?? '').'|'.($context['reason'] ?? '');
            if (isset($this->seen[$key])) {
                return;
            }
            $this->seen[$key] = true;

            Log::channel(config('logging.default'))->info('payment_timing_cutover', array_merge(
                ['event' => $event],
                array_intersect_key($context, array_flip([
                    'visit_id', 'operation', 'cutover_mode', 'authority',
                    'legacy_allowed', 'typed_allowed', 'policy', 'source', 'reason',
                ])),
            ));
        } catch (Throwable) {
            // Diagnostics must never affect the gate.
        }
    }
}
