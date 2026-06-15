<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountingPeriod;
use App\Models\FiscalYear;
use App\Services\AccountingPeriodService;
use Illuminate\Http\Request;

class AccountingPeriodController extends Controller
{
    public function index(Request $request)
    {
        $periods = AccountingPeriod::with('fiscalYear')
            ->when($request->fiscal_year_id, fn ($q, $id) => $q->where('fiscal_year_id', $id))
            ->orderByDesc('start_date')
            ->paginate(24)
            ->withQueryString();

        $fiscalYears = FiscalYear::orderByDesc('start_date')->get();

        return view('accounting.periods.index', compact('periods', 'fiscalYears'));
    }

    public function store(Request $request, AccountingPeriodService $service)
    {
        $data = $request->validate([
            'fiscal_year_id' => ['required', 'exists:fiscal_years,id'],
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
        ]);

        $service->createPeriod($data, $request->user());

        return back()->with('success', __('messages.accounting.period_created'));
    }

    public function close(Request $request, AccountingPeriod $period, AccountingPeriodService $service)
    {
        $service->closePeriod($period, $request->user());

        return back()->with('success', __('messages.accounting.period_closed'));
    }

    public function reopen(Request $request, AccountingPeriod $period, AccountingPeriodService $service)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $service->reopenPeriod($period, $request->user(), $data['reason']);

        return back()->with('success', __('messages.accounting.period_reopened'));
    }
}
