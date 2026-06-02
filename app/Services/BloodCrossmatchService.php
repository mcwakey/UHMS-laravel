<?php

namespace App\Services;

use App\Models\BloodCrossmatch;
use App\Models\BloodRequest;
use App\Models\BloodUnit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BloodCrossmatchService
{
    public function __construct(private BloodBankCompatibilityService $compatibility) {}

    public function perform(BloodRequest $request, BloodUnit $unit, User $user, ?string $method = null, ?string $notes = null): BloodCrossmatch
    {
        if ($unit->isExpired()) {
            throw ValidationException::withMessages(['blood_unit_id' => 'Expired blood units cannot be crossmatched.']);
        }

        if ($unit->screening_status !== BloodUnit::SCREENING_PASSED) {
            throw ValidationException::withMessages(['blood_unit_id' => 'Only screened and passed blood units can be crossmatched.']);
        }

        if (! in_array($unit->status, [BloodUnit::STATUS_AVAILABLE, BloodUnit::STATUS_RESERVED, BloodUnit::STATUS_CROSSMATCHED], true)) {
            throw ValidationException::withMessages(['blood_unit_id' => 'This blood unit is not available for crossmatch.']);
        }

        $result = $this->compatibility->isCompatible($unit->blood_group, $request->blood_group)
            ? BloodCrossmatch::RESULT_COMPATIBLE
            : BloodCrossmatch::RESULT_INCOMPATIBLE;

        return DB::transaction(function () use ($request, $unit, $user, $method, $notes, $result) {
            $crossmatch = BloodCrossmatch::updateOrCreate(
                [
                    'blood_request_id' => $request->id,
                    'blood_unit_id' => $unit->id,
                ],
                [
                    'visit_id' => $request->visit_id,
                    'patient_id' => $request->patient_id,
                    'performed_by' => $user->id,
                    'performed_at' => now(),
                    'result' => $result,
                    'method' => $method,
                    'notes' => $notes,
                ]
            );

            if ($result === BloodCrossmatch::RESULT_COMPATIBLE) {
                $unit->update([
                    'status' => BloodUnit::STATUS_CROSSMATCHED,
                    'crossmatch_status' => BloodUnit::CROSSMATCH_COMPATIBLE,
                    'reserved_for_request_id' => $request->id,
                ]);
            } else {
                $unit->update(['crossmatch_status' => BloodUnit::CROSSMATCH_INCOMPATIBLE]);
            }

            return $crossmatch->load('unit', 'request');
        });
    }
}
