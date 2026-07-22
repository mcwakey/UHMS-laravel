<?php

namespace App\Services\Admissions;

use App\Enums\AdmissionDischargeClearanceStatus;
use App\Enums\AdmissionDischargeClearanceType;
use App\Enums\AdmissionDischargeReadinessStatus;
use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Enums\PostnatalCaseStatus;
use App\Models\Admission;
use App\Services\ActivityLogService;
use Illuminate\Support\Collection;

class AdmissionDischargeReadinessService
{
    public function __construct(
        private AdmissionDischargeWorkflowService $workflow,
        private AdmissionCareOverviewService $careOverview,
        private ActivityLogService $logger,
    ) {}

    public function forAdmission(Admission $admission, array $medicationBoard = [], bool $ensureClearances = true, ?array $care = null): array
    {
        if ($ensureClearances) {
            $this->workflow->ensureClearances($admission);
        }

        $admission->loadMissing([
            'patient',
            'bed.ward',
            'dischargePlanningStartedBy',
            'dischargeClearances.clearedBy',
            'dischargeClearances.revokedBy',
            'dischargeSummaryRecord.preparedBy',
            'dischargeSummaryRecord.approvedBy',
            'postnatalCases.deliveryRecord.newbornRecords',
            'nursingTasks',
            'visit.latestInvoice.items',
            'visit.latestInvoice.payments',
            'visit.vitals',
            'wardRounds',
        ]);

        $care ??= $this->careOverview->forAdmission($admission, $medicationBoard);
        $clearances = $admission->dischargeClearances->keyBy(fn ($clearance) => $clearance->clearance_type->value);
        $summary = $admission->dischargeSummaryRecord;
        $invoice = $admission->visit?->latestInvoice;
        $invoiceBalance = $invoice ? (float) $invoice->balance : null;
        $postnatal = $this->postnatalArea($admission);

        $areas = collect([
            'clinical' => $this->area(AdmissionDischargeClearanceType::CLINICAL, $clearances, $care['ward_round_overdue'] ? AdmissionDischargeReadinessStatus::WARNING : AdmissionDischargeReadinessStatus::READY, $care['ward_round_overdue'] ? __('admissions.ward_round_review_pending') : __('admissions.clinical_ready')),
            'nursing' => $this->area(AdmissionDischargeClearanceType::NURSING, $clearances, $care['overdue_tasks']->isNotEmpty() ? AdmissionDischargeReadinessStatus::WARNING : AdmissionDischargeReadinessStatus::READY, $care['overdue_tasks']->isNotEmpty() ? __('admissions.nursing_tasks_pending') : __('admissions.nursing_ready')),
            'medication' => $this->area(AdmissionDischargeClearanceType::MEDICATION, $clearances, ((int) ($medicationBoard['counts']['overdue'] ?? 0)) > 0 ? AdmissionDischargeReadinessStatus::WARNING : AdmissionDischargeReadinessStatus::READY, ((int) ($medicationBoard['counts']['overdue'] ?? 0)) > 0 ? __('admissions.medication_review_pending') : __('admissions.medication_ready')),
            'billing' => $this->area(AdmissionDischargeClearanceType::BILLING, $clearances, $invoice ? ($invoiceBalance > 0 ? AdmissionDischargeReadinessStatus::WARNING : AdmissionDischargeReadinessStatus::READY) : AdmissionDischargeReadinessStatus::UNAVAILABLE, $invoice ? ($invoiceBalance > 0 ? __('admissions.billing_not_cleared') : __('admissions.billing_ready')) : __('admissions.billing_unavailable'), ['balance' => $invoiceBalance]),
            'bed_release' => $this->area(AdmissionDischargeClearanceType::BED_RELEASE, $clearances, $admission->bed_id ? AdmissionDischargeReadinessStatus::READY : AdmissionDischargeReadinessStatus::UNAVAILABLE, $admission->bed_id ? __('admissions.bed_release_ready') : __('admissions.no_current_bed')),
            'documentation' => $this->area(AdmissionDischargeClearanceType::DOCUMENTATION, $clearances, $summary?->summary_status?->isApproved() ? AdmissionDischargeReadinessStatus::READY : AdmissionDischargeReadinessStatus::WARNING, $summary ? __('admissions.discharge_summary_status_label', ['status' => $summary->summary_status?->label()]) : __('admissions.missing_summary')),
            'follow_up' => $this->area(AdmissionDischargeClearanceType::FOLLOW_UP, $clearances, ($summary?->follow_up_date || $summary?->follow_up_instructions) ? AdmissionDischargeReadinessStatus::READY : AdmissionDischargeReadinessStatus::WARNING, ($summary?->follow_up_date || $summary?->follow_up_instructions) ? __('admissions.follow_up_ready') : __('admissions.follow_up_missing')),
            'postnatal' => $postnatal,
        ]);

        $blockers = $this->enforcementBlockers($areas, $summary);

        return [
            'planning_started' => (bool) $admission->discharge_planning_started_at,
            'expected_discharge_at' => $admission->expected_discharge_at,
            'planning_note' => $admission->discharge_planning_note,
            'summary' => $summary,
            'clearances' => $clearances,
            'areas' => $areas,
            'care' => $care,
            'invoice' => $invoice,
            'invoice_balance' => $invoiceBalance,
            'enforcement' => [
                'clearance_required' => (bool) config('admissions.discharge.require_clearance_before_discharge', false),
                'summary_required' => (bool) config('admissions.discharge.require_summary_before_discharge', false),
                'billing_required' => (bool) config('admissions.discharge.require_billing_clearance_before_discharge', false),
                'postnatal_required' => (bool) config('admissions.discharge.require_postnatal_ready_before_discharge', false),
                'enabled' => (bool) (
                    config('admissions.discharge.require_clearance_before_discharge', false)
                    || config('admissions.discharge.require_summary_before_discharge', false)
                    || config('admissions.discharge.require_billing_clearance_before_discharge', false)
                    || config('admissions.discharge.require_postnatal_ready_before_discharge', false)
                ),
                'blockers' => $blockers,
                'can_discharge' => $blockers->isEmpty(),
            ],
            'overall_status' => $blockers->isNotEmpty()
                ? AdmissionDischargeReadinessStatus::BLOCKED
                : ($areas->contains(fn ($area) => $area['status'] === AdmissionDischargeReadinessStatus::WARNING) ? AdmissionDischargeReadinessStatus::WARNING : AdmissionDischargeReadinessStatus::READY),
        ];
    }

    public function assertCanDischarge(Admission $admission, array $medicationBoard = []): void
    {
        $readiness = $this->forAdmission($admission, $medicationBoard);

        if ($readiness['enforcement']['blockers']->isNotEmpty()) {
            $this->logger->log(LogModule::ADMISSION, 'FINAL_DISCHARGE_BLOCKED_BY_READINESS_ENFORCEMENT', $admission->toActivityContext() + [
                'severity' => LogSeverity::WARNING,
                'metadata' => [
                    'blockers_count' => $readiness['enforcement']['blockers']->count(),
                    'clearance_required' => $readiness['enforcement']['clearance_required'],
                    'summary_required' => $readiness['enforcement']['summary_required'],
                    'billing_required' => $readiness['enforcement']['billing_required'],
                    'postnatal_required' => $readiness['enforcement']['postnatal_required'],
                ],
            ], $admission, 'Final discharge blocked by readiness enforcement');

            throw \Illuminate\Validation\ValidationException::withMessages([
                'discharge_readiness' => $readiness['enforcement']['blockers']->implode(' '),
            ]);
        }
    }

    private function area(
        AdmissionDischargeClearanceType $type,
        Collection $clearances,
        AdmissionDischargeReadinessStatus $fallbackStatus,
        string $message,
        array $meta = []
    ): array {
        $clearance = $clearances->get($type->value);
        $status = $fallbackStatus;

        if ($clearance) {
            $status = match ($clearance->status) {
                AdmissionDischargeClearanceStatus::CLEARED, AdmissionDischargeClearanceStatus::NOT_REQUIRED => AdmissionDischargeReadinessStatus::READY,
                AdmissionDischargeClearanceStatus::BLOCKED => AdmissionDischargeReadinessStatus::BLOCKED,
                AdmissionDischargeClearanceStatus::REVOKED, AdmissionDischargeClearanceStatus::PENDING => $fallbackStatus,
            };
        }

        return [
            'type' => $type,
            'label' => $type->label(),
            'status' => $status,
            'message' => $message,
            'clearance' => $clearance,
            'meta' => $meta,
        ];
    }

    private function enforcementBlockers(Collection $areas, $summary): Collection
    {
        $blockers = collect();

        if (config('admissions.discharge.require_clearance_before_discharge', false)) {
            $notCleared = $areas->filter(fn ($area) => ! $area['clearance']?->status?->isReady());
            if ($notCleared->isNotEmpty()) {
                $blockers->push(__('admissions.clearance_required_blocker'));
            }
        }

        if (config('admissions.discharge.require_summary_before_discharge', false) && ! $summary?->summary_status?->isApproved()) {
            $blockers->push(__('admissions.summary_required_blocker'));
        }

        if (config('admissions.discharge.require_billing_clearance_before_discharge', false)) {
            $billing = $areas->get('billing');
            if (! $billing || $billing['status'] !== AdmissionDischargeReadinessStatus::READY) {
                $blockers->push(__('admissions.billing_required_blocker'));
            }
        }

        if (config('admissions.discharge.require_postnatal_ready_before_discharge', false)) {
            $postnatal = $areas->get('postnatal');
            if ($postnatal && $postnatal['status'] === AdmissionDischargeReadinessStatus::WARNING) {
                $blockers->push(__('admissions.postnatal_required_blocker'));
            }
        }

        return $blockers->values();
    }

    private function postnatalArea(Admission $admission): array
    {
        $cases = $admission->postnatalCases;

        if ($cases->isEmpty()) {
            return [
                'type' => null,
                'label' => __('admissions.postnatal_readiness'),
                'status' => AdmissionDischargeReadinessStatus::UNAVAILABLE,
                'message' => __('admissions.postnatal_not_linked'),
                'clearance' => null,
                'meta' => ['case_count' => 0],
            ];
        }

        $activeCases = $cases->filter(fn ($case) => ! $case->status?->isClosed());
        $notReady = $activeCases->filter(fn ($case) => ! $case->readyForDischarge() || $case->referral_required);

        return [
            'type' => null,
            'label' => __('admissions.postnatal_readiness'),
            'status' => $notReady->isEmpty() ? AdmissionDischargeReadinessStatus::READY : AdmissionDischargeReadinessStatus::WARNING,
            'message' => $notReady->isEmpty() ? __('admissions.postnatal_ready') : __('admissions.postnatal_warning'),
            'clearance' => null,
            'meta' => [
                'case_count' => $cases->count(),
                'active_count' => $activeCases->count(),
                'not_ready_count' => $notReady->count(),
                'referral_required' => $cases->contains(fn ($case) => (bool) $case->referral_required),
                'closed_count' => $cases->filter(fn ($case) => in_array($case->status, [PostnatalCaseStatus::CLOSED, PostnatalCaseStatus::CANCELLED], true))->count(),
            ],
        ];
    }
}
