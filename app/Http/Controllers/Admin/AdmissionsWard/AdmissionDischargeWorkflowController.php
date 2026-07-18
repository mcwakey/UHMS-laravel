<?php

namespace App\Http\Controllers\Admin\AdmissionsWard;

use App\Enums\AdmissionDischargeClearanceStatus;
use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\AdmissionDischargeClearance;
use App\Models\AdmissionDischargeSummary;
use App\Services\Admissions\AdmissionDischargeWorkflowService;
use App\Services\WorkspaceRouteResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdmissionDischargeWorkflowController extends Controller
{
    public function __construct(private AdmissionDischargeWorkflowService $workflow) {}

    public function startPlanning(Request $request, Admission $admission)
    {
        $data = $request->validate($this->planningRules());
        $this->workflow->startPlanning($admission, $data, $request->user());

        return redirect()
            ->route(app(WorkspaceRouteResolver::class)->routeName('admin.admissions.show'), $admission)
            ->withFragment('tab-discharge')
            ->with('success', __('admissions.discharge_planning_started'));
    }

    public function updatePlanning(Request $request, Admission $admission)
    {
        $data = $request->validate($this->planningRules());
        $this->workflow->updatePlanning($admission, $data, $request->user());

        return redirect()
            ->route(app(WorkspaceRouteResolver::class)->routeName('admin.admissions.show'), $admission)
            ->withFragment('tab-discharge')
            ->with('success', __('admissions.expected_discharge_updated'));
    }

    public function updateClearance(Request $request, Admission $admission, AdmissionDischargeClearance $clearance)
    {
        abort_unless((int) $clearance->admission_id === (int) $admission->id, 404);

        $data = $request->validate([
            'status' => ['required', Rule::in([
                AdmissionDischargeClearanceStatus::CLEARED->value,
                AdmissionDischargeClearanceStatus::BLOCKED->value,
                AdmissionDischargeClearanceStatus::NOT_REQUIRED->value,
                AdmissionDischargeClearanceStatus::PENDING->value,
            ])],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->workflow->updateClearance($clearance, AdmissionDischargeClearanceStatus::from($data['status']), $data['note'] ?? null, $request->user());

        return redirect()
            ->route(app(WorkspaceRouteResolver::class)->routeName('admin.admissions.show'), $admission)
            ->withFragment('tab-discharge')
            ->with('success', __('admissions.clearance_updated'));
    }

    public function revokeClearance(Request $request, Admission $admission, AdmissionDischargeClearance $clearance)
    {
        abort_unless((int) $clearance->admission_id === (int) $admission->id, 404);

        $data = $request->validate([
            'note' => ['required', 'string', 'max:1000'],
        ]);

        $this->workflow->revokeClearance($clearance, $data['note'], $request->user());

        return redirect()
            ->route(app(WorkspaceRouteResolver::class)->routeName('admin.admissions.show'), $admission)
            ->withFragment('tab-discharge')
            ->with('success', __('admissions.clearance_revoked'));
    }

    public function saveSummary(Request $request, Admission $admission)
    {
        $data = $request->validate($this->summaryRules());
        $this->workflow->saveSummary($admission, $this->normaliseSummaryData($data), $request->user());

        return redirect()
            ->route(app(WorkspaceRouteResolver::class)->routeName('admin.admissions.show'), $admission)
            ->withFragment('tab-discharge')
            ->with('success', __('admissions.discharge_summary_saved'));
    }

    public function prepareSummary(Request $request, Admission $admission, AdmissionDischargeSummary $summary)
    {
        abort_unless((int) $summary->admission_id === (int) $admission->id, 404);
        $this->workflow->prepareSummary($summary, $request->user());

        return redirect()
            ->route(app(WorkspaceRouteResolver::class)->routeName('admin.admissions.show'), $admission)
            ->withFragment('tab-discharge')
            ->with('success', __('admissions.discharge_summary_prepared'));
    }

    public function approveSummary(Request $request, Admission $admission, AdmissionDischargeSummary $summary)
    {
        abort_unless((int) $summary->admission_id === (int) $admission->id, 404);
        $this->workflow->approveSummary($summary, $request->user());

        return redirect()
            ->route(app(WorkspaceRouteResolver::class)->routeName('admin.admissions.show'), $admission)
            ->withFragment('tab-discharge')
            ->with('success', __('admissions.discharge_summary_approved'));
    }

    public function printSummary(Admission $admission, AdmissionDischargeSummary $summary)
    {
        abort_unless((int) $summary->admission_id === (int) $admission->id, 404);

        $admission->loadMissing(['patient', 'bed.ward', 'admittedBy', 'dischargedBy']);
        $summary->loadMissing(['preparedBy', 'approvedBy']);

        return view('admissions.discharge-summary-print', compact('admission', 'summary'));
    }

    private function planningRules(): array
    {
        return [
            'expected_discharge_at' => ['nullable', 'date'],
            'discharge_planning_note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    private function summaryRules(): array
    {
        return [
            'primary_diagnosis' => ['nullable', 'string', 'max:255'],
            'secondary_diagnoses' => ['nullable', 'string', 'max:2000'],
            'admission_reason' => ['nullable', 'string', 'max:5000'],
            'hospital_course' => ['nullable', 'string', 'max:5000'],
            'investigations_summary' => ['nullable', 'string', 'max:5000'],
            'procedures_summary' => ['nullable', 'string', 'max:5000'],
            'treatment_given' => ['nullable', 'string', 'max:5000'],
            'discharge_condition' => ['nullable', 'string', 'max:255'],
            'discharge_medications' => ['nullable', 'string', 'max:5000'],
            'follow_up_instructions' => ['nullable', 'string', 'max:5000'],
            'follow_up_date' => ['nullable', 'date'],
            'warning_signs' => ['nullable', 'string', 'max:5000'],
            'final_outcome' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function normaliseSummaryData(array $data): array
    {
        if (! empty($data['secondary_diagnoses'])) {
            $data['secondary_diagnoses'] = collect(preg_split('/\r\n|\r|\n/', $data['secondary_diagnoses']))
                ->map(fn ($line) => trim($line))
                ->filter()
                ->values()
                ->all();
        } else {
            $data['secondary_diagnoses'] = null;
        }

        return $data;
    }
}
