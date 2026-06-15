<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\FiscalYear;
use App\Services\AccountingPeriodService;
use App\Services\YearEndClosingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FiscalYearController extends Controller
{
    public function index()
    {
        $years = FiscalYear::withCount('periods')->orderByDesc('start_date')->paginate(20);

        return view('accounting.fiscal-years.index', compact('years'));
    }

    public function store(Request $request, AccountingPeriodService $service)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('fiscal_years', 'name')],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
        ]);

        $service->createFiscalYear($data, $request->user());

        return back()->with('success', __('messages.accounting.fiscal_year_created'));
    }

    public function close(Request $request, FiscalYear $fiscalYear, AccountingPeriodService $service)
    {
        $service->closeFiscalYear($fiscalYear, $request->user());

        return back()->with('success', __('messages.accounting.fiscal_year_closed'));
    }

    public function reopen(Request $request, FiscalYear $fiscalYear, AccountingPeriodService $service)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $service->reopenFiscalYear($fiscalYear, $request->user(), $data['reason']);

        return back()->with('success', __('messages.accounting.fiscal_year_reopened'));
    }

    public function yearEndClose(Request $request, FiscalYear $fiscalYear, YearEndClosingService $service)
    {
        $service->close($fiscalYear, $request->user());

        return back()->with('success', __('messages.accounting.year_end_closed'));
    }
}
