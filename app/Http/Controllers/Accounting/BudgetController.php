<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Budget;
use App\Models\BudgetCommitment;
use App\Models\Department;
use App\Models\FiscalYear;
use App\Services\BudgetApprovalService;
use App\Services\BudgetAvailabilityService;
use App\Services\CommitmentService;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function index(Request $request, BudgetAvailabilityService $availability)
    {
        $fiscalYears = FiscalYear::orderByDesc('start_date')->get();
        $fiscalYearId = $request->integer('fiscal_year_id') ?: $fiscalYears->first()?->id;
        $budgets = Budget::with(['fiscalYear', 'lines.account', 'lines.department'])
            ->when($fiscalYearId, fn ($query) => $query->where('fiscal_year_id', $fiscalYearId))
            ->orderByDesc('created_at')
            ->paginate(10);
        $summary = $availability->summary($fiscalYearId);

        return view('accounting.budgets.index', [
            'budgets' => $budgets,
            'summary' => $summary,
            'fiscalYears' => $fiscalYears,
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'accounts' => Account::active()->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'fiscal_year_id' => ['required', 'integer', 'exists:fiscal_years,id'],
            'name' => ['required', 'string', 'max:191'],
            'enforcement_mode' => ['required', 'string', 'in:warning,blocking'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $budget = Budget::create($data + ['created_by' => $request->user()?->id]);

        return redirect()->route('admin.accounting.budgets.index', ['fiscal_year_id' => $budget->fiscal_year_id])
            ->with('success', 'Budget draft created.');
    }

    public function addLine(Request $request, Budget $budget)
    {
        $data = $request->validate([
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $budget->lines()->create($data);

        return back()->with('success', 'Budget line added.');
    }

    public function submit(Budget $budget, BudgetApprovalService $approval)
    {
        $approval->submit($budget, auth()->user());

        return back()->with('success', 'Budget submitted for approval.');
    }

    public function approve(Budget $budget, BudgetApprovalService $approval)
    {
        $approval->approve($budget, auth()->user());

        return back()->with('success', 'Budget approved and activated.');
    }

    public function commitments(Request $request)
    {
        $commitments = BudgetCommitment::with(['fiscalYear', 'budget', 'movements'])
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('accounting.budgets.commitments', [
            'commitments' => $commitments,
            'fiscalYears' => FiscalYear::orderByDesc('start_date')->get(),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'accounts' => Account::active()->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    public function storeCommitment(Request $request, CommitmentService $commitments)
    {
        $data = $request->validate([
            'fiscal_year_id' => ['required', 'integer', 'exists:fiscal_years,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'source_reference' => ['nullable', 'string', 'max:191'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'over_budget_acknowledged' => ['nullable', 'boolean'],
        ]);

        $commitments->create($data + [
            'over_budget_acknowledged' => $request->boolean('over_budget_acknowledged'),
        ], $request->user());

        return back()->with('success', 'Budget commitment created.');
    }

    public function releaseCommitment(Request $request, BudgetCommitment $commitment, CommitmentService $commitments)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $commitments->release($commitment, (float) $data['amount'], $request->user(), $data['notes'] ?? null);

        return back()->with('success', 'Budget commitment released.');
    }

    public function cancelCommitment(Request $request, BudgetCommitment $commitment, CommitmentService $commitments)
    {
        $data = $request->validate(['notes' => ['nullable', 'string', 'max:1000']]);
        $commitments->cancel($commitment, $request->user(), $data['notes'] ?? null);

        return back()->with('success', 'Budget commitment cancelled.');
    }
}
