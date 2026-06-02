<?php

namespace App\Services;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Enums\NotificationModule;
use App\Enums\NotificationPriority;
use App\Models\BloodCrossmatch;
use App\Models\BloodIssue;
use App\Models\BloodRequest;
use App\Models\BloodUnit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BloodIssueService
{
    public function __construct(
        private BloodBankCompatibilityService $compatibility,
        private ActivityLogService $log,
        private NotificationService $notifier,
    ) {}

    public function issue(BloodRequest $request, BloodUnit $unit, User $user, array $data = []): BloodIssue
    {
        $emergency = (bool) ($data['emergency'] ?? false);

        // 1. Request must be approved and have recipient details.
        if (! in_array($request->status, [BloodRequest::STATUS_APPROVED, BloodRequest::STATUS_PARTIALLY_ISSUED], true)) {
            throw ValidationException::withMessages(['blood_request_id' => 'Only approved blood requests can be issued.']);
        }

        $request->loadMissing('recipient');
        if (! $request->recipient) {
            throw ValidationException::withMessages(['blood_request_id' => 'Recipient details are required before issue.']);
        }

        // 2. Unit must be safe (not expired/discarded/rejected/already issued).
        if ($unit->isBlockedForIssue()) {
            throw ValidationException::withMessages(['blood_unit_id' => 'This blood unit is expired, rejected, discarded, or already issued and cannot be issued.']);
        }

        // 3. Screening must have passed.
        if ($unit->screening_status !== BloodUnit::SCREENING_PASSED) {
            throw ValidationException::withMessages(['blood_unit_id' => 'Unit screening has not passed; it cannot be issued.']);
        }

        // 4. Status must be issuable.
        if (! in_array($unit->status, [BloodUnit::STATUS_AVAILABLE, BloodUnit::STATUS_RESERVED, BloodUnit::STATUS_CROSSMATCHED], true)) {
            throw ValidationException::withMessages(['blood_unit_id' => 'This blood unit is not in an issuable state.']);
        }

        // 5. Compatibility.
        $recipientGroup = $request->recipientGroup();
        $component = $request->recipient->requested_component_type ?: $request->component_type;
        $eval = $this->compatibility->evaluate($recipientGroup, $unit->blood_group, $component);
        $compatibilityStatus = $eval['status'];

        if ($eval['status'] === BloodBankCompatibilityService::INCOMPATIBLE && ! $emergency) {
            throw ValidationException::withMessages([
                'blood_unit_id' => 'Incompatible unit cannot be issued without an emergency override. '.$eval['reason'],
            ]);
        }

        // 6. Crossmatch requirement (per component, unless emergency release).
        if (! $emergency && $this->crossmatchRequired($component)) {
            $compatibleXm = BloodCrossmatch::where('blood_request_id', $request->id)
                ->where('blood_unit_id', $unit->id)
                ->where('result', BloodCrossmatch::RESULT_COMPATIBLE)
                ->exists();

            if (! $compatibleXm) {
                throw ValidationException::withMessages(['blood_unit_id' => 'A compatible crossmatch is required before issue.']);
            }
        }

        // 7. Emergency release requires a type + reason.
        if ($emergency) {
            $type = $data['emergency_release_type'] ?? null;
            if (! $type || ! in_array($type, (array) config('blood_bank.emergency_release_types'), true)) {
                throw ValidationException::withMessages(['emergency_release_type' => 'A valid emergency release type is required.']);
            }
            if (empty($data['emergency_release_reason'])) {
                throw ValidationException::withMessages(['emergency_release_reason' => 'A reason is required for emergency blood release.']);
            }
            if ($eval['status'] === BloodBankCompatibilityService::INCOMPATIBLE) {
                $compatibilityStatus = BloodCrossmatch::COMPAT_EMERGENCY_OVERRIDE;
            }
        }

        return DB::transaction(function () use ($request, $unit, $user, $data, $emergency, $compatibilityStatus) {
            $issue = BloodIssue::create([
                'issue_number' => BloodIssue::generateIssueNumber(),
                'blood_request_id' => $request->id,
                'blood_unit_id' => $unit->id,
                'visit_id' => $request->visit_id,
                'patient_id' => $request->patient_id,
                'admission_id' => $request->admission_id,
                'emergency_case_id' => $request->emergency_case_id,
                'issued_by' => $user->id,
                'received_by' => $data['received_by'] ?? null,
                'received_by_name' => $data['received_by_name'] ?? null,
                'issued_at' => $data['issued_at'] ?? now(),
                'transfusion_status' => BloodIssue::STATUS_ISSUED,
                'compatibility_status' => $compatibilityStatus,
                'is_emergency_release' => $emergency,
                'emergency_release_type' => $emergency ? ($data['emergency_release_type'] ?? null) : null,
                'emergency_release_reason' => $emergency ? ($data['emergency_release_reason'] ?? null) : null,
                'authorized_by' => $data['authorized_by'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $unit->update([
                'status' => BloodUnit::STATUS_ISSUED,
                'issued_at' => $issue->issued_at,
                'reserved_for_request_id' => $request->id,
            ]);

            $issued = $request->issues()->count();
            $request->update([
                'units_issued' => $issued,
                'status' => $issued >= $request->units_requested
                    ? BloodRequest::STATUS_ISSUED
                    : BloodRequest::STATUS_PARTIALLY_ISSUED,
            ]);

            $this->log->log(LogModule::BLOOD_BANK, $emergency ? 'BLOOD_EMERGENCY_RELEASE' : 'BLOOD_ISSUED', [
                'description' => ($emergency ? 'EMERGENCY RELEASE: ' : '')."Unit {$unit->unit_number} ({$unit->blood_group}) issued for {$request->request_number}.",
                'causer' => $user,
                'severity' => $emergency ? LogSeverity::CRITICAL : LogSeverity::NOTICE,
                'patient_id' => $request->patient_id,
                'visit_id' => $request->visit_id,
            ], $issue);

            if ($emergency) {
                $this->notifier->notifyPermission('blood_bank.units.issue', [
                    'module' => NotificationModule::BLOOD_BANK,
                    'priority' => NotificationPriority::CRITICAL,
                    'title' => 'Emergency blood release',
                    'message' => "Unit {$unit->unit_number} emergency-released ({$issue->emergency_release_type}) for {$request->request_number}.",
                    'url' => url('/admin/blood-bank/requests'),
                    'source_type' => 'blood_issue',
                    'source_id' => $issue->id,
                    'patient_id' => $request->patient_id,
                ]);
            }

            return $issue->load('unit', 'request');
        });
    }

    public function recordTransfusion(BloodIssue $issue, User $user, array $data = []): BloodIssue
    {
        return DB::transaction(function () use ($issue, $user, $data) {
            $reactionOccurred = (bool) ($data['reaction_occurred'] ?? (! empty($data['reaction_notes']) || ! empty($data['reaction_type'])));
            $outcome = $data['outcome'] ?? ($reactionOccurred ? BloodIssue::OUTCOME_STOPPED_REACTION : BloodIssue::OUTCOME_COMPLETED);

            $status = $reactionOccurred
                ? BloodIssue::STATUS_REACTION_RECORDED
                : BloodIssue::STATUS_TRANSFUSED;

            $issue->update([
                'transfusion_status' => $status,
                'transfused_by' => $user->id,
                'transfused_at' => $data['transfused_at'] ?? now(),
                'transfusion_started_at' => $data['transfusion_started_at'] ?? $issue->transfusion_started_at,
                'transfusion_completed_at' => $data['transfusion_completed_at'] ?? ($outcome === BloodIssue::OUTCOME_COMPLETED ? now() : $issue->transfusion_completed_at),
                'witnessed_by' => $data['witnessed_by'] ?? $issue->witnessed_by,
                'pre_transfusion_vitals' => $data['pre_transfusion_vitals'] ?? $issue->pre_transfusion_vitals,
                'post_transfusion_vitals' => $data['post_transfusion_vitals'] ?? $issue->post_transfusion_vitals,
                'reaction_occurred' => $reactionOccurred,
                'reaction_type' => $data['reaction_type'] ?? $issue->reaction_type,
                'reaction_notes' => $data['reaction_notes'] ?? $issue->reaction_notes,
                'outcome' => $outcome,
                'notes' => $data['notes'] ?? $issue->notes,
            ]);

            $issue->unit?->update(['status' => BloodUnit::STATUS_TRANSFUSED]);

            if ($issue->request && $issue->request->units_issued >= $issue->request->units_requested) {
                $issue->request->update(['status' => BloodRequest::STATUS_COMPLETED]);
            }

            $this->log->log(LogModule::BLOOD_BANK, $reactionOccurred ? 'TRANSFUSION_REACTION_RECORDED' : 'TRANSFUSION_RECORDED', [
                'description' => "Transfusion {$outcome} for issue {$issue->issue_number}.".($reactionOccurred ? ' Reaction: '.($data['reaction_type'] ?? 'reported').'.' : ''),
                'causer' => $user,
                'severity' => $reactionOccurred ? LogSeverity::WARNING : LogSeverity::INFO,
                'patient_id' => $issue->patient_id,
                'visit_id' => $issue->visit_id,
            ], $issue);

            if ($reactionOccurred) {
                $this->notifier->notifyPermission('blood_bank.transfusions.record', [
                    'module' => NotificationModule::BLOOD_BANK,
                    'priority' => NotificationPriority::URGENT,
                    'title' => 'Transfusion reaction reported',
                    'message' => "Reaction ({$issue->reaction_type}) during transfusion of unit {$issue->unit?->unit_number}.",
                    'url' => url('/admin/blood-bank/requests'),
                    'source_type' => 'blood_issue',
                    'source_id' => $issue->id,
                    'patient_id' => $issue->patient_id,
                ]);
            }

            return $issue->refresh()->load('unit', 'request');
        });
    }

    public function recordReaction(BloodIssue $issue, User $user, array $data): BloodIssue
    {
        return $this->recordTransfusion($issue, $user, array_merge($data, ['reaction_occurred' => true]));
    }

    protected function crossmatchRequired(string $component): bool
    {
        $component = strtoupper($component);

        return (bool) (config("blood_bank.require_crossmatch_before_issue.{$component}")
            ?? config('blood_bank.default_require_crossmatch', true));
    }
}
