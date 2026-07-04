<?php

namespace App\Services\Admissions;

use App\Enums\AdmissionDischargeClearanceStatus;
use App\Enums\AdmissionDischargeClearanceType;
use App\Enums\AdmissionDischargeSummaryStatus;
use App\Enums\LogModule;
use App\Models\Admission;
use App\Models\AdmissionDischargeClearance;
use App\Models\AdmissionDischargeSummary;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdmissionDischargeWorkflowService
{
    public function __construct(private ActivityLogService $logger) {}

    public function ensureClearances(Admission $admission): void
    {
        foreach (AdmissionDischargeClearanceType::cases() as $type) {
            AdmissionDischargeClearance::firstOrCreate([
                'admission_id' => $admission->id,
                'clearance_type' => $type->value,
            ], [
                'patient_id' => $admission->patient_id,
                'visit_id' => $admission->visit_id,
                'status' => AdmissionDischargeClearanceStatus::PENDING,
            ]);
        }
    }

    public function startPlanning(Admission $admission, array $data, User $user): Admission
    {
        $admission->update([
            'discharge_planning_started_at' => $admission->discharge_planning_started_at ?: now(),
            'discharge_planning_started_by' => $admission->discharge_planning_started_by ?: $user->id,
            'expected_discharge_at' => $data['expected_discharge_at'] ?? $admission->expected_discharge_at,
            'discharge_planning_note' => $data['discharge_planning_note'] ?? $admission->discharge_planning_note,
        ]);

        $this->logger->log(LogModule::ADMISSION, 'DISCHARGE_PLANNING_STARTED', $admission->toActivityContext() + [
            'metadata' => [
                'expected_discharge_at' => $admission->expected_discharge_at,
            ],
            'causer' => $user,
        ], $admission, 'Discharge planning started');

        return $admission->fresh(['dischargePlanningStartedBy']);
    }

    public function updatePlanning(Admission $admission, array $data, User $user): Admission
    {
        $admission->update([
            'discharge_planning_started_at' => $admission->discharge_planning_started_at ?: now(),
            'discharge_planning_started_by' => $admission->discharge_planning_started_by ?: $user->id,
            'expected_discharge_at' => $data['expected_discharge_at'] ?? null,
            'discharge_planning_note' => $data['discharge_planning_note'] ?? null,
        ]);

        $this->logger->log(LogModule::ADMISSION, 'EXPECTED_DISCHARGE_UPDATED', $admission->toActivityContext() + [
            'metadata' => [
                'expected_discharge_at' => $admission->expected_discharge_at,
                'has_note' => filled($admission->discharge_planning_note),
            ],
            'causer' => $user,
        ], $admission, 'Expected discharge updated');

        return $admission->fresh(['dischargePlanningStartedBy']);
    }

    public function updateClearance(AdmissionDischargeClearance $clearance, AdmissionDischargeClearanceStatus $status, ?string $note, User $user): AdmissionDischargeClearance
    {
        return DB::transaction(function () use ($clearance, $status, $note, $user) {
            $data = [
                'status' => $status,
                'note' => $note,
            ];

            if ($status->isReady()) {
                $data['cleared_by'] = $user->id;
                $data['cleared_at'] = now();
                $data['revoked_by'] = null;
                $data['revoked_at'] = null;
            }

            $clearance->update($data);
            $action = match ($status) {
                AdmissionDischargeClearanceStatus::CLEARED, AdmissionDischargeClearanceStatus::NOT_REQUIRED => 'DISCHARGE_CLEARANCE_CLEARED',
                AdmissionDischargeClearanceStatus::BLOCKED => 'DISCHARGE_CLEARANCE_BLOCKED',
                default => 'DISCHARGE_CLEARANCE_UPDATED',
            };

            $this->logClearance($clearance, $action, $user);

            return $clearance->fresh(['clearedBy', 'revokedBy']);
        });
    }

    public function revokeClearance(AdmissionDischargeClearance $clearance, string $note, User $user): AdmissionDischargeClearance
    {
        if (blank($note)) {
            throw ValidationException::withMessages(['note' => __('admissions.revocation_note_required')]);
        }

        $clearance->update([
            'status' => AdmissionDischargeClearanceStatus::REVOKED,
            'revoked_by' => $user->id,
            'revoked_at' => now(),
            'note' => $note,
        ]);

        $this->logClearance($clearance, 'DISCHARGE_CLEARANCE_REVOKED', $user);

        return $clearance->fresh(['clearedBy', 'revokedBy']);
    }

    public function saveSummary(Admission $admission, array $data, User $user): AdmissionDischargeSummary
    {
        $summary = $admission->dischargeSummaryRecord ?: new AdmissionDischargeSummary([
            'admission_id' => $admission->id,
            'patient_id' => $admission->patient_id,
            'visit_id' => $admission->visit_id,
            'prepared_by' => $user->id,
            'summary_status' => AdmissionDischargeSummaryStatus::DRAFT,
        ]);

        if ($summary->exists && $summary->summary_status?->isLocked()) {
            throw ValidationException::withMessages(['summary' => __('admissions.approved_summary_locked')]);
        }

        $summary->fill($data);
        $summary->prepared_by = $summary->prepared_by ?: $user->id;
        $summary->save();

        $this->logger->log(LogModule::ADMISSION, $summary->wasRecentlyCreated ? 'DISCHARGE_SUMMARY_CREATED' : 'DISCHARGE_SUMMARY_UPDATED', $summary->toActivityContext() + [
            'metadata' => [
                'summary_id' => $summary->id,
                'summary_status' => $summary->summary_status?->value,
            ],
            'causer' => $user,
        ], $summary, $summary->wasRecentlyCreated ? 'Discharge summary created' : 'Discharge summary updated');

        return $summary->fresh(['preparedBy', 'approvedBy']);
    }

    public function prepareSummary(AdmissionDischargeSummary $summary, User $user): AdmissionDischargeSummary
    {
        if ($summary->summary_status?->isLocked()) {
            return $summary;
        }

        $summary->update([
            'summary_status' => AdmissionDischargeSummaryStatus::PREPARED,
            'prepared_by' => $summary->prepared_by ?: $user->id,
        ]);

        $this->logSummary($summary, 'DISCHARGE_SUMMARY_PREPARED', $user);

        return $summary->fresh(['preparedBy', 'approvedBy']);
    }

    public function approveSummary(AdmissionDischargeSummary $summary, User $user): AdmissionDischargeSummary
    {
        $summary->update([
            'summary_status' => AdmissionDischargeSummaryStatus::APPROVED,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        $this->logSummary($summary, 'DISCHARGE_SUMMARY_APPROVED', $user);

        return $summary->fresh(['preparedBy', 'approvedBy']);
    }

    private function logClearance(AdmissionDischargeClearance $clearance, string $action, User $user): void
    {
        $this->logger->log(LogModule::ADMISSION, $action, $clearance->toActivityContext() + [
            'metadata' => [
                'clearance_id' => $clearance->id,
                'clearance_type' => $clearance->clearance_type?->value,
                'status' => $clearance->status?->value,
                'has_note' => filled($clearance->note),
            ],
            'causer' => $user,
        ], $clearance, str_replace('_', ' ', ucfirst(strtolower($action))));
    }

    private function logSummary(AdmissionDischargeSummary $summary, string $action, User $user): void
    {
        $this->logger->log(LogModule::ADMISSION, $action, $summary->toActivityContext() + [
            'metadata' => [
                'summary_id' => $summary->id,
                'summary_status' => $summary->summary_status?->value,
            ],
            'causer' => $user,
        ], $summary, str_replace('_', ' ', ucfirst(strtolower($action))));
    }
}
