<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountingPostingAttempt;
use App\Models\AccountingReconciliationItem;
use App\Models\AccountingReconciliationResolution;
use App\Models\AccountingReconciliationRun;
use App\Models\JournalEntry;
use App\Services\ReconciliationResolutionService;
use App\Services\SubledgerReconciliationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubledgerReconciliationController extends Controller
{
    public function __construct(
        protected SubledgerReconciliationService $reconciliations,
        protected ReconciliationResolutionService $resolutions,
    ) {}

    public function index()
    {
        return view('accounting.subledger-reconciliation.index', [
            'dashboard' => $this->reconciliations->dashboard(),
            'recentRuns' => AccountingReconciliationRun::query()
                ->with(['startedBy', 'approvedBy'])
                ->latest('created_at')
                ->limit(20)
                ->get(),
            'types' => AccountingReconciliationRun::TYPES,
        ]);
    }

    public function create()
    {
        return view('accounting.subledger-reconciliation.create', [
            'types' => AccountingReconciliationRun::TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'reconciliation_type' => ['required', Rule::in(AccountingReconciliationRun::TYPES)],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'as_of_date' => ['required', 'date', 'after_or_equal:period_start'],
            'tolerance_amount' => ['required', 'numeric', 'min:0', 'max:9999999999999999.99'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);
        $run = $this->reconciliations->start($data, $request->user());

        return redirect()->route('admin.accounting.subledger-reconciliation.show', $run)
            ->with('success', __('accounting.reconciliation_run_completed'));
    }

    public function show(AccountingReconciliationRun $reconciliationRun)
    {
        $reconciliationRun->load([
            'items.glAccount',
            'items.resolutions.resolvedBy',
            'resolutions.linkedJournalEntry',
            'resolutions.linkedPostingAttempt',
            'startedBy', 'completedBy', 'approvedBy', 'cancelledBy',
        ]);

        return view('accounting.subledger-reconciliation.show', [
            'run' => $reconciliationRun,
            'classificationCounts' => $reconciliationRun->items->countBy('classification'),
            'resolutionCounts' => $reconciliationRun->items->countBy('resolution_status'),
        ]);
    }

    public function item(AccountingReconciliationRun $reconciliationRun, AccountingReconciliationItem $item)
    {
        abort_unless($item->accounting_reconciliation_run_id === $reconciliationRun->id, 404);
        $item->load(['glAccount', 'resolutions.linkedJournalEntry', 'resolutions.linkedPostingAttempt', 'resolutions.resolvedBy']);

        return view('accounting.subledger-reconciliation.item', [
            'run' => $reconciliationRun,
            'item' => $item,
            'resolutionTypes' => AccountingReconciliationResolution::TYPES,
            'journals' => JournalEntry::query()->latest('entry_date')->limit(200)->get(['id', 'journal_number', 'description']),
            'postingAttempts' => AccountingPostingAttempt::query()->latest('last_attempted_at')->limit(200)->get(['id', 'source_type', 'source_id', 'status']),
        ]);
    }

    public function resolve(Request $request, AccountingReconciliationRun $reconciliationRun, AccountingReconciliationItem $item)
    {
        abort_unless($item->accounting_reconciliation_run_id === $reconciliationRun->id, 404);
        $data = $request->validate([
            'resolution_type' => ['required', Rule::in(AccountingReconciliationResolution::TYPES)],
            'resolution_note' => ['required', 'string', 'max:4000'],
            'linked_journal_entry_id' => ['nullable', 'exists:journal_entries,id'],
            'linked_posting_attempt_id' => ['nullable', 'exists:accounting_posting_attempts,id'],
            'linked_source_type' => ['nullable', 'string', 'max:120'],
            'linked_source_id' => ['nullable', 'integer', 'min:1'],
        ]);
        $this->resolutions->add($reconciliationRun, $item, $data, $request->user());

        return back()->with('success', __('accounting.reconciliation_resolution_added'));
    }

    public function approval(AccountingReconciliationRun $reconciliationRun)
    {
        $reconciliationRun->load(['items.glAccount', 'resolutions']);

        return view('accounting.subledger-reconciliation.approval', ['run' => $reconciliationRun]);
    }

    public function approve(Request $request, AccountingReconciliationRun $reconciliationRun)
    {
        $data = $request->validate(['approve_with_unresolved' => ['nullable', 'boolean']]);
        $this->reconciliations->approve($reconciliationRun, $request->user(), (bool) ($data['approve_with_unresolved'] ?? false));

        return redirect()->route('admin.accounting.subledger-reconciliation.show', $reconciliationRun)
            ->with('success', __('accounting.reconciliation_run_approved'));
    }

    public function cancel(Request $request, AccountingReconciliationRun $reconciliationRun)
    {
        $data = $request->validate(['cancellation_reason' => ['required', 'string', 'max:4000']]);
        $this->reconciliations->cancel($reconciliationRun, $data['cancellation_reason'], $request->user());

        return redirect()->route('admin.accounting.subledger-reconciliation.show', $reconciliationRun)
            ->with('success', __('accounting.reconciliation_run_cancelled'));
    }

    public function history(Request $request)
    {
        $runs = AccountingReconciliationRun::query()
            ->with(['startedBy', 'approvedBy'])
            ->when($request->type, fn ($query, $type) => $query->where('reconciliation_type', $type))
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->latest('created_at')
            ->paginate(30)
            ->withQueryString();

        return view('accounting.subledger-reconciliation.history', [
            'runs' => $runs,
            'types' => AccountingReconciliationRun::TYPES,
            'statuses' => AccountingReconciliationRun::STATUSES,
        ]);
    }
}
