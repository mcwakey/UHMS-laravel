<?php

namespace App\Http\Controllers\Admin\Billing;

use App\Enums\ClaimItemStatus;
use App\Enums\ClaimStatus;
use App\Enums\ServiceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClaimRequest;
use App\Models\Claim;
use App\Models\ClaimItem;
use App\Models\InsuranceProvider;
use App\Models\InsuranceType as InsuranceTypeModel;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Services\ClaimPreparationMirrorService;
use App\Services\Claims\ClaimPaymentService;
use App\Services\Claims\ClaimWorkflowManager;
use App\Services\ClaimService;
use Illuminate\Http\Request;

class ClaimController extends Controller
{
    public function __construct(
        private ClaimService $claimService,
        private ClaimWorkflowManager $workflowManager,
        private ClaimPaymentService $claimPaymentService,
        private ClaimPreparationMirrorService $claimPreparationMirrorService,
    ) {}

    /**
     * List all claims.
     */
    public function index(Request $request)
    {
        $stats = $this->claimService->getStats();
        $claims = $this->claimService->list($request->all());
        $providers = InsuranceProvider::with('insuranceType')->active()->orderBy('name')->get();
        $insuranceTypes = InsuranceTypeModel::active()->orderBy('name')->get();
        $statuses = ClaimStatus::cases();

        return view('claims.index', compact('claims', 'stats', 'providers', 'insuranceTypes', 'statuses'));
    }

    public function nhiaIndex(Request $request)
    {
        $request->merge(['workflow' => 'NHIA']);

        return $this->index($request);
    }

    public function eligibleVisits(Request $request)
    {
        $insuranceTypes = InsuranceTypeModel::active()
            ->whereNotNull('claim_workflow')
            ->orderBy('name')
            ->get();

        $selectedTypeCode = strtoupper((string) ($request->input('type') ?: $request->input('workflow') ?: ''));

        $visits = Visit::query()
            ->with([
                'patient',
                'visitInsurance.insuranceProvider.insuranceType',
                'latestInvoice.items',
            ])
            ->whereHas('visitInsurance.insuranceProvider.insuranceType', function ($query) use ($selectedTypeCode) {
                $query->where('requires_claim_submission', true);
                if ($selectedTypeCode !== '') {
                    $query->where('code', $selectedTypeCode);
                }
            })
            ->whereHas('invoices.items', function ($query) {
                $query->where(function ($q) {
                    $q->where('payer_type', 'insurance')
                        ->orWhere('insurance_covered', '>', 0)
                        ->orWhere(function ($legacy) {
                            $legacy->where('is_nhis_covered', true)
                                ->where('nhis_approved_amount', '>', 0);
                        });
                });
            })
            ->whereDoesntHave('invoices.claim', function ($query) {
                $query->whereNotIn('status', [ClaimStatus::CANCELLED->value, ClaimStatus::REJECTED->value]);
            })
            ->latest('visit_date')
            ->paginate(20)
            ->withQueryString();

        return view('claims.eligible-visits', compact('visits', 'insuranceTypes', 'selectedTypeCode'));
    }

    public function nhiaEligibleVisits(Request $request)
    {
        $request->merge(['type' => 'NHIA']);

        return $this->eligibleVisits($request);
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
                ->with('success', __('messages.claims.already_exists'));
        }

        $providersQuery = InsuranceProvider::active()->where('is_default', false);

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
            ->with('success', __('messages.claims.ready_for_review'));
    }

    public function prepareFromVisit(Request $request, Visit $visit)
    {
        $visit->loadMissing(['latestInvoice.items', 'visitInsurance.insuranceProvider.insuranceType']);

        if (! $visit->latestInvoice) {
            return back()->with('error', __('messages.claims.no_invoice'));
        }

        try {
            $claim = $this->claimService->createFromInvoice(
                $visit->latestInvoice,
                $visit->visitInsurance?->insurance_provider_id,
                $request->integer('assigned_doctor_id') ?: null,
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.claims.show', $claim)
            ->with('success', __('messages.claims.prepared_from_visit'));
    }

    /**
     * Store a new claim manually.
     */
    public function store(StoreClaimRequest $request)
    {
        $claim = $this->claimService->create($request->validated());

        return redirect()
            ->route('admin.claims.show', $claim)
            ->with('success', __('messages.claims.created'));
    }

    /**
     * Show claim details.
     */
    public function show(Claim $claim)
    {
        $claim->load([
            'insuranceType', 'insuranceProvider.insuranceType', 'insuranceProvider.tiers',
            'patient', 'visit.visitInsurance.insuranceTier', 'invoice',
            'items.invoiceItem', 'items.department', 'items.service', 'items.product',
            'assignedDoctor', 'createdByUser', 'preparedBy', 'submittedBy', 'statusLogs.performer', 'payments.receiver',
        ]);
        $validation = $this->claimService->validateClaim($claim);
        $clinicalMirror = $this->claimPreparationMirrorService->forClaim($claim);

        return view('claims.show', compact('claim', 'validation', 'clinicalMirror'));
    }

    /**
     * Submit claim for review.
     */
    public function updateVerificationCode(Request $request, Claim $claim)
    {
        $label = $claim->insuranceProvider?->verificationCodeLabel() ?: 'Verification Code';
        $request->validate([
            'verification_code' => ['nullable', 'string', 'max:120'],
        ], [], [
            'verification_code' => $label,
        ]);

        $this->claimService->updateVerificationCode($claim, $request->input('verification_code'));

        return back()->with('success', __('messages.claims.field_updated', ['label' => $label]));
    }

    public function validateClaim(Claim $claim)
    {
        $result = $this->claimService->validateClaim($claim);

        if ($result->valid) {
            return back()->with('success', __('messages.claims.validation_passed'));
        }

        return back()
            ->with('error', __('messages.claims.validation_issues'))
            ->with('claim_validation', $result->toArray());
    }

    public function markReady(Claim $claim)
    {
        try {
            $this->claimService->markReady($claim);

            return back()->with('success', __('messages.claims.ready_for_submission'));
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function submit(Request $request, Claim $claim)
    {
        $request->validate([
            'submission_mode' => ['nullable', 'string', 'max:40'],
            'submission_reference' => ['nullable', 'string', 'max:120'],
        ]);

        try {
            $this->workflowManager->forClaim($claim)->submit($claim, $request->user(), $request->only([
                'submission_mode', 'submission_reference',
            ]));

            return redirect()
                ->route('admin.claims.show', $claim)
                ->with('success', __('messages.claims.submitted'));
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function exportClaim(Request $request, Claim $claim)
    {
        return $this->workflowManager
            ->forClaim($claim)
            ->export($claim, $request->input('format'));
    }

    /**
     * Show review page.
     */
    public function review(Claim $claim)
    {
        $claim->load(['insuranceType', 'insuranceProvider.insuranceType', 'patient', 'visit', 'items.invoiceItem', 'items.department', 'assignedDoctor']);

        if (! $claim->is_reviewable) {
            return redirect()
                ->route('admin.claims.show', $claim)
                ->with('error', __('messages.claims.cannot_review_status'));
        }

        // Start review if submitted
        if ($claim->status === ClaimStatus::SUBMITTED) {
            $this->claimService->startReview($claim);
            $claim->refresh();
        }

        $validation = $this->claimService->validateClaim($claim);
        $clinicalMirror = $this->claimPreparationMirrorService->forClaim($claim);

        return view('claims.review', compact('claim', 'validation', 'clinicalMirror'));
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

        return back()->with('success', __('messages.claims.field_updated', ['label' => 'Item']));
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
            return back()->with('error', __('messages.claims.items_pending', ['count' => $pendingItems]));
        }

        $this->claimService->completeReview($claim, $request->reviewer_notes);

        return redirect()
            ->route('admin.claims.show', $claim)
            ->with('success', __('messages.claims.review_completed'));
    }

    /**
     * Mark claim as paid.
     */
    public function markPaid(Claim $claim)
    {
        $this->claimService->markPaid($claim);

        return back()->with('success', __('messages.claims.marked_paid'));
    }

    public function recordPayment(Request $request, Claim $claim)
    {
        $data = $request->validate([
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_reference' => ['nullable', 'string', 'max:120'],
            'payment_method' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->claimPaymentService->record($claim, $data, $request->user());

        return back()->with('success', __('messages.claims.payment_recorded'));
    }

    /**
     * Appeal a rejected claim.
     */
    public function appeal(Claim $claim)
    {
        try {
            $this->claimService->appeal($claim);

            return back()->with('success', __('messages.claims.appealed'));
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

        return back()->with('success', __('messages.claims.item_added'));
    }

    /**
     * Remove item from claim.
     */
    public function removeItem(ClaimItem $item)
    {
        $claim = $item->claim;

        if (! $claim->is_editable) {
            return back()->with('error', __('messages.claims.cannot_modify'));
        }

        $this->claimService->removeItem($item);

        return back()->with('success', __('messages.claims.item_removed'));
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

        $claims = Claim::with(['insuranceType', 'insuranceProvider', 'patient', 'visit', 'items'])
            ->when($request->provider_id, fn ($q, $p) => $q->byProvider($p))
            ->when($request->date_from, fn ($q, $d) => $q->where('claim_date', '>=', $d))
            ->when($request->date_to, fn ($q, $d) => $q->where('claim_date', '<=', $d))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest('claim_date')
            ->get();

        // Build CSV export
        $filename = 'claims_export_'.now()->format('Ymd_His').'.csv';
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
                    $claim->patient->first_name.' '.$claim->patient->last_name,
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
