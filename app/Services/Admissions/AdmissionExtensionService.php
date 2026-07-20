<?php

namespace App\Services\Admissions;

use App\Enums\AdmissionStatus;
use App\Enums\LogModule;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Admission;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\AdmissionBedBillingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdmissionExtensionService
{
    public function __construct(
        private readonly AdmissionBedBillingService $billing,
        private readonly ActivityLogService $activityLog,
    ) {}

    public function extend(Admission $admission, User $user, string $reason): Admission
    {
        if (! $user->can('admissions.extend') && ! $user->can('admissions.readmit')) {
            throw ValidationException::withMessages(['permission' => __('consultations.reopen.permission_denied')]);
        }

        $this->assertWithinExtensionWindow($admission);

        if (blank($reason) || mb_strlen($reason) < 5) {
            throw ValidationException::withMessages(['reason' => __('validation.min.string', ['attribute' => 'reason', 'min' => 5])]);
        }

        return DB::transaction(function () use ($admission, $user, $reason) {
            $admission->loadMissing(['visit', 'bed']);

            $duplicateActive = Admission::query()
                ->where('patient_id', $admission->patient_id)
                ->whereKeyNot($admission->id)
                ->whereIn('status', [AdmissionStatus::ADMITTED->value, AdmissionStatus::ON_LEAVE->value])
                ->whereNull('actual_discharge_date')
                ->exists();

            if ($duplicateActive) {
                throw ValidationException::withMessages(['admission' => __('admissions.duplicate_active_admission')]);
            }

            $previousStatus = $admission->status;
            $previousDischargeDate = $admission->actual_discharge_date;

            $admission->forceFill([
                'status' => AdmissionStatus::ADMITTED,
                'actual_discharge_date' => null,
                'discharged_by' => null,
            ])->save();

            if ($admission->bed) {
                $admission->bed->markOccupied($user->id, __('admissions.extend_admission'));
            }

            $visit = $admission->visit;
            if ($visit) {
                $visit->forceFill([
                    'visit_type' => VisitType::INPATIENT,
                    'status' => VisitStatus::ADMITTED,
                    'completed_at' => null,
                    'completed_by' => null,
                    'checked_out_at' => null,
                ])->save();

                $this->billing->createInitialCharges($admission->fresh(['visit', 'bed.ward']), []);
            }

            $this->activityLog->log(
                LogModule::ADMISSION,
                'ADMISSION_EXTENDED_FOR_CONTINUED_CARE',
                [
                    'patient_id' => $admission->patient_id,
                    'visit_id' => $admission->visit_id,
                    'admission_id' => $admission->id,
                    'causer' => $user,
                    'reason' => $reason,
                    'metadata' => [
                        'previous_status' => $previousStatus?->value ?? $previousStatus,
                        'previous_discharge_date' => $previousDischargeDate?->toDateTimeString(),
                    ],
                ],
                $admission,
                'Admission re-admitted or extended for continued inpatient care.',
            );

            return $admission->fresh(['visit', 'bed.ward']);
        });
    }

    public function canExtend(Admission $admission): bool
    {
        return $admission->status === AdmissionStatus::DISCHARGED
            && $admission->actual_discharge_date !== null
            && $this->hospitalDay($admission->actual_discharge_date)->isSameDay($this->today());
    }

    public function assertWithinExtensionWindow(Admission $admission): void
    {
        if (! $this->canExtend($admission)) {
            throw ValidationException::withMessages([
                'admission' => __('admissions.extension_window_expired'),
            ]);
        }
    }

    private function today(): Carbon
    {
        return Carbon::today(config('app.timezone'));
    }

    private function hospitalDay($value): Carbon
    {
        return Carbon::parse($value)->timezone(config('app.timezone'));
    }
}
