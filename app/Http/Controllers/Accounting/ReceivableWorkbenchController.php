<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\InvoiceReceivable;
use App\Models\ReceivableCase;
use App\Models\ReceivableStatementRun;
use App\Models\User;
use App\Services\ReceivableCaseService;
use App\Services\ReceivableRecommendationService;
use App\Services\ReceivableStatementService;
use App\Services\ReceivableWorkbenchService;
use Illuminate\Http\Request;

class ReceivableWorkbenchController extends Controller
{
    public function index(Request $request, ReceivableWorkbenchService $workbench)
    {
        $filters = $request->only(['as_of', 'payer_type', 'status']);
        $data = $workbench->dashboard($filters);

        return view('accounting.receivables.index', [
            ...$data,
            'filters' => $filters,
            'receivables' => InvoiceReceivable::with(['invoice', 'patient', 'insuranceProvider', 'sponsor', 'corporateClient'])
                ->open()
                ->when($request->payer_type, fn ($query, $type) => $query->where('payer_type', $type))
                ->orderByRaw('due_date IS NULL, due_date ASC')
                ->limit(100)
                ->get(),
            'collectors' => User::orderBy('name')->get(['id', 'name']),
            'statements' => ReceivableStatementRun::latest()->limit(10)->get(),
        ]);
    }

    public function openCase(Request $request, ReceivableCaseService $cases)
    {
        $data = $request->validate([
            'invoice_receivable_id' => ['required', 'integer', 'exists:invoice_receivables,id'],
            'case_type' => ['nullable', 'string', 'max:40'],
            'priority' => ['nullable', 'string', 'in:low,normal,high,critical'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'allow_duplicate_active' => ['nullable', 'boolean'],
        ]);

        $case = $cases->openFromReceivable(InvoiceReceivable::findOrFail($data['invoice_receivable_id']), $data, $request->user());

        return redirect()->route('admin.accounting.receivables.index', ['case' => $case->id])
            ->with('success', __('receivables.case_opened'));
    }

    public function assign(Request $request, ReceivableCase $case, ReceivableCaseService $cases)
    {
        $data = $request->validate(['assigned_to' => ['required', 'integer', 'exists:users,id']]);
        $cases->assign($case, User::findOrFail($data['assigned_to']), $request->user());

        return back()->with('success', __('receivables.case_assigned'));
    }

    public function followup(Request $request, ReceivableCase $case, ReceivableCaseService $cases)
    {
        $data = $request->validate([
            'followup_type' => ['required', 'string', 'max:30'],
            'followup_date' => ['required', 'date'],
            'next_followup_date' => ['nullable', 'date'],
            'contact_person' => ['nullable', 'string', 'max:191'],
            'contact_channel' => ['nullable', 'string', 'max:191'],
            'summary' => ['required', 'string', 'max:2000'],
            'outcome' => ['required', 'string', 'max:40'],
        ]);
        $cases->addFollowup($case, $data, $request->user());

        return back()->with('success', __('receivables.followup_recorded'));
    }

    public function promise(Request $request, ReceivableCase $case, ReceivableCaseService $cases)
    {
        $data = $request->validate([
            'promised_by' => ['nullable', 'string', 'max:191'],
            'promise_date' => ['required', 'date'],
            'expected_payment_date' => ['required', 'date'],
            'promised_amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $cases->addPromise($case, $data, $request->user());

        return back()->with('success', __('receivables.promise_recorded'));
    }

    public function dispute(Request $request, ReceivableCase $case, ReceivableCaseService $cases)
    {
        $data = $request->validate([
            'source_type' => ['nullable', 'string', 'max:120'],
            'source_id' => ['nullable', 'integer'],
            'dispute_reason' => ['required', 'string', 'max:191'],
            'disputed_amount' => ['required', 'numeric', 'min:0.01'],
            'recommended_action' => ['nullable', 'string', 'max:40'],
        ]);
        $cases->addDispute($case, $data, $request->user());

        return back()->with('success', __('receivables.dispute_recorded'));
    }

    public function dunning(Request $request, ReceivableCase $case, ReceivableCaseService $cases)
    {
        $data = $request->validate([
            'notice_level' => ['required', 'string', 'max:40'],
            'notice_date' => ['required', 'date'],
            'delivery_channel' => ['nullable', 'string', 'max:30'],
            'recipient_name' => ['nullable', 'string', 'max:191'],
            'recipient_contact' => ['nullable', 'string', 'max:191'],
            'subject' => ['nullable', 'string', 'max:191'],
            'body' => ['nullable', 'string'],
        ]);
        $cases->generateDunningNotice($case, $data, $request->user());

        return back()->with('success', __('receivables.dunning_generated'));
    }

    public function statement(Request $request, ReceivableStatementService $statements)
    {
        $data = $request->validate([
            'payer_type' => ['required', 'string', 'max:30'],
            'payer_id' => ['nullable', 'integer'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ]);
        $statements->generate($data['payer_type'], $data['payer_id'] ?? null, $data['period_start'], $data['period_end'], $request->user());

        return back()->with('success', __('receivables.statement_generated'));
    }

    public function approveStatement(Request $request, ReceivableStatementRun $statement, ReceivableStatementService $statements)
    {
        $statements->approve($statement, $request->user());

        return back()->with('success', __('receivables.statement_approved'));
    }

    public function recommendWriteoff(Request $request, ReceivableCase $case, ReceivableRecommendationService $recommendations)
    {
        $data = $this->recommendationData($request);
        $recommendations->recommendWriteoff($case, $data, $request->user());

        return back()->with('success', __('receivables.writeoff_recommended'));
    }

    public function recommendCreditNote(Request $request, ReceivableCase $case, ReceivableRecommendationService $recommendations)
    {
        $data = $this->recommendationData($request);
        $recommendations->recommendCreditNote($case, $data, $request->user());

        return back()->with('success', __('receivables.creditnote_recommended'));
    }

    private function recommendationData(Request $request): array
    {
        return $request->validate([
            'source_type' => ['required', 'string', 'max:120'],
            'source_id' => ['required', 'integer'],
            'recommended_amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);
    }
}
