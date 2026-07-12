<?php

namespace App\Http\Controllers\Admin\Billing;

use App\Data\Billing\VisitPaymentArrangementApprovalData;
use App\Data\Billing\VisitPaymentArrangementData;
use App\Enums\PatientFinancialRiskLevel;
use App\Enums\VisitPaymentArrangementSource;
use App\Enums\VisitPaymentArrangementStatus;
use App\Enums\VisitPaymentTimingPolicy;
use App\Exceptions\VisitPaymentArrangementException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\ApproveVisitPaymentArrangementRequest;
use App\Http\Requests\Billing\RejectVisitPaymentArrangementRequest;
use App\Http\Requests\Billing\RestoreVisitPaymentBaselineRequest;
use App\Http\Requests\Billing\RevokeVisitPaymentArrangementRequest;
use App\Http\Requests\Billing\StoreVisitPaymentArrangementRequest;
use App\Http\Requests\Billing\UpdateVisitPaymentArrangementRequest;
use App\Http\Requests\Billing\WithdrawVisitPaymentArrangementRequest;
use App\Models\Visit;
use App\Models\VisitPaymentArrangement;
use App\Services\Billing\VisitPaymentArrangementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Per-visit payment-arrangement request/approval workflow UI + actions (Payment
 * Timing Policy Phase 7). Thin controller — all domain transitions run through
 * the service. Approved arrangements are administrative only.
 */
class VisitPaymentArrangementController extends Controller
{
    public function __construct(private readonly VisitPaymentArrangementService $service) {}

    public function index(Request $request)
    {
        $query = VisitPaymentArrangement::query()
            ->with(['visit:id,visit_number,visit_type,patient_id', 'visit.patient:id,patient_number,first_name,last_name,other_names', 'requester:id,name', 'approver:id,name']);

        foreach (['status' => 'status', 'requested_policy' => 'requested_policy', 'approved_policy' => 'approved_policy', 'source' => 'source', 'risk_level' => 'risk_level_snapshot'] as $param => $column) {
            if ($value = $request->get($param)) {
                $query->where($column, $value);
            }
        }
        if ($request->filled('visit_type')) {
            $query->whereHas('visit', fn ($v) => $v->where('visit_type', $request->get('visit_type')));
        }
        if ($request->boolean('finance_review')) {
            $query->where('finance_review_snapshot', true);
        }
        if ($request->boolean('expiring_soon')) {
            $query->where('status', VisitPaymentArrangementStatus::APPROVED->value)
                ->whereNotNull('expires_at')
                ->whereDate('expires_at', '>=', now())->whereDate('expires_at', '<=', now()->addDays(14));
        }

        $arrangements = $query->orderByDesc('id')->paginate(25)->withQueryString();

        return view('admin.billing.visit-payment-arrangements.index', [
            'arrangements' => $arrangements,
            'statuses' => VisitPaymentArrangementStatus::cases(),
            'policies' => VisitPaymentTimingPolicy::operationalPolicies(),
            'sources' => VisitPaymentArrangementSource::cases(),
            'levels' => PatientFinancialRiskLevel::cases(),
            'filters' => $request->only(['status', 'requested_policy', 'approved_policy', 'source', 'risk_level', 'visit_type', 'finance_review', 'expiring_soon']),
        ]);
    }

    public function show(Request $request, VisitPaymentArrangement $arrangement)
    {
        $arrangement->load(['visit.patient', 'requester', 'approver', 'reviewer', 'revoker', 'withdrawer', 'replacement']);

        $history = collect();
        if ($request->user()?->can('visits.payment_arrangement.history')) {
            $history = $arrangement->history()->with('performer')->take(50)->get();
        }

        return view('admin.billing.visit-payment-arrangements.show', [
            'arrangement' => $arrangement,
            'history' => $history,
            'riskStale' => $this->service->riskIsStale($arrangement),
        ]);
    }

    public function store(StoreVisitPaymentArrangementRequest $request, Visit $visit): RedirectResponse
    {
        return $this->guard(fn () => $this->service->request($visit, VisitPaymentArrangementData::fromValidated($request->validated()), $request->user()), 'requested');
    }

    public function update(UpdateVisitPaymentArrangementRequest $request, VisitPaymentArrangement $arrangement): RedirectResponse
    {
        return $this->guard(fn () => $this->service->update($arrangement, VisitPaymentArrangementData::fromValidated($request->validated()), $request->user()), 'updated');
    }

    public function approve(ApproveVisitPaymentArrangementRequest $request, VisitPaymentArrangement $arrangement): RedirectResponse
    {
        $selfAllowed = $request->user()?->can(config('visit_payment_arrangement.allow_self_approval_permission')) ?? false;

        return $this->guard(fn () => $this->service->approve($arrangement, VisitPaymentArrangementApprovalData::fromValidated($request->validated()), $request->user(), $selfAllowed), 'approved');
    }

    public function reject(RejectVisitPaymentArrangementRequest $request, VisitPaymentArrangement $arrangement): RedirectResponse
    {
        return $this->guard(fn () => $this->service->reject($arrangement, $request->validated()['reason'], $request->user()), 'rejected');
    }

    public function withdraw(WithdrawVisitPaymentArrangementRequest $request, VisitPaymentArrangement $arrangement): RedirectResponse
    {
        return $this->guard(fn () => $this->service->withdraw($arrangement, $request->validated()['reason'], $request->user()), 'withdrawn');
    }

    public function revoke(RevokeVisitPaymentArrangementRequest $request, VisitPaymentArrangement $arrangement): RedirectResponse
    {
        return $this->guard(fn () => $this->service->revoke($arrangement, $request->validated()['reason'], $request->user()), 'revoked');
    }

    public function restoreBaseline(RestoreVisitPaymentBaselineRequest $request, Visit $visit): RedirectResponse
    {
        return $this->guard(fn () => $this->service->restoreBaseline($visit, $request->validated()['reason'], $request->user()), 'baseline_restored');
    }

    public function report()
    {
        $count = fn (callable $c) => $c(VisitPaymentArrangement::query());
        $overdueHours = (int) config('visit_payment_arrangement.review_overdue_hours', 24);

        $metrics = [
            'pending' => VisitPaymentArrangement::query()->pending()->count(),
            'approved' => VisitPaymentArrangement::query()->approved()->count(),
            'rejected' => VisitPaymentArrangement::query()->where('status', VisitPaymentArrangementStatus::REJECTED->value)->count(),
            'revoked' => VisitPaymentArrangement::query()->where('status', VisitPaymentArrangementStatus::REVOKED->value)->count(),
            'expired' => VisitPaymentArrangement::query()->where('status', VisitPaymentArrangementStatus::EXPIRED->value)->count(),
            'pay_before' => VisitPaymentArrangement::query()->approved()->forApprovedPolicy(VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE)->count(),
            'pay_after' => VisitPaymentArrangement::query()->approved()->forApprovedPolicy(VisitPaymentTimingPolicy::PAY_AFTER_ALL_SERVICES)->count(),
            'running_bill' => VisitPaymentArrangement::query()->approved()->forApprovedPolicy(VisitPaymentTimingPolicy::RUNNING_BILL)->count(),
            'high_risk_deferred' => VisitPaymentArrangement::query()->approved()
                ->whereIn('approved_policy', [VisitPaymentTimingPolicy::PAY_AFTER_ALL_SERVICES->value, VisitPaymentTimingPolicy::RUNNING_BILL->value])
                ->whereIn('risk_level_snapshot', [PatientFinancialRiskLevel::HIGH_RISK->value, PatientFinancialRiskLevel::BLOCKED_CREDIT->value])->count(),
            'overdue_review' => VisitPaymentArrangement::query()->pending()->where('requested_at', '<=', now()->subHours($overdueHours))->count(),
        ];

        return view('admin.billing.visit-payment-arrangements.report', ['metrics' => $metrics]);
    }

    private function guard(callable $action, string $flashKey): RedirectResponse
    {
        try {
            $action();
        } catch (VisitPaymentArrangementException $e) {
            return back()->with('error', __('visit_payment_arrangement.errors.'.$e->errorCode));
        }

        return back()->with('success', __('visit_payment_arrangement.flash.'.$flashKey));
    }
}
