<?php

namespace App\Observers;

use App\Models\Visit;
use App\Services\Billing\VisitPaymentPolicyMaterializationService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Central, single integration point for visit-payment-policy materialisation
 * (Payment Timing Policy Phase 6). All visit-creation paths funnel through
 * Visit::create, so this `created` hook covers outpatient, inpatient, emergency,
 * appointment-generated and walk-in visits without duplicated controller calls.
 *
 * Safety: materialisation is OBSERVATIONAL. Failure here must never block visit
 * creation or emergency care — errors are swallowed and logged, and the visit
 * can be repaired later via `billing:visit-payment-policy-backfill`. The auto
 * path writes the record + history but NOT an ActivityLog (kept lightweight for
 * the clinical hot path; explicit refresh/backfill do log).
 */
class VisitObserver
{
    public function created(Visit $visit): void
    {
        if (! config('visit_payment_policy.auto_materialize', true)) {
            return;
        }

        try {
            app(VisitPaymentPolicyMaterializationService::class)->materialize($visit, actor: null, logActivity: false);
        } catch (Throwable $e) {
            Log::warning('Visit payment policy auto-materialisation failed; visit unaffected.', [
                'visit_id' => $visit->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
