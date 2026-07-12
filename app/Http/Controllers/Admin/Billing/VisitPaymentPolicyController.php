<?php

namespace App\Http\Controllers\Admin\Billing;

use App\Enums\PatientFinancialRiskLevel;
use App\Enums\VisitPaymentPolicySource;
use App\Enums\VisitPaymentTimingPolicy;
use App\Http\Controllers\Controller;
use App\Models\Visit;
use App\Models\VisitPaymentPolicy;
use App\Services\Billing\VisitPaymentPolicyMaterializationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Observational visit-payment-policy worklist, detail, history and controlled
 * refresh (Payment Timing Policy Phase 6). No approval/override action exists.
 * `refresh` only re-observes — it never approves or activates a policy.
 */
class VisitPaymentPolicyController extends Controller
{
    public function __construct(private readonly VisitPaymentPolicyMaterializationService $service) {}

    public function index(Request $request)
    {
        $query = VisitPaymentPolicy::query()
            ->with(['visit:id,visit_number,visit_type,patient_id,status', 'visit.patient:id,patient_number,first_name,last_name,other_names']);

        if ($p = $request->get('resolved_policy')) {
            $query->forResolvedPolicy($p);
        }
        if ($p = $request->get('recommended_policy')) {
            $query->forRecommendation($p);
        }
        if ($s = $request->get('resolution_source')) {
            $query->where('resolution_source', $s);
        }
        if ($l = $request->get('risk_level')) {
            $query->where('patient_risk_level_snapshot', $l);
        }
        if ($t = $request->get('visit_type')) {
            $query->where('visit_type_snapshot', $t);
        }
        if ($request->boolean('finance_review')) {
            $query->requiringFinanceReview();
        }
        if ($request->boolean('active_only')) {
            $query->whereHas('visit', fn ($v) => $v->active());
        }
        if ($from = $request->get('from')) {
            $query->whereDate('materialized_at', '>=', $from);
        }
        if ($to = $request->get('to')) {
            $query->whereDate('materialized_at', '<=', $to);
        }

        $policies = $query->orderByDesc('materialized_at')->orderByDesc('id')->paginate(25)->withQueryString();

        // Bounded staleness computation for the visible page only.
        $stale = [];
        foreach ($policies as $policy) {
            $stale[$policy->id] = $this->service->snapshotIsStale($policy);
        }

        return view('admin.billing.visit-payment-policies.index', [
            'policies' => $policies,
            'stale' => $stale,
            'policies_enum' => VisitPaymentTimingPolicy::selectable(),
            'sources' => VisitPaymentPolicySource::cases(),
            'levels' => PatientFinancialRiskLevel::cases(),
            'filters' => $request->only(['resolved_policy', 'recommended_policy', 'resolution_source', 'risk_level', 'visit_type', 'finance_review', 'active_only', 'from', 'to']),
        ]);
    }

    public function show(Request $request, Visit $visit)
    {
        $policy = $visit->paymentPolicy()->with('patientFinancialRiskProfile')->first();
        abort_if($policy === null, 404);

        $history = collect();
        if ($request->user()?->can('visits.payment_policy.history')) {
            $history = $policy->history()->with('performer')->take(50)->get();
        }

        // Phase 7 — administrative arrangement context (permission-gated in the view).
        $approvedArrangement = null;
        $pendingArrangement = null;
        if ($request->user()?->can('visits.payment_arrangement.view')) {
            $approvedArrangement = $visit->currentApprovedPaymentArrangement()->with('approver')->first();
            $pendingArrangement = $visit->pendingPaymentArrangement()->with('requester')->first();
        }

        return view('admin.billing.visit-payment-policies.show', [
            'visit' => $visit,
            'policy' => $policy,
            'history' => $history,
            'isStale' => $this->service->snapshotIsStale($policy),
            'approvedArrangement' => $approvedArrangement,
            'pendingArrangement' => $pendingArrangement,
        ]);
    }

    public function refresh(Request $request, Visit $visit): RedirectResponse
    {
        $this->service->refresh($visit, $request->user(), reasonCode: 'manual_refresh');

        return back()->with('success', __('visit_payment_policy.flash.refreshed'));
    }

    public function report()
    {
        $metrics = [
            'total' => VisitPaymentPolicy::query()->count(),
            'finance_review' => VisitPaymentPolicy::query()->requiringFinanceReview()->count(),
            'with_recommendation' => VisitPaymentPolicy::query()->whereNotNull('recommended_policy')->count(),
            'high_risk_snapshot' => VisitPaymentPolicy::query()->where('patient_risk_level_snapshot', PatientFinancialRiskLevel::HIGH_RISK->value)->count(),
            'blocked_credit_snapshot' => VisitPaymentPolicy::query()->where('patient_risk_level_snapshot', PatientFinancialRiskLevel::BLOCKED_CREDIT->value)->count(),
            'materialized_this_month' => VisitPaymentPolicy::query()->whereBetween('materialized_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
        ];

        return view('admin.billing.visit-payment-policies.report', ['metrics' => $metrics]);
    }
}
