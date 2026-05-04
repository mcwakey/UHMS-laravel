<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClaimItemStatus;
use App\Enums\ClaimStatus;
use App\Enums\InsuranceType;
use App\Enums\ServiceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClaimRequest;
use App\Models\Claim;
use App\Models\ClaimItem;
use App\Models\InsuranceProvider;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Services\ClaimService;
use Illuminate\Http\Request;

class ClaimController extends Controller
{
    public function __construct(
        private ClaimService $claimService,
    ) {}

    /**
     * List all claims.
     */
    public function index(Request $request)
    {
        $stats = $this->claimService->getStats();
        $claims = $this->claimService->list($request->all());
        $providers = InsuranceProvider::active()->orderBy('name')->get();
        $statuses = ClaimStatus::cases();

        return view('claims.index', compact('claims', 'stats', 'providers', 'statuses'));
    }

    /**
     * Show create claim form.
     */
    public function create(Request $request)
    {
        $invoice = $request->has('invoice_id')
            ? Invoice::with(['items.serviceCatalog', 'patient', 'visit.visitInsurance.insuranceProvider', 'claim'])->findOrFail($request->invoice_id)
            : null;

        if ($invoice?->claim) {
            return redirect()
                ->route('admin.claims.show', $invoice->claim)
                ->with('success', 'A claim already exists for this invoice.');
        }

        $providersQuery = InsuranceProvider::active();
        if ($invoice && (float) $invoice->nhis_amount > 0) {
            $providersQuery->where('type', InsuranceType::NHIA->value);
        }

        $providers = $providersQuery->orderBy('name')->get();
        $doctors = User::role('Doctor')->orderBy('first_name')->get();
        $patients = Patient::where('status', 'active')->orderBy('first_name')->get();
        $visits = Visit::with('patient')->latest('visit_date')->limit(200)->get();
        $serviceTypes = ServiceType::cases();

        return view('claims.create', compact('invoice', 'providers', 'doctors', 'patients', 'visits', 'serviceTypes'));
    }

    /**
     * Store a new claim from invoice.
     */
    public function storeFromInvoice(Request $request)
    {
        $request->validate([
            'invoice_id' => ['required', 'exists:invoices,id'],
            'insurance_provider_id' => ['nullable', 'exists:insurance_providers,id'],
            'assigned_doctor_id' => ['nullable', 'exists:users,id'],
        ]);

        $invoice = Invoice::with(['items.serviceCatalog', 'visit.visitInsurance.insuranceProvider', 'patient'])->findOrFail($request->invoice_id);

        try {
            $claim = $this->claimService->createFromInvoice(
                $invoice,
                $request->integer('insurance_provider_id') ?: null,
                $request->integer('assigned_doctor_id') ?: null,
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.claims.show', $claim)
            ->with('success', 'NHIS claim is ready for review.');
    }

    /**
     * Store a new claim manually.
     */
    public function store(StoreClaimRequest $request)
    {
        $claim = $this->claimService->create($request->validated());

        return redirect()
            ->route('admin.claims.show', $claim)
            ->with('success', 'Claim created successfully.');
    }

    /**
     * Show claim details.
     */
    public function show(Claim $claim)
    {
        $claim->load([
            'insuranceProvider.tiers', 'patient', 'visit.visitInsurance.insuranceTier', 'invoice',
            'items', 'assignedDoctor', 'createdByUser',
        ]);

        return view('claims.show', compact('claim'));
    }

    /**
     * Submit claim for review.
     */
    public function submit(Claim $claim)
    {
        try {
            $this->claimService->submit($claim);
            return redirect()
                ->route('admin.claims.show', $claim)
                ->with('success', 'Claim submitted for review.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show review page.
     */
    public function review(Claim $claim)
    {
        $claim->load(['insuranceProvider', 'patient', 'visit', 'items', 'assignedDoctor']);

        if (! $claim->is_reviewable) {
            return redirect()
                ->route('admin.claims.show', $claim)
                ->with('error', 'This claim cannot be reviewed in its current status.');
        }

        // Start review if submitted
        if ($claim->status === ClaimStatus::SUBMITTED) {
            $this->claimService->startReview($claim);
            $claim->refresh();
        }

        return view('claims.review', compact('claim'));
    }

    /**
     * Process review of individual items.
     */
    public function reviewItem(Request $request, Claim $claim, ClaimItem $item)
    {
        abort_if($item->claim_id !== $claim->id, 404);

        $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'approved_amount' => ['nullable', 'numeric', 'min:0'],
            'rejection_reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->claimService->reviewItem(
            $item,
            $request->action,
            $request->approved_amount ? (float) $request->approved_amount : null,
            $request->rejection_reason,
        );

        return back()->with('success', "Item {$request->action}d successfully.");
    }

    /**
     * Complete the review process.
     */
    public function completeReview(Request $request, Claim $claim)
    {
        $request->validate([
            'reviewer_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        // Ensure all items have been reviewed
        $pendingItems = $claim->items()->where('status', ClaimItemStatus::PENDING)->count();
        if ($pendingItems > 0) {
            return back()->with('error', "Please review all items. {$pendingItems} item(s) still pending.");
        }

        $this->claimService->completeReview($claim, $request->reviewer_notes);

        return redirect()
            ->route('admin.claims.show', $claim)
            ->with('success', 'Claim review completed.');
    }

    /**
     * Mark claim as paid.
     */
    public function markPaid(Claim $claim)
    {
        $this->claimService->markPaid($claim);

        return back()->with('success', 'Claim marked as paid.');
    }

    /**
     * Appeal a rejected claim.
     */
    public function appeal(Claim $claim)
    {
        try {
            $this->claimService->appeal($claim);
            return back()->with('success', 'Claim has been appealed and sent for re-review.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Add item to existing claim.
     */
    public function addItem(Request $request, Claim $claim)
    {
        $request->validate([
            'service_name' => ['required', 'string', 'max:255'],
            'service_type' => ['required', 'string'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $this->claimService->addItem($claim, $request->only([
            'service_name', 'service_type', 'quantity', 'unit_price',
        ]));

        return back()->with('success', 'Item added to claim.');
    }

    /**
     * Remove item from claim.
     */
    public function removeItem(ClaimItem $item)
    {
        $claim = $item->claim;

        if (! $claim->is_editable) {
            return back()->with('error', 'Cannot modify items on this claim.');
        }

        $this->claimService->removeItem($item);

        return back()->with('success', 'Item removed from claim.');
    }

    /**
     * Export claims (placeholder for Excel export).
     */
    public function export(Request $request)
    {
        $request->validate([
            'provider_id' => ['nullable', 'exists:insurance_providers,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'status' => ['nullable', 'string'],
        ]);

        $claims = Claim::with(['insuranceProvider', 'patient', 'items'])
            ->when($request->provider_id, fn ($q, $p) => $q->byProvider($p))
            ->when($request->date_from, fn ($q, $d) => $q->where('claim_date', '>=', $d))
            ->when($request->date_to, fn ($q, $d) => $q->where('claim_date', '<=', $d))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest('claim_date')
            ->get();

        // Build CSV export
        $filename = 'claims_export_' . now()->format('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($claims) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'Claim #', 'Provider', 'Patient Name',
                'Visit Date', 'Claim Date', 'Total Amount', 'Approved Amount',
                'Status', 'Items Count',
            ]);

            foreach ($claims as $claim) {
                fputcsv($file, [
                    $claim->claim_number,
                    $claim->insuranceProvider->name,
                    $claim->patient->first_name . ' ' . $claim->patient->last_name,
                    $claim->visit->visit_date?->format('Y-m-d') ?? '',
                    $claim->claim_date->format('Y-m-d'),
                    $claim->total_amount,
                    $claim->approved_amount ?? '',
                    $claim->status->label(),
                    $claim->items->count(),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
