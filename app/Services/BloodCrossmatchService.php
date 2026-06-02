<?php

namespace App\Services;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Enums\NotificationModule;
use App\Enums\NotificationPriority;
use App\Models\BloodCrossmatch;
use App\Models\BloodRequest;
use App\Models\BloodUnit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BloodCrossmatchService
{
    public function __construct(
        private BloodBankCompatibilityService $compatibility,
        private ActivityLogService $log,
        private NotificationService $notifier,
    ) {}

    public function perform(
        BloodRequest $request,
        BloodUnit $unit,
        User $user,
        ?string $method = null,
        ?string $notes = null,
        bool $emergencyOverride = false,
        ?string $overrideReason = null,
    ): BloodCrossmatch {
        // Data integrity: recipient details must exist before crossmatch.
        $request->loadMissing('recipient');
        if (! $request->recipient) {
            throw ValidationException::withMessages([
                'blood_request_id' => 'Recipient clinical details are required before crossmatching.',
            ]);
        }

        if ($unit->isExpired()) {
            throw ValidationException::withMessages(['blood_unit_id' => 'Expired blood units cannot be crossmatched.']);
        }

        if ($unit->screening_status !== BloodUnit::SCREENING_PASSED) {
            throw ValidationException::withMessages(['blood_unit_id' => 'Only screened and passed blood units can be crossmatched.']);
        }

        if (! in_array($unit->status, [BloodUnit::STATUS_AVAILABLE, BloodUnit::STATUS_RESERVED, BloodUnit::STATUS_CROSSMATCHED], true)) {
            throw ValidationException::withMessages(['blood_unit_id' => 'This blood unit is not available for crossmatch.']);
        }

        $recipientGroup = $request->recipientGroup();
        $component = $request->recipient->requested_component_type ?: $request->component_type;
        $eval = $this->compatibility->evaluate($recipientGroup, $unit->blood_group, $component);

        // Map compatibility to crossmatch result + status.
        if ($eval['status'] === BloodBankCompatibilityService::INCOMPATIBLE) {
            if (! $emergencyOverride) {
                // Still record the incompatible crossmatch (do not silently drop it).
                $crossmatch = $this->store($request, $unit, $user, $method, $notes, $recipientGroup, $component, BloodCrossmatch::RESULT_INCOMPATIBLE, BloodCrossmatch::COMPAT_INCOMPATIBLE);
                $unit->update(['crossmatch_status' => BloodUnit::CROSSMATCH_INCOMPATIBLE]);

                $this->log->log(LogModule::BLOOD_BANK, 'CROSSMATCH_INCOMPATIBLE', [
                    'description' => "Incompatible crossmatch: unit {$unit->unit_number} ({$unit->blood_group}) vs recipient {$recipientGroup}. {$eval['reason']}",
                    'causer' => $user,
                    'severity' => LogSeverity::WARNING,
                    'patient_id' => $request->patient_id,
                ], $crossmatch);

                return $crossmatch;
            }

            if (empty($overrideReason)) {
                throw ValidationException::withMessages([
                    'override_reason' => 'A reason is required to crossmatch an incompatible unit under emergency override.',
                ]);
            }

            $result = BloodCrossmatch::RESULT_COMPATIBLE; // released for issue under override
            $compatStatus = BloodCrossmatch::COMPAT_EMERGENCY_OVERRIDE;
        } else {
            $result = BloodCrossmatch::RESULT_COMPATIBLE;
            $compatStatus = $eval['status'] === BloodBankCompatibilityService::COMPATIBLE_WITH_CAUTION
                ? BloodCrossmatch::COMPAT_WITH_CAUTION
                : BloodCrossmatch::COMPAT_COMPATIBLE;
        }

        return DB::transaction(function () use ($request, $unit, $user, $method, $notes, $recipientGroup, $component, $result, $compatStatus, $eval, $overrideReason, $emergencyOverride) {
            $crossmatch = $this->store(
                $request, $unit, $user, $method,
                $emergencyOverride ? trim(($notes ? $notes.' ' : '').'[EMERGENCY OVERRIDE: '.$overrideReason.']') : $notes,
                $recipientGroup, $component, $result, $compatStatus
            );

            $unit->update([
                'status' => BloodUnit::STATUS_CROSSMATCHED,
                'crossmatch_status' => BloodUnit::CROSSMATCH_COMPATIBLE,
                'reserved_for_request_id' => $request->id,
            ]);

            $this->log->log(LogModule::BLOOD_BANK, $emergencyOverride ? 'CROSSMATCH_EMERGENCY_OVERRIDE' : 'CROSSMATCH_COMPATIBLE', [
                'description' => "Crossmatch {$compatStatus}: unit {$unit->unit_number} reserved for {$request->request_number}. {$eval['reason']}",
                'causer' => $user,
                'severity' => $emergencyOverride ? LogSeverity::CRITICAL : LogSeverity::INFO,
                'patient_id' => $request->patient_id,
            ], $crossmatch);

            $this->notifier->notifyPermission('blood_bank.units.issue', [
                'module' => NotificationModule::BLOOD_BANK,
                'priority' => $emergencyOverride ? NotificationPriority::URGENT : NotificationPriority::NORMAL,
                'title' => 'Crossmatch completed',
                'message' => "Unit {$unit->unit_number} {$compatStatus} for {$request->request_number}.",
                'url' => url('/admin/blood-bank/requests'),
                'source_type' => 'blood_crossmatch',
                'source_id' => $crossmatch->id,
                'patient_id' => $request->patient_id,
            ]);

            return $crossmatch->load('unit', 'request');
        });
    }

    public function verify(BloodCrossmatch $crossmatch, User $user): BloodCrossmatch
    {
        $crossmatch->update(['verified_by' => $user->id, 'verified_at' => now()]);

        $this->log->log(LogModule::BLOOD_BANK, 'CROSSMATCH_VERIFIED', [
            'description' => "Crossmatch #{$crossmatch->id} verified for request #{$crossmatch->blood_request_id}.",
            'causer' => $user,
            'patient_id' => $crossmatch->patient_id,
        ], $crossmatch);

        return $crossmatch->refresh();
    }

    protected function store(
        BloodRequest $request, BloodUnit $unit, User $user, ?string $method, ?string $notes,
        string $recipientGroup, string $component, string $result, string $compatStatus
    ): BloodCrossmatch {
        return BloodCrossmatch::updateOrCreate(
            ['blood_request_id' => $request->id, 'blood_unit_id' => $unit->id],
            [
                'visit_id' => $request->visit_id,
                'patient_id' => $request->patient_id,
                'performed_by' => $user->id,
                'performed_at' => now(),
                'result' => $result,
                'method' => $method,
                'recipient_blood_group' => $recipientGroup,
                'donor_blood_group' => $unit->blood_group,
                'component_type' => $component,
                'compatibility_status' => $compatStatus,
                'notes' => $notes,
            ]
        );
    }
}
