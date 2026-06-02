<?php

namespace App\Services;

use App\Models\BloodDonation;
use App\Models\BloodDonor;
use App\Models\BloodUnit;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BloodDonationService
{
    public function recordDonation(BloodDonor $donor, array $data, User $user): BloodDonation
    {
        return DB::transaction(function () use ($donor, $data, $user) {
            $donation = BloodDonation::create([
                'donation_number' => BloodDonation::generateDonationNumber(),
                'donor_id' => $donor->id,
                'collected_by' => $user->id,
                'donation_date' => $data['donation_date'] ?? now(),
                'donation_type' => $data['donation_type'] ?? 'WHOLE_BLOOD',
                'volume_ml' => $data['volume_ml'] ?? 450,
                'blood_group' => $data['blood_group'] ?? $donor->blood_group,
                'screening_status' => BloodDonation::SCREENING_PENDING,
                'status' => BloodDonation::STATUS_COLLECTED,
                'notes' => $data['notes'] ?? null,
            ]);

            BloodUnit::create([
                'unit_number' => BloodUnit::generateUnitNumber(),
                'donation_id' => $donation->id,
                'donor_id' => $donor->id,
                'blood_group' => $donation->blood_group,
                'component_type' => $data['component_type'] ?? 'WHOLE_BLOOD',
                'volume_ml' => $donation->volume_ml,
                'collection_date' => $donation->donation_date,
                'expiry_date' => $data['expiry_date'] ?? $donation->donation_date->copy()->addDays(35),
                'storage_location_id' => $data['storage_location_id'] ?? null,
                'screening_status' => BloodUnit::SCREENING_PENDING,
                'status' => BloodUnit::STATUS_QUARANTINED,
                'created_by' => $user->id,
            ]);

            $donor->update([
                'blood_group' => $donation->blood_group,
                'last_donation_at' => $donation->donation_date,
            ]);

            return $donation->load('unit', 'donor');
        });
    }

    public function updateScreening(BloodDonation $donation, string $screeningStatus, User $user, ?string $notes = null): BloodDonation
    {
        return DB::transaction(function () use ($donation, $screeningStatus, $user, $notes) {
            $screeningStatus = strtoupper($screeningStatus);
            $donation->update([
                'screening_status' => $screeningStatus,
                'screening_notes' => $notes,
                'screened_by' => $user->id,
                'screened_at' => now(),
                'status' => $screeningStatus === BloodDonation::SCREENING_PASSED
                    ? BloodDonation::STATUS_ACCEPTED
                    : BloodDonation::STATUS_REJECTED,
            ]);

            if ($unit = $donation->unit) {
                $unit->update([
                    'screening_status' => $screeningStatus,
                    'status' => match ($screeningStatus) {
                        BloodDonation::SCREENING_PASSED => BloodUnit::STATUS_AVAILABLE,
                        BloodDonation::SCREENING_FAILED => BloodUnit::STATUS_DISCARDED,
                        default => BloodUnit::STATUS_QUARANTINED,
                    },
                    'discarded_at' => $screeningStatus === BloodDonation::SCREENING_FAILED ? now() : $unit->discarded_at,
                    'discarded_by' => $screeningStatus === BloodDonation::SCREENING_FAILED ? $user->id : $unit->discarded_by,
                    'discard_reason' => $screeningStatus === BloodDonation::SCREENING_FAILED ? ($notes ?: 'Screening failed') : $unit->discard_reason,
                ]);
            }

            return $donation->refresh()->load('unit', 'donor');
        });
    }
}
