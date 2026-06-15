<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationMatch;
use App\Models\BankStatementLine;
use App\Services\BankMatchSuggestionService;
use App\Services\BankReconciliationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BankReconciliationController extends Controller
{
    public function __construct(protected BankReconciliationService $service) {}

    public function index(Request $request)
    {
        $reconciliations = BankReconciliation::with(['bankAccount', 'preparedBy', 'approvedBy'])
            ->when($request->filled('bank_account_id'), fn ($q) => $q->where('bank_account_id', $request->bank_account_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest('period_end')
            ->paginate(20)
            ->withQueryString();

        return view('accounting.bank.reconciliations.index', [
            'reconciliations' => $reconciliations,
            'bankAccounts' => BankAccount::active()->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request)
    {
        return view('accounting.bank.reconciliations.create', [
            'bankAccounts' => BankAccount::active()->orderBy('name')->get(),
            'selectedBankAccountId' => $request->integer('bank_account_id') ?: null,
        ]);
    }

    public function prepare(Request $request)
    {
        $data = $request->validate([
            'bank_account_id' => ['required', 'exists:bank_accounts,id'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date'],
            'statement_opening_balance' => ['nullable', 'numeric'],
            'statement_closing_balance' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $account = BankAccount::findOrFail($data['bank_account_id']);
        $reconciliation = $this->service->prepare($account, $data, $request->user());

        return redirect()->route('admin.accounting.bank.reconciliations.show', $reconciliation)
            ->with('success', __('messages.accounting.reconciliation_prepared'));
    }

    public function show(BankReconciliation $reconciliation)
    {
        $reconciliation->load([
            'bankAccount.glAccount', 'preparedBy', 'approvedBy', 'reopenedBy',
            'adjustments.account', 'adjustments.journalEntry',
            'matches' => fn ($q) => $q->where('status', BankReconciliationMatch::STATUS_ACTIVE),
        ]);

        $lines = BankStatementLine::query()
            ->where('bank_account_id', $reconciliation->bank_account_id)
            ->whereHas('import', fn ($q) => $q->whereIn('status', \App\Models\BankStatementImport::USABLE_STATUSES))
            ->whereDate('transaction_date', '>=', $reconciliation->period_start)
            ->whereDate('transaction_date', '<=', $reconciliation->period_end)
            ->orderBy('transaction_date')
            ->orderBy('line_number')
            ->get();

        return view('accounting.bank.reconciliations.show', [
            'reconciliation' => $reconciliation,
            'lines' => $lines,
            'contraAccounts' => \App\Models\Account::active()->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    public function statement(BankReconciliation $reconciliation)
    {
        $reconciliation->load(['bankAccount.glAccount', 'adjustments', 'preparedBy', 'approvedBy']);

        return view('accounting.bank.reconciliations.statement', compact('reconciliation'));
    }

    public function suggestions(Request $request, BankReconciliation $reconciliation, BankStatementLine $line, BankMatchSuggestionService $suggestions)
    {
        abort_unless($line->bank_account_id === $reconciliation->bank_account_id, 404);

        return response()->json([
            'suggestions' => $suggestions->suggestForLine($reconciliation, $line),
        ]);
    }

    public function match(Request $request, BankReconciliation $reconciliation, BankStatementLine $line)
    {
        $data = $request->validate([
            'matchable_type' => ['required', 'string', Rule::in(BankReconciliationService::MATCHABLE_TYPES)],
            'matchable_id' => ['required', 'integer'],
            'matched_amount' => ['required', 'numeric', 'min:0.01'],
            'match_method' => ['nullable', 'string'],
        ]);

        $this->service->match(
            $reconciliation,
            $line,
            $data['matchable_type'],
            (int) $data['matchable_id'],
            (float) $data['matched_amount'],
            $data['match_method'] ?? BankReconciliationMatch::METHOD_MANUAL,
            $request->user(),
        );

        return back()->with('success', __('messages.accounting.line_matched'));
    }

    public function unmatch(Request $request, BankReconciliation $reconciliation, BankReconciliationMatch $match)
    {
        abort_unless($match->bank_reconciliation_id === $reconciliation->id, 404);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        $this->service->unmatch($match, $data['reason'] ?? '', $request->user());

        return back()->with('success', __('messages.accounting.line_unmatched'));
    }

    public function approve(Request $request, BankReconciliation $reconciliation)
    {
        $data = $request->validate([
            'tolerance' => ['nullable', 'numeric', 'min:0'],
            'override' => ['nullable', 'boolean'],
        ]);

        $override = $request->boolean('override')
            && ($request->user()?->can('accounting.bank_reconciliation.reverse') ?? false);

        $this->service->approve($reconciliation, $request->user(), (float) ($data['tolerance'] ?? 0), $override);

        return back()->with('success', __('messages.accounting.reconciliation_approved'));
    }

    public function reopen(Request $request, BankReconciliation $reconciliation)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $this->service->reopen($reconciliation, $data['reason'], $request->user());

        return back()->with('success', __('messages.accounting.reconciliation_reopened'));
    }

    public function reverse(Request $request, BankReconciliation $reconciliation)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $this->service->reverse($reconciliation, $data['reason'], $request->user());

        return back()->with('success', __('messages.accounting.reconciliation_reversed'));
    }
}
