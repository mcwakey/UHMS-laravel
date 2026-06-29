<?php

namespace App\Http\Controllers\Admin\Journey;

use App\Enums\JourneyDelayCause;
use App\Enums\PatientJourneyStage;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Department\DepartmentDashboardCapabilityService;
use App\Services\Journey\JourneyAssignableUserService;
use App\Services\Journey\JourneyHandoffWorklistService;
use App\Services\Journey\JourneyWorklistService;
use Illuminate\Http\Request;

/**
 * Patient flow worklist — delayed actions, cross-department handoffs, SLA breaches
 * and assignment coordination. Capability-gated; reuses the journey services. Only
 * the active tab's rows are resolved per request, so each load stays bounded. The
 * same data builder powers the full page and the live-refresh partial.
 */
class JourneyWorklistController extends Controller
{
    private const TABS = ['my_actions', 'owed_by', 'owed_to', 'sla_breaches', 'assigned_to_me', 'oversight'];

    public function __construct(
        private JourneyWorklistService $worklist,
        private JourneyHandoffWorklistService $handoffWorklist,
        private JourneyAssignableUserService $assignableUsers,
        private DepartmentDashboardCapabilityService $capabilities,
        private \App\Services\Journey\JourneyPredictionService $predictions,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        abort_if($this->capabilities->capabilitiesFor($user) === [], 403);

        return view('admin.journey.worklist', $this->buildData($request, $user));
    }

    /** Live refresh — same auth/filters, returns just the summary + rows partial. */
    public function refresh(Request $request)
    {
        $user = $request->user();
        abort_if($this->capabilities->capabilitiesFor($user) === [], 403);

        return view('admin.journey.partials.worklist-refresh', $this->buildData($request, $user));
    }

    private function buildData(Request $request, User $user): array
    {
        $canOversight = $user->can('journey.oversight');
        $tab = in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : 'my_actions';
        // Oversight tab is permission-gated — silently fall back for everyone else.
        if ($tab === 'oversight' && ! $canOversight) {
            $tab = 'my_actions';
        }

        $filters = [
            'severity' => in_array($request->query('severity'), ['delayed', 'critical'], true) ? $request->query('severity') : null,
            'status' => in_array($request->query('status'), ['open', 'actionable', 'blocked'], true) ? $request->query('status') : null,
            'sla_status' => in_array($request->query('sla_status'), ['near_breach', 'breached', 'critical_breach'], true) ? $request->query('sla_status') : null,
            'assignment_status' => in_array($request->query('assignment_status'), ['unassigned', 'assigned', 'acknowledged'], true) ? $request->query('assignment_status') : null,
            'escalation_level' => in_array($request->query('escalation_level'), ['warning', 'supervisor', 'critical'], true) ? $request->query('escalation_level') : null,
            'risk_level' => in_array($request->query('risk_level'), ['low', 'medium', 'high', 'critical'], true) ? $request->query('risk_level') : null,
            'cause' => $request->query('cause') ? JourneyDelayCause::tryFrom((string) $request->query('cause')) : null,
            'stage' => $request->query('stage') ? PatientJourneyStage::tryFrom((string) $request->query('stage')) : null,
            'mine_only' => $request->boolean('mine_only') ?: null,
            'unassigned_only' => $request->boolean('unassigned_only') ?: null,
        ];
        $clean = array_filter($filters, fn ($value) => $value !== null);
        $department = $user->department;

        $actions = [];
        $handoffs = [];

        switch ($tab) {
            case 'owed_by':
                $handoffs = $department
                    ? $this->handoffWorklist->owedByDepartment($department, $user, $clean)
                    : $this->handoffWorklist->forUser($user, $clean);
                break;
            case 'owed_to':
                $handoffs = $department
                    ? $this->handoffWorklist->owedToDepartment($department, $user, $clean)
                    : $this->handoffWorklist->forUser($user, $clean);
                break;
            case 'sla_breaches':
                $handoffs = $this->handoffWorklist->forUser($user, $clean + ['breached_only' => true]);
                break;
            case 'assigned_to_me':
                $handoffs = $this->handoffWorklist->assignedToMe($user, $clean);
                break;
            case 'oversight':
                // Unassigned near-breach+ handoffs across domains (oversight only).
                $handoffs = $this->handoffWorklist->unassignedBreachedHandoffs(
                    null,
                    isset($clean['cause']) ? $clean['cause']->value : null,
                    50,
                );
                break;
            default:
                $actions = $this->worklist->forUser($user, $clean);
                break;
        }

        // Eligible assignees + the current user's authority per destination domain
        // in the displayed handoffs (≤ a few queries — distinct types only).
        $assignableByType = [];
        $canActByType = [];
        foreach (collect($handoffs)->pluck('toDepartmentType')->filter()->unique() as $type) {
            $assignableByType[$type] = $this->assignableUsers->forDestination(null, $type);
            $capability = $this->capabilityForType($type);
            $canActByType[$type] = $capability === null || $this->capabilities->can($user, $capability);
        }

        // Phase 9.9 — explainable breach-risk predictions for the displayed handoffs
        // (one cached baseline read for the whole batch). Filter + optional sort by risk.
        $predictions = $handoffs !== [] && $user->can('journey.predictions.view')
            ? $this->predictions->predictForHandoffs($handoffs)
            : collect();
        if ($predictions->isNotEmpty()) {
            if (! empty($filters['risk_level'])) {
                $handoffs = array_values(array_filter($handoffs, fn ($h) => ($predictions->get($h->visitId)?->riskLevel->value) === $filters['risk_level']));
            }
            if ($request->query('sort') === 'risk') {
                usort($handoffs, fn ($a, $b) => ($predictions->get($b->visitId)?->riskScore ?? 0) <=> ($predictions->get($a->visitId)?->riskScore ?? 0));
            }
        }

        return [
            'tab' => $tab,
            'actions' => $actions,
            'handoffs' => $handoffs,
            'actionSummary' => $this->worklist->summaryForUser($user),
            'handoffSummary' => $this->handoffWorklist->summaryForUser($user),
            'matrix' => $this->handoffWorklist->matrixForUser($user),
            'assignableByType' => $assignableByType,
            'canActByType' => $canActByType,
            'filters' => $filters,
            'causes' => JourneyDelayCause::cases(),
            'refreshConfig' => config('journey.worklist_refresh'),
            'currentUserId' => $user->id,
            'canOversight' => $canOversight,
            'predictions' => $predictions,
            'canPredict' => $user->can('journey.predictions.view'),
        ];
    }

    private function capabilityForType(?string $type): ?string
    {
        return match ($type) {
            'investigation', 'radiology', 'blood_bank' => 'investigation_access',
            'pharmacy' => 'pharmacy_access',
            'inpatient', 'maternity' => 'ward_access',
            'finance', 'administrative' => 'financial_access',
            'consultation', 'treatment', 'procedure', 'theatre', 'emergency', 'ambulance', 'nursing', 'records' => 'consultation_access',
            default => null,
        };
    }
}
