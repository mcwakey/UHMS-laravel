<?php

namespace App\Http\Controllers\Accounting;

use App\Enums\Accounting\JournalEntryStatus;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\CreditNote;
use App\Models\Department;
use App\Models\Invoice;
use App\Models\InvoiceDiscount;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Services\JournalEntryService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JournalEntryController extends Controller
{
    public function index(Request $request)
    {
        $entries = JournalEntry::with(['createdBy', 'postedBy', 'lines'])
            ->when($request->search, function ($query, string $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('journal_number', 'like', "%{$search}%")
                        ->orWhere('reference_number', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->date_from, fn ($q, $date) => $q->whereDate('entry_date', '>=', $date))
            ->when($request->date_to, fn ($q, $date) => $q->whereDate('entry_date', '<=', $date))
            ->latest('entry_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('accounting.journals.index', [
            'entries' => $entries,
            'statuses' => JournalEntryStatus::cases(),
        ]);
    }

    public function create()
    {
        return view('accounting.journals.create', $this->formData());
    }

    public function store(Request $request, JournalEntryService $service)
    {
        $entry = $service->createDraft($this->validated($request));

        return redirect()
            ->route('admin.accounting.journals.show', $entry)
            ->with('success', __('messages.accounting.journal_drafted'));
    }

    public function show(JournalEntry $journal)
    {
        $journal->load(['lines.account', 'lines.department', 'fiscalYear', 'accountingPeriod', 'createdBy', 'postedBy', 'reversedEntry', 'reversalEntries']);
        $sourceLink = $this->sourceLink($journal);

        return view('accounting.journals.show', compact('journal', 'sourceLink'));
    }

    public function edit(JournalEntry $journal)
    {
        if ($journal->status !== JournalEntryStatus::DRAFT) {
            return redirect()
                ->route('admin.accounting.journals.show', $journal)
                ->with('error', __('messages.accounting.journal_cannot_edit'));
        }

        $journal->load('lines');

        return view('accounting.journals.edit', array_merge($this->formData(), compact('journal')));
    }

    public function update(Request $request, JournalEntry $journal, JournalEntryService $service)
    {
        $service->updateDraft($journal, $this->validated($request));

        return redirect()
            ->route('admin.accounting.journals.show', $journal)
            ->with('success', __('messages.accounting.journal_updated'));
    }

    public function post(Request $request, JournalEntry $journal, JournalEntryService $service)
    {
        $service->post($journal, $request->user());

        return redirect()
            ->route('admin.accounting.journals.show', $journal)
            ->with('success', __('messages.accounting.journal_posted'));
    }

    public function reverse(Request $request, JournalEntry $journal, JournalEntryService $service)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $reversal = $service->reverse($journal, $data['reason'], $request->user());

        return redirect()
            ->route('admin.accounting.journals.show', $reversal)
            ->with('success', __('messages.accounting.journal_reversed'));
    }

    public function cancel(Request $request, JournalEntry $journal, JournalEntryService $service)
    {
        $service->cancelDraft($journal, $request->user());

        return redirect()
            ->route('admin.accounting.journals.show', $journal)
            ->with('success', __('messages.accounting.journal_cancelled'));
    }

    protected function formData(): array
    {
        return [
            'accounts' => Account::active()->orderBy('code')->get(),
            'departments' => Department::orderBy('name')->get(),
        ];
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'entry_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['nullable', 'exists:accounts,id'],
            'lines.*.description' => ['nullable', 'string', 'max:1000'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.department_id' => ['nullable', 'exists:departments,id'],
        ]);
    }

    protected function sourceLink(JournalEntry $journal): ?array
    {
        if (! $journal->reference_type || ! $journal->reference_id) {
            return null;
        }

        return match ($journal->reference_type) {
            Invoice::class => ($invoice = Invoice::query()->find($journal->reference_id))
                ? ['label' => "Invoice {$invoice->invoice_number}", 'url' => route('admin.billing.invoices.show', $invoice)]
                : null,
            Payment::class => ($payment = Payment::with('invoice')->find($journal->reference_id))
                ? ['label' => "Payment {$payment->payment_number}", 'url' => $payment->invoice ? route('admin.billing.invoices.show', $payment->invoice) : route('admin.billing.payments.index')]
                : null,
            InvoiceDiscount::class => ($discount = InvoiceDiscount::with('invoice')->find($journal->reference_id))
                ? ['label' => "Invoice discount #{$discount->id}", 'url' => $discount->invoice ? route('admin.billing.invoices.show', $discount->invoice) : route('admin.billing.invoices.index')]
                : null,
            CreditNote::class => ($creditNote = CreditNote::with('invoice')->find($journal->reference_id))
                ? ['label' => "{$creditNote->type?->label()} {$creditNote->credit_note_number}", 'url' => $creditNote->invoice ? route('admin.billing.invoices.show', $creditNote->invoice) : route('admin.billing.credit-notes.index')]
                : null,
            default => null,
        };
    }
}
