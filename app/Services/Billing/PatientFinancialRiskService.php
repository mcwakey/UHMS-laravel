<?php

namespace App\Services\Billing;

use App\Data\Billing\PatientFinancialRiskData;
use App\Enums\LogModule;
use App\Enums\PatientFinancialRiskEvent;
use App\Enums\PatientFinancialRiskStatus;
use App\Exceptions\InvalidFinancialRiskTransitionException;
use App\Models\Patient;
use App\Models\PatientFinancialRiskHistory;
use App\Models\PatientFinancialRiskProfile;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Central service for patient financial-risk profiles (Payment Timing Policy
 * Phase 5).
 *
 * Guarantees:
 * - Every material mutation runs inside a DB transaction with row locking so a
 *   patient can never end up with two active-slot profiles.
 * - Every mutation appends an immutable history row (the authoritative in-txn
 *   audit) AND writes an ActivityLog entry.
 * - It NEVER calls PaymentGateService, VisitPaymentTimingResolver or override
 *   services, and never touches invoices, receivables or visits. It changes no
 *   production payment outcome.
 */
class PatientFinancialRiskService
{
    public function __construct(private readonly ActivityLogService $activityLog) {}

    /** The current active-slot profile for a patient, if any. */
    public function currentFor(Patient $patient): ?PatientFinancialRiskProfile
    {
        return PatientFinancialRiskProfile::query()
            ->where('patient_id', $patient->id)
            ->active()
            ->latest('id')
            ->first();
    }

    /**
     * Create a new classification, or — if the patient already has an active-slot
     * profile — mutate that current profile. Never creates a duplicate active
     * profile. This never classifies a patient automatically; a caller (a user)
     * must supply the classification.
     */
    public function createOrClassify(Patient $patient, PatientFinancialRiskData $data, User $actor): PatientFinancialRiskProfile
    {
        return DB::transaction(function () use ($patient, $data, $actor) {
            $current = $this->lockCurrent($patient);

            if ($current !== null) {
                return $this->applyUpdate($current, $data, $actor);
            }

            $profile = new PatientFinancialRiskProfile(array_merge($data->toAttributes(), [
                'patient_id' => $patient->id,
                'status' => PatientFinancialRiskStatus::ACTIVE->value,
                'set_by' => $actor->id,
            ]));
            $profile->save();

            $this->record($profile, PatientFinancialRiskEvent::CREATED, $actor, [], $this->snapshot($profile), $data->reasonDetails);

            return $profile->refresh();
        });
    }

    public function update(PatientFinancialRiskProfile $profile, PatientFinancialRiskData $data, User $actor): PatientFinancialRiskProfile
    {
        return DB::transaction(function () use ($profile, $data, $actor) {
            $profile = $profile->newQuery()->lockForUpdate()->findOrFail($profile->id);

            return $this->applyUpdate($profile, $data, $actor);
        });
    }

    public function submitForReview(PatientFinancialRiskProfile $profile, User $actor, ?string $reason = null): PatientFinancialRiskProfile
    {
        return $this->transition($profile, PatientFinancialRiskStatus::UNDER_REVIEW, PatientFinancialRiskEvent::SUBMITTED_FOR_REVIEW, $actor, $reason);
    }

    public function completeReview(PatientFinancialRiskProfile $profile, User $actor, ?string $reason = null): PatientFinancialRiskProfile
    {
        return $this->transition(
            $profile,
            PatientFinancialRiskStatus::ACTIVE,
            PatientFinancialRiskEvent::REVIEW_COMPLETED,
            $actor,
            $reason,
            fn (PatientFinancialRiskProfile $p) => $p->forceFill([
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
            ]),
        );
    }

    public function suspend(PatientFinancialRiskProfile $profile, User $actor, string $reason): PatientFinancialRiskProfile
    {
        return $this->transition(
            $profile,
            PatientFinancialRiskStatus::SUSPENDED,
            PatientFinancialRiskEvent::SUSPENDED,
            $actor,
            $reason,
            fn (PatientFinancialRiskProfile $p) => $p->forceFill([
                'suspended_by' => $actor->id,
                'suspended_at' => now(),
            ]),
        );
    }

    public function reactivate(PatientFinancialRiskProfile $profile, User $actor, string $reason): PatientFinancialRiskProfile
    {
        return $this->transition(
            $profile,
            PatientFinancialRiskStatus::ACTIVE,
            PatientFinancialRiskEvent::REACTIVATED,
            $actor,
            $reason,
            fn (PatientFinancialRiskProfile $p) => $p->forceFill([
                'suspended_by' => null,
                'suspended_at' => null,
            ]),
        );
    }

    public function clear(PatientFinancialRiskProfile $profile, User $actor, string $reason): PatientFinancialRiskProfile
    {
        return $this->transition(
            $profile,
            PatientFinancialRiskStatus::CLEARED,
            PatientFinancialRiskEvent::CLEARED,
            $actor,
            $reason,
            fn (PatientFinancialRiskProfile $p) => $p->forceFill([
                'cleared_by' => $actor->id,
                'cleared_at' => now(),
                'clearance_reason' => $reason,
            ]),
        );
    }

    /**
     * Transition every due active/under-review/suspended profile to EXPIRED.
     * Idempotent — already-cleared/expired profiles are never touched. Returns
     * the number of profiles expired.
     */
    public function expireDueProfiles(?Carbon $asOf = null): int
    {
        $asOf ??= now();
        $expired = 0;

        PatientFinancialRiskProfile::query()
            ->expired($asOf)
            ->orderBy('id')
            ->each(function (PatientFinancialRiskProfile $profile) use ($asOf, &$expired): void {
                DB::transaction(function () use ($profile, $asOf, &$expired): void {
                    $locked = $profile->newQuery()->lockForUpdate()->find($profile->id);
                    // Re-check inside the lock: another worker may have transitioned it.
                    if ($locked === null || $locked->status->isTerminal() || ! $locked->isPastExpiry($asOf)) {
                        return;
                    }

                    $this->transition(
                        $locked,
                        PatientFinancialRiskStatus::EXPIRED,
                        PatientFinancialRiskEvent::EXPIRED,
                        null,
                        null,
                        null,
                        locked: true,
                    );
                    $expired++;
                });
            });

        return $expired;
    }

    /*
    |--------------------------------------------------------------------------
    | Internals
    |--------------------------------------------------------------------------
    */

    private function applyUpdate(PatientFinancialRiskProfile $profile, PatientFinancialRiskData $data, User $actor): PatientFinancialRiskProfile
    {
        $old = $this->snapshot($profile);
        $profile->fill($data->toAttributes());

        if (! $profile->isDirty()) {
            return $profile; // nothing changed; no history, no audit
        }

        $profile->save();
        $this->record($profile, PatientFinancialRiskEvent::UPDATED, $actor, $old, $this->snapshot($profile), $data->reasonDetails);

        return $profile->refresh();
    }

    /**
     * @param  null|callable(PatientFinancialRiskProfile):mixed  $mutate
     */
    private function transition(
        PatientFinancialRiskProfile $profile,
        PatientFinancialRiskStatus $to,
        PatientFinancialRiskEvent $event,
        ?User $actor,
        ?string $reason,
        ?callable $mutate = null,
        bool $locked = false,
    ): PatientFinancialRiskProfile {
        $runner = function () use ($profile, $to, $event, $actor, $reason, $mutate, $locked) {
            $profile = $locked
                ? $profile
                : $profile->newQuery()->lockForUpdate()->findOrFail($profile->id);

            $from = $profile->status;
            if (! $from->canTransitionTo($to)) {
                throw InvalidFinancialRiskTransitionException::between($from, $to);
            }

            $old = $this->snapshot($profile);
            $profile->status = $to;
            if ($mutate !== null) {
                $mutate($profile);
            }
            $profile->save();

            $this->record($profile, $event, $actor, $old, $this->snapshot($profile), $reason);

            return $profile->refresh();
        };

        // When already inside a locked transaction (expiry), don't nest a new one.
        return $locked ? $runner() : DB::transaction($runner);
    }

    /**
     * Append the immutable history row (authoritative in-transaction audit) and
     * write the ActivityLog entry. Both happen inside the caller's transaction.
     *
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    private function record(
        PatientFinancialRiskProfile $profile,
        PatientFinancialRiskEvent $event,
        ?User $actor,
        array $old,
        array $new,
        ?string $reason,
    ): void {
        PatientFinancialRiskHistory::create([
            'patient_financial_risk_profile_id' => $profile->id,
            'patient_id' => $profile->patient_id,
            'event_type' => $event->value,
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
            'reason' => $reason,
            'performed_by' => $actor?->id,
            'performed_at' => now(),
        ]);

        $this->activityLog->log(
            LogModule::BILLING,
            $event->activityAction(),
            [
                'patient_id' => $profile->patient_id,
                'old_values' => $this->auditValues($old),
                'new_values' => $this->auditValues($new),
                'reason' => $reason,
                'metadata' => [
                    'profile_id' => $profile->id,
                    'risk_level' => $profile->risk_level->value,
                    'status' => $profile->status->value,
                ],
            ],
            $profile,
            $actor ? null : 'Financial-risk profile expired by scheduled process',
        );
    }

    private function lockCurrent(Patient $patient): ?PatientFinancialRiskProfile
    {
        return PatientFinancialRiskProfile::query()
            ->where('patient_id', $patient->id)
            ->active()
            ->lockForUpdate()
            ->latest('id')
            ->first();
    }

    /**
     * Material fields captured in history/audit — never full patient snapshots
     * or personal data.
     *
     * @return array<string, mixed>
     */
    private function snapshot(PatientFinancialRiskProfile $profile): array
    {
        return [
            'risk_level' => $profile->risk_level?->value,
            'status' => $profile->status?->value,
            'primary_reason' => $profile->primary_reason?->value,
            'credit_limit' => $profile->credit_limit,
            'effective_from' => optional($profile->effective_from)->toDateString(),
            'review_due_at' => optional($profile->review_due_at)->toDateString(),
            'expires_at' => optional($profile->expires_at)->toDateString(),
            'reference' => $profile->reference,
        ];
    }

    /**
     * Audit metadata excludes free-text reason details (kept only in history).
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function auditValues(array $values): array
    {
        unset($values['reference']);

        return $values;
    }
}
