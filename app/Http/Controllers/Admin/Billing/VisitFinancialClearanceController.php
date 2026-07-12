<?php

namespace App\Http\Controllers\Admin\Billing;

use App\Enums\VisitFinancialClearanceExceptionStatus;
use App\Enums\VisitFinancialClearanceExceptionType;
use App\Enums\VisitFinancialClearanceMode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\AssessVisitFinancialClearanceRequest;
use App\Http\Requests\Billing\ApproveVisitFinancialClearanceExceptionRequest;
use App\Http\Requests\Billing\CloseVisitFinanciallyRequest;
use App\Http\Requests\Billing\RejectVisitFinancialClearanceExceptionRequest;
use App\Http\Requests\Billing\RevokeVisitFinancialClearanceExceptionRequest;
use App\Http\Requests\Billing\RollbackVisitFinancialClearanceRequest;
use App\Http\Requests\Billing\StoreVisitFinancialClearanceExceptionRequest;
use App\Http\Requests\Billing\UpdateVisitFinancialClearanceSettingsRequest;
use App\Http\Requests\Billing\WithdrawVisitFinancialClearanceExceptionRequest;
use App\Models\Setting;
use App\Models\Visit;
use App\Models\VisitFinancialClearance;
use App\Models\VisitFinancialClearanceException;
use App\Services\Billing\VisitFinancialClearanceConfigurationService;
use App\Services\Billing\VisitFinancialClearanceExceptionService;
use App\Services\Billing\VisitFinancialClearanceService;
use Illuminate\Http\Request;

class VisitFinancialClearanceController extends Controller
{
    public function __construct(private VisitFinancialClearanceService $service, private VisitFinancialClearanceExceptionService $exceptions, private VisitFinancialClearanceConfigurationService $config) {}

    public function index(Request $request)
    {
        $query = VisitFinancialClearance::with(['visit.patient', 'currentException'])->latest('assessed_at');
        foreach (['status', 'basis'] as $filter) if ($request->filled($filter)) $query->where($filter, $request->string($filter));
        if ($request->boolean('requires_finance_action')) $query->requiringFinanceAction();
        return view('admin.billing.visit-financial-clearances.index', ['clearances' => $query->paginate(25)->withQueryString()]);
    }

    public function show(Request $request, Visit $visit)
    {
        $clearance = $visit->financialClearance()->with('currentException')->first();
        $history = $request->user()->can('visits.financial_clearance.history') ? $visit->financialClearanceHistory()->with('performer')->latest('performed_at')->take(50)->get() : collect();
        $exceptions = $visit->financialClearanceExceptions()->with(['requester', 'approver'])->latest()->get();
        return view('admin.billing.visit-financial-clearances.show', compact('visit', 'clearance', 'history', 'exceptions'));
    }

    public function assess(AssessVisitFinancialClearanceRequest $request, Visit $visit) { $this->service->assess($visit, $request->user(), true); return back()->with('success', __('visit_financial_clearance.flash.assessed')); }
    public function close(CloseVisitFinanciallyRequest $request, Visit $visit) { $this->service->financiallyClose($visit, $request->user(), $request->validated('reason')); return back()->with('success', __('visit_financial_clearance.flash.closed')); }
    public function reopen(Request $request, Visit $visit) { $data = $request->validate(['reason' => 'required|string|max:500']); $this->service->reopenFinancialClearance($visit, $data['reason'], $request->user()); return back()->with('success', __('visit_financial_clearance.flash.reopened')); }
    public function requestException(StoreVisitFinancialClearanceExceptionRequest $request, Visit $visit) { $d = $request->validated(); $this->exceptions->request($visit, VisitFinancialClearanceExceptionType::from($d['type']), (string) $d['requested_amount'], $d['request_reason'], $request->user(), $d['supporting_reference'] ?? null, $d['expires_at'] ?? null); return back()->with('success', __('visit_financial_clearance.flash.exception_requested')); }
    public function approve(ApproveVisitFinancialClearanceExceptionRequest $request, VisitFinancialClearanceException $exception) { $d = $request->validated(); $this->exceptions->approve($exception, (string) $d['approved_amount'], $d['reason'], $request->user()); return back()->with('success', __('visit_financial_clearance.flash.exception_approved')); }
    public function reject(RejectVisitFinancialClearanceExceptionRequest $request, VisitFinancialClearanceException $exception) { $this->exceptions->reject($exception, $request->validated('reason'), $request->user()); return back(); }
    public function withdraw(WithdrawVisitFinancialClearanceExceptionRequest $request, VisitFinancialClearanceException $exception) { $this->exceptions->withdraw($exception, $request->validated('reason'), $request->user()); return back(); }
    public function revoke(RevokeVisitFinancialClearanceExceptionRequest $request, VisitFinancialClearanceException $exception) { $this->exceptions->revoke($exception, $request->validated('reason'), $request->user()); return back(); }

    public function report() { return view('admin.billing.visit-financial-clearances.report', ['metrics' => ['pending' => VisitFinancialClearance::pending()->count(), 'cleared' => VisitFinancialClearance::cleared()->count(), 'conditional' => VisitFinancialClearance::conditionallyCleared()->count(), 'closed' => VisitFinancialClearance::financiallyClosed()->count(), 'stale' => VisitFinancialClearance::stale()->count(), 'outstanding' => VisitFinancialClearance::sum('patient_outstanding_snapshot')]]); }
    public function export() { return response()->streamDownload(function () { $h = fopen('php://output', 'w'); fputcsv($h, ['visit', 'status', 'basis', 'patient_outstanding', 'assessed_at']); VisitFinancialClearance::with('visit:id,visit_number')->chunk(500, fn ($rows) => $rows->each(fn ($c) => fputcsv($h, [$c->visit?->visit_number, $c->status->value, $c->basis?->value, $c->patient_outstanding_snapshot, $c->assessed_at]))); fclose($h); }, 'visit-financial-clearances-'.now()->format('Ymd-His').'.csv'); }

    public function settings() { return view('admin.billing.visit-financial-clearances.settings', ['configuredMode' => $this->config->configuredMode(), 'effectiveMode' => $this->config->effectiveMode(), 'forceDisabled' => $this->config->forceDisabled()]); }
    public function updateSettings(UpdateVisitFinancialClearanceSettingsRequest $request) { $mode = VisitFinancialClearanceMode::from($request->validated('mode')); if ($mode === VisitFinancialClearanceMode::ACTIVE) abort_unless($request->user()->can('billing.financial_clearance.settings.activate'), 403); Setting::setValue(VisitFinancialClearanceConfigurationService::GROUP, 'mode', $mode->value); app(\App\Services\ActivityLogService::class)->log('billing', 'VISIT_FINANCIAL_CLEARANCE_MODE_CHANGED', ['metadata' => ['mode' => $mode->value]]); return back()->with('success', __('visit_financial_clearance.flash.settings_updated')); }
    public function rollback(RollbackVisitFinancialClearanceRequest $request) { Setting::setValue(VisitFinancialClearanceConfigurationService::GROUP, 'mode', VisitFinancialClearanceMode::DISABLED->value); app(\App\Services\ActivityLogService::class)->log('billing', 'VISIT_FINANCIAL_CLEARANCE_ROLLBACK', ['metadata' => ['reason' => mb_substr($request->validated('reason'), 0, 500)]]); return back()->with('success', __('visit_financial_clearance.flash.rolled_back')); }
}
