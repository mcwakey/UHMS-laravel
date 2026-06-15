<?php

namespace App\Http\Controllers\Billing;

use App\Enums\CreditNoteType;
use App\Http\Controllers\Controller;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Services\CreditNoteService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CreditNoteController extends Controller
{
    public function __construct(
        protected CreditNoteService $creditNoteService,
    ) {}

    public function index(Request $request)
    {
        $query = CreditNote::with([
            'invoice:id,invoice_number',
            'patient:id,first_name,last_name,patient_number',
            'issuedBy:id,first_name,last_name',
            'originalCreditNote:id,credit_note_number',
            'reversal:id,credit_note_number,reverses_credit_note_id',
        ])
            ->latest();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('credit_note_number', 'like', "%{$search}%")
                    ->orWhereHas('invoice', fn ($q2) => $q2->where('invoice_number', 'like', "%{$search}%"))
                    ->orWhereHas('patient', fn ($q2) => $q2
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%"));
            });
        }

        $creditNotes = $query->paginate(20)->withQueryString();
        $user = $request->user();

        $stats = [
            'total_credit_notes' => (float) CreditNote::active()->where('type', CreditNoteType::CREDIT_NOTE->value)->sum('amount'),
            'total_write_offs' => (float) CreditNote::active()->where('type', CreditNoteType::WRITE_OFF->value)->sum('amount'),
            'count' => CreditNote::active()->count(),
        ];

        return Inertia::render('Billing/CreditNotes/Index', [
            'creditNotes' => $creditNotes->through(fn (CreditNote $cn) => [
                'id' => $cn->id,
                'credit_note_number' => $cn->credit_note_number,
                'invoice_number' => $cn->invoice?->invoice_number,
                'invoice_url' => $cn->invoice ? route('admin.billing.invoices.show', $cn->invoice_id) : null,
                'patient_name' => $cn->patient ? trim($cn->patient->first_name . ' ' . $cn->patient->last_name) : '—',
                'patient_number' => $cn->patient?->patient_number,
                'type' => [
                    'value' => $cn->type?->value,
                    'label' => $cn->type?->label(),
                    'color' => $cn->type?->color(),
                ],
                'status' => $cn->status,
                'amount' => (float) $cn->amount,
                'reason' => $cn->reason,
                'issued_by' => $cn->issuedBy?->name,
                'created_at_display' => optional($cn->created_at)->format('d M Y'),
                'is_reversal' => (bool) $cn->is_reversal,
                'original_number' => $cn->originalCreditNote?->credit_note_number,
                'reversal_number' => $cn->reversal?->credit_note_number,
                'can_reverse' => $cn->status === 'issued'
                    && ! $cn->is_reversal
                    && ($cn->type === CreditNoteType::WRITE_OFF
                        ? (($user?->can('credit_notes.write_off') ?? false) || ($user?->can('billing.write_off.reverse') ?? false))
                        : (($user?->can('credit_notes.create') ?? false) || ($user?->can('billing.credit_note.reverse') ?? false))),
                'reverse_url' => route('admin.billing.credit-notes.reverse', $cn),
            ]),
            'stats' => $stats,
            'filters' => $request->only(['search', 'type', 'status']),
            'typeOptions' => collect(CreditNoteType::cases())->map(fn ($c) => [
                'value' => $c->value,
                'label' => $c->label(),
            ])->values(),
            'routes' => [
                'index' => route('admin.billing.credit-notes.index'),
                'create' => route('admin.billing.credit-notes.create'),
            ],
            'can' => [
                'create' => $user?->can('credit_notes.create') ?? false,
            ],
        ]);
    }

    public function create(Request $request)
    {
        $invoices = Invoice::with('patient:id,first_name,last_name,patient_number')
            ->whereIn('status', ['pending', 'partially_paid'])
            ->where('balance', '>', 0)
            ->latest()
            ->limit(200)
            ->get()
            ->map(fn (Invoice $inv) => [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'patient_name' => $inv->patient ? trim($inv->patient->first_name . ' ' . $inv->patient->last_name) : '—',
                'balance' => (float) $inv->balance,
            ]);

        $selected = null;
        if ($request->filled('invoice_id')) {
            $invoice = Invoice::with('items', 'patient:id,first_name,last_name')->find($request->invoice_id);
            if ($invoice) {
                $selected = [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'patient_name' => $invoice->patient ? trim($invoice->patient->first_name . ' ' . $invoice->patient->last_name) : '—',
                    'available' => $this->creditNoteService->availableToCredit($invoice),
                ];
            }
        }

        return Inertia::render('Billing/CreditNotes/Create', [
            'invoices' => $invoices,
            'selected' => $selected,
            'typeOptions' => collect(CreditNoteType::cases())->map(fn ($c) => [
                'value' => $c->value,
                'label' => $c->label(),
            ])->values(),
            'routes' => [
                'store' => route('admin.billing.credit-notes.store'),
                'index' => route('admin.billing.credit-notes.index'),
                'available' => route('admin.billing.credit-notes.available'),
            ],
        ]);
    }

    /**
     * JSON: outstanding amount available to credit for an invoice.
     */
    public function available(Request $request)
    {
        $invoice = Invoice::with('items')->findOrFail($request->integer('invoice_id'));

        return response()->json([
            'available' => $this->creditNoteService->availableToCredit($invoice),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'invoice_id' => ['required', 'exists:invoices,id'],
            'type' => ['required', Rule::in(array_column(CreditNoteType::cases(), 'value'))],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $invoice = Invoice::with('items')->findOrFail($data['invoice_id']);

        try {
            $creditNote = $this->creditNoteService->issue(
                $invoice,
                CreditNoteType::from($data['type']),
                (float) $data['amount'],
                $data['reason'],
                $data['notes'] ?? null,
            );
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        $redirect = $request->input('return') === 'invoice'
            ? redirect()->route('admin.billing.invoices.show', $invoice)
            : redirect()->route('admin.billing.credit-notes.index');

        return $redirect
            ->with('success', __('messages.billing.credit_note_issued', ['type' => $creditNote->type->label(), 'number' => $creditNote->credit_note_number]));
    }

    public function reverse(Request $request, CreditNote $creditNote)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $reversal = $this->creditNoteService->reverse($creditNote, $data['reason']);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('messages.billing.credit_note_reversed', [
            'number' => $creditNote->credit_note_number,
            'reversal' => $reversal->credit_note_number,
        ]));
    }

    public function cancel(Request $request, CreditNote $creditNote)
    {
        return $this->reverse($request, $creditNote);
    }
}
