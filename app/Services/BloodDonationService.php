<?php

namespace App\Services;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Enums\NotificationModule;
use App\Enums\NotificationPriority;
use App\Models\BloodDonation;
use App\Models\BloodDonationTest;
use App\Models\BloodDonor;
use App\Models\BloodUnit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BloodDonationService
{
    public function __construct(
        private ActivityLogService $log,
        private NotificationService $notifier,
    ) {}

    public function recordDonation(BloodDonor $donor, array $data, User $user, bool $override = false): BloodDonation
    {
        // WHO rule: collection only from eligible donors unless explicitly overridden.
        if (! $override && ! $donor->canDonate()) {
            throw ValidationException::withMessages([
                'donor_id' => 'Donor is not eligible for donation. Complete screening or use an authorised override.',
            ]);
        }

        if ($override && empty($data['override_reason'])) {
            throw ValidationException::withMessages([
                'override_reason' => 'A reason is required to override donor eligibility.',
            ]);
        }

        return DB::transaction(function () use ($donor, $data, $user, $override) {
            $component = strtoupper($data['component_type'] ?? 'WHOLE_BLOOD');
            $donationDate = isset($data['donation_date']) ? \Illuminate\Support\Carbon::parse($data['donation_date']) : now();

            $donation = BloodDonation::create([
                'donation_number' => BloodDonation::generateDonationNumber(),
                'donor_id' => $donor->id,
                'collected_by' => $user->id,
                'donation_date' => $donationDate,
                'donation_type' => $data['donation_type'] ?? 'WHOLE_BLOOD',
                'volume_ml' => $data['volume_ml'] ?? 450,
                'blood_group' => $data['blood_group'] ?? $donor->blood_group,
                'screening_status' => BloodDonation::SCREENING_PENDING,
                'status' => BloodDonation::STATUS_COLLECTED,
                'notes' => $data['notes'] ?? ($override ? 'Eligibility overridden: '.($data['override_reason'] ?? '') : null),
            ]);

            BloodUnit::create([
                'unit_number' => BloodUnit::generateUnitNumber(),
                'donation_id' => $donation->id,
                'donor_id' => $donor->id,
                'blood_group' => $donation->blood_group,
                'component_type' => $component,
                'volume_ml' => $donation->volume_ml,
                'collection_date' => $donation->donation_date,
                'expiry_date' => $data['expiry_date'] ?? $donationDate->copy()->addDays($this->expiryDays($component)),
                'storage_location_id' => $data['storage_location_id'] ?? null,
                'screening_status' => BloodUnit::SCREENING_PENDING,
                'status' => BloodUnit::STATUS_QUARANTINED,
                'created_by' => $user->id,
            ]);

            // Seed the configured infectious-disease screening panel.
            foreach ((array) config('blood_bank.screening_tests') as $code => $meta) {
                $donation->tests()->create([
                    'test_code' => $code,
                    'test_name' => $meta['label'] ?? $code,
                    'mandatory' => (bool) ($meta['mandatory'] ?? true),
                    'result' => BloodDonationTest::RESULT_NOT_DONE,
                ]);
            }

            $donor->update([
                'blood_group' => $donation->blood_group,
                'last_donation_at' => $donation->donation_date,
            ]);

            $this->log->log(LogModule::BLOOD_BANK, 'DONATION_COLLECTED', [
                'description' => "Donation {$donation->donation_number} collected from {$donor->donor_number}; unit quarantined pending screening.",
                'causer' => $user,
                'severity' => $override ? LogSeverity::WARNING : LogSeverity::INFO,
            ], $donation);

            return $donation->load('unit', 'donor', 'tests');
        });
    }

    /** Record (or update) a single infectious-disease screening test result. */
    public function recordTest(BloodDonation $donation, string $testCode, string $result, User $user, ?string $notes = null): BloodDonationTest
    {
        $meta = config("blood_bank.screening_tests.{$testCode}");

        $test = $donation->tests()->updateOrCreate(
            ['test_code' => $testCode],
            [
                'test_name' => $meta['label'] ?? $testCode,
                'mandatory' => (bool) ($meta['mandatory'] ?? true),
                'result' => strtoupper($result),
                'performed_by' => $user->id,
                'performed_at' => now(),
                'notes' => $notes,
                // Re-verification is required after a result change.
                'verified_by' => null,
                'verified_at' => null,
            ]
        );

        $this->log->log(LogModule::BLOOD_BANK, 'DONATION_SCREENING_RESULT', [
            'description' => "Screening {$test->test_name} = {$test->result} for {$donation->donation_number}",
            'causer' => $user,
        ], $test);

        $this->reconcileScreening($donation->refresh(), $user);

        return $test;
    }

    public function verifyTest(BloodDonationTest $test, User $user): BloodDonationTest
    {
        $test->update(['verified_by' => $user->id, 'verified_at' => now()]);

        $this->log->log(LogModule::BLOOD_BANK, 'DONATION_SCREENING_VERIFIED', [
            'description' => "Screening {$test->test_name} verified for donation #{$test->donation_id}",
            'causer' => $user,
        ], $test);

        $this->reconcileScreening($test->donation->refresh(), $user);

        return $test;
    }

    /**
     * Derive overall screening status from individual tests and apply it to the
     * donation + unit. A unit can only become AVAILABLE once screening passes.
     */
    public function reconcileScreening(BloodDonation $donation, User $user): BloodDonation
    {
        $tests = $donation->tests()->get();
        $mandatory = $tests->where('mandatory', true);
        $requireVerify = (bool) config('blood_bank.require_test_verification', true);

        $hasFailing = $tests->contains(fn ($t) => $t->isFailing());
        $hasInconclusive = $tests->contains(fn ($t) => $t->result === BloodDonationTest::RESULT_INCONCLUSIVE);

        $allMandatoryPassing = $mandatory->isNotEmpty()
            && $mandatory->every(fn ($t) => $t->isPassing());
        $allMandatoryVerified = ! $requireVerify || $mandatory->every(fn ($t) => $t->verified_at !== null);

        $status = match (true) {
            $hasFailing => BloodDonation::SCREENING_FAILED,
            $hasInconclusive => BloodDonation::SCREENING_INCONCLUSIVE,
            $allMandatoryPassing && $allMandatoryVerified => BloodDonation::SCREENING_PASSED,
            default => BloodDonation::SCREENING_PENDING,
        };

        // Only auto-apply terminal/clear states; PENDING just keeps quarantine.
        if ($status !== $donation->screening_status) {
            $this->applyScreeningOutcome($donation, $status, $user);
        }

        return $donation->refresh();
    }

    /**
     * Manually set/override overall screening status (kept for the existing
     * controller + tests) and cascade to the unit safely.
     */
    public function updateScreening(BloodDonation $donation, string $screeningStatus, User $user, ?string $notes = null): BloodDonation
    {
        return $this->applyScreeningOutcome($donation, strtoupper($screeningStatus), $user, $notes);
    }

    protected function applyScreeningOutcome(BloodDonation $donation, string $screeningStatus, User $user, ?string $notes = null): BloodDonation
    {
        return DB::transaction(function () use ($donation, $screeningStatus, $user, $notes) {
            $donation->update([
                'screening_status' => $screeningStatus,
                'screening_notes' => $notes ?? $donation->screening_notes,
                'screened_by' => $user->id,
                'screened_at' => now(),
                'status' => match ($screeningStatus) {
                    BloodDonation::SCREENING_PASSED => BloodDonation::STATUS_ACCEPTED,
                    BloodDonation::SCREENING_FAILED => BloodDonation::STATUS_REJECTED,
                    default => BloodDonation::STATUS_COLLECTED,
                },
            ]);

            if ($unit = $donation->unit) {
                // Never downgrade a unit that has already moved past the bench.
                $locked = in_array($unit->status, [
                    BloodUnit::STATUS_RESERVED, BloodUnit::STATUS_CROSSMATCHED,
                    BloodUnit::STATUS_ISSUED, BloodUnit::STATUS_TRANSFUSED,
                ], true);

                if (! $locked) {
                    $unit->update([
                        'screening_status' => match ($screeningStatus) {
                            BloodDonation::SCREENING_PASSED => BloodUnit::SCREENING_PASSED,
                            BloodDonation::SCREENING_FAILED => BloodUnit::SCREENING_FAILED,
                            default => BloodUnit::SCREENING_PENDING,
                        },
                        'status' => match ($screeningStatus) {
                            BloodDonation::SCREENING_PASSED => BloodUnit::STATUS_AVAILABLE,
                            BloodDonation::SCREENING_FAILED => BloodUnit::STATUS_REJECTED,
                            default => BloodUnit::STATUS_QUARANTINED,
                        },
                        'approved_by' => $screeningStatus === BloodDonation::SCREENING_PASSED ? $user->id : null,
                        'approved_at' => $screeningStatus === BloodDonation::SCREENING_PASSED ? now() : null,
                        'discarded_at' => $screeningStatus === BloodDonation::SCREENING_FAILED ? now() : $unit->discarded_at,
                        'discarded_by' => $screeningStatus === BloodDonation::SCREENING_FAILED ? $user->id : $unit->discarded_by,
                        'discard_reason' => $screeningStatus === BloodDonation::SCREENING_FAILED ? ($notes ?: 'Screening failed') : $unit->discard_reason,
                    ]);
                }

                $this->notifyScreeningOutcome($donation, $unit, $screeningStatus);
            }

            $this->log->log(LogModule::BLOOD_BANK, 'DONATION_SCREENING_'.$screeningStatus, [
                'description' => "Donation {$donation->donation_number} screening {$screeningStatus}.",
                'causer' => $user,
                'severity' => $screeningStatus === BloodDonation::SCREENING_FAILED ? LogSeverity::WARNING : LogSeverity::INFO,
            ], $donation);

            return $donation->refresh()->load('unit', 'donor', 'tests');
        });
    }

    protected function notifyScreeningOutcome(BloodDonation $donation, BloodUnit $unit, string $status): void
    {
        if ($status === BloodDonation::SCREENING_PASSED) {
            $this->notifier->notifyPermission('blood_bank.units.view', [
                'module' => NotificationModule::BLOOD_BANK,
                'priority' => NotificationPriority::NORMAL,
                'title' => 'Blood unit approved',
                'message' => "Unit {$unit->unit_number} ({$unit->blood_group} {$unit->component_type}) passed screening and is available.",
                'url' => url('/admin/blood-bank/units'),
                'source_type' => 'blood_unit',
                'source_id' => $unit->id,
            ]);
        } elseif ($status === BloodDonation::SCREENING_FAILED) {
            $this->notifier->notifyPermission('blood_bank.screening.manage', [
                'module' => NotificationModule::BLOOD_BANK,
                'priority' => NotificationPriority::HIGH,
                'title' => 'Blood screening failed',
                'message' => "Donation {$donation->donation_number} failed screening — unit {$unit->unit_number} rejected.",
                'url' => url('/admin/blood-bank/donations'),
                'source_type' => 'blood_donation',
                'source_id' => $donation->id,
            ]);
        }
    }

    protected function expiryDays(string $component): int
    {
        return (int) (config("blood_bank.component_expiry_days.{$component}")
            ?? config('blood_bank.default_expiry_days', 35));
    }
}
