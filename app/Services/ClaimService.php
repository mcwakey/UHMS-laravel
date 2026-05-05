<?php

namespace App\Services;

use App\Enums\ClaimItemStatus;
use App\Enums\ClaimStatus;
use App\Enums\ServiceType;
use App\Models\Claim;
use App\Models\ClaimItem;
use App\Models\Invoice;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClaimService
{
    /**
     * List claims with filters.
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return Claim::with(['insuranceProvider', 'patient', 'visit', 'invoice', 'assignedDoctor'])
            ->withCount('items')
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->search($s))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['provider_id'] ?? null, fn ($q, $p) => $q->byProvider($p))
            ->when($filters['date_from'] ?? null, fn ($q, $d) => $q->where('claim_date', '>=', $d))
            ->when($filters['date_to'] ?? null, fn ($q, $d) => $q->where('claim_date', '<=', $d))
            ->latest('claim_date')
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Create a claim from an invoice.
     */
    public function createFromInvoice(Invoice $invoice, ?int $providerId = null, ?int $doctorId = null): Claim
    {
        return DB::transaction(function () use ($invoice, $providerId, $doctorId) {
            $invoice->load(['items.serviceCatalog', 'visit.visitInsurance.insuranceProvider', 'patient']);

            $existingClaim = Claim::where('invoice_id', $invoice->id)->first();
            if ($existingClaim) {
                return $existingClaim->fresh(['items', 'insuranceProvider', 'patient', 'invoice']);
            }

            $providerId ??= $invoice->visit?->visitInsurance?->insurance_provider_id;

            if (! $providerId) {
                throw new \InvalidArgumentException('Select an insurance provider before creating this claim.');
            }

            $claimableItems = $this->claimableInvoiceItems($invoice);

            if ($claimableItems->isEmpty()) {
                throw new \InvalidArgumentException('This invoice has no insurance-covered items to claim.');
            }

            $periodDate = $invoice->visit?->visit_date?->toDateString()
                ?? $invoice->created_at?->toDateString()
                ?? now()->toDateString();

            $claim = Claim::create([
                'claim_number' => Claim::generateClaimNumber(),
                'insurance_provider_id' => $providerId,
                'patient_id' => $invoice->patient_id,
                'visit_id' => $invoice->visit_id,
                'invoice_id' => $invoice->id,
                'claim_date' => now()->toDateString(),
                'period_from' => $periodDate,
                'period_to' => $periodDate,
                'total_amount' => 0,
                'status' => ClaimStatus::DRAFT,
                'assigned_doctor_id' => $doctorId,
                'created_by' => Auth::id(),
            ]);

            foreach ($claimableItems as $item) {
                $quantity = max(1, (int) ($item->quantity ?? 1));
                $claimAmount = min((float) $item->nhis_approved_amount, (float) $item->total_price);

                ClaimItem::create([
                    'claim_id' => $claim->id,
                    'service_name' => $item->description ?? $item->service_name ?? 'Service',
                    'service_type' => $this->mapServiceType($item),
                    'quantity' => $quantity,
                    'unit_price' => round($claimAmount / $quantity, 2),
                    'total_price' => $claimAmount,
                    'status' => ClaimItemStatus::PENDING,
                ]);
            }

            $claim->recalculateTotal();

            return $claim->fresh(['items', 'insuranceProvider', 'patient']);
        });
    }

    /**
     * Create a claim manually.
     */
    public function create(array $data): Claim
    {
        return DB::transaction(function () use ($data) {
            $data['claim_number'] = Claim::generateClaimNumber();
            $data['created_by'] = Auth::id();
            $data['status'] = ClaimStatus::DRAFT;

            $items = $data['items'] ?? [];
            unset($data['items']);

            $claim = Claim::create($data);

            foreach ($items as $item) {
                $item['claim_id'] = $claim->id;
                $item['total_price'] = ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0);
                $item['status'] = ClaimItemStatus::PENDING;
                ClaimItem::create($item);
            }

            $claim->recalculateTotal();

            return $claim->fresh(['items', 'insuranceProvider', 'patient']);
        });
    }

    /**
     * Add an item to an existing claim.
     */
    public function addItem(Claim $claim, array $data): ClaimItem
    {
        $data['claim_id'] = $claim->id;
        $data['total_price'] = ($data['quantity'] ?? 1) * ($data['unit_price'] ?? 0);
        $data['status'] = ClaimItemStatus::PENDING;

        $item = ClaimItem::create($data);
        $claim->recalculateTotal();

        return $item;
    }

    /**
     * Remove an item from a claim.
     */
    public function removeItem(ClaimItem $item): void
    {
        $claim = $item->claim;
        $item->delete();
        $claim->recalculateTotal();
    }

    /**
     * Submit a claim for review.
     */
    public function submit(Claim $claim): Claim
    {
        if (! $claim->status->canTransitionTo(ClaimStatus::SUBMITTED)) {
            throw new \InvalidArgumentException('Cannot submit claim from current status.');
        }

        if ($claim->items()->count() === 0) {
            throw new \InvalidArgumentException('Cannot submit claim with no items.');
        }

        $claim->update([
            'status' => ClaimStatus::SUBMITTED,
            'submitted_at' => now(),
        ]);

        return $claim->fresh();
    }

    /**
     * Start reviewing a claim.
     */
    public function startReview(Claim $claim): Claim
    {
        if (! $claim->status->canTransitionTo(ClaimStatus::UNDER_REVIEW)) {
            throw new \InvalidArgumentException('Cannot start review from current status.');
        }

        $claim->update(['status' => ClaimStatus::UNDER_REVIEW]);

        return $claim;
    }

    /**
     * Review individual claim items (approve/reject each).
     */
    public function reviewItem(ClaimItem $item, string $action, ?float $approvedAmount = null, ?string $rejectionReason = null): ClaimItem
    {
        if ($action === 'approve') {
            $item->update([
                'status' => ClaimItemStatus::APPROVED,
                'approved_amount' => $approvedAmount ?? $item->total_price,
                'rejection_reason' => null,
            ]);
        } else {
            $item->update([
                'status' => ClaimItemStatus::REJECTED,
                'approved_amount' => 0,
                'rejection_reason' => $rejectionReason,
            ]);
        }

        return $item;
    }

    /**
     * Complete the review (approve/partially approve/reject the whole claim).
     */
    public function completeReview(Claim $claim, ?string $notes = null): Claim
    {
        $totalItems = $claim->items()->count();
        $approvedItems = $claim->items()->where('status', ClaimItemStatus::APPROVED)->count();
        $rejectedItems = $claim->items()->where('status', ClaimItemStatus::REJECTED)->count();

        $newStatus = match (true) {
            $approvedItems === $totalItems => ClaimStatus::APPROVED,
            $rejectedItems === $totalItems => ClaimStatus::REJECTED,
            default => ClaimStatus::PARTIALLY_APPROVED,
        };

        $claim->recalculateApproved();
        $claim->update([
            'status' => $newStatus,
            'reviewed_at' => now(),
            'reviewer_notes' => $notes,
        ]);

        return $claim->fresh();
    }

    /**
     * Mark claim as paid.
     */
    public function markPaid(Claim $claim): Claim
    {
        $claim->update(['status' => ClaimStatus::PAID]);
        return $claim;
    }

    /**
     * Appeal a rejected/partially approved claim.
     */
    public function appeal(Claim $claim): Claim
    {
        if (! $claim->status->canTransitionTo(ClaimStatus::APPEALED)) {
            throw new \InvalidArgumentException('Cannot appeal claim from current status.');
        }

        $claim->update(['status' => ClaimStatus::APPEALED]);

        // Reset item statuses for re-review
        $claim->items()->update([
            'status' => ClaimItemStatus::PENDING,
            'approved_amount' => null,
            'rejection_reason' => null,
        ]);

        return $claim->fresh();
    }

    /**
     * Get dashboard stats.
     */
    public function getStats(): array
    {
        return [
            'total_claims' => Claim::count(),
            'draft' => Claim::where('status', ClaimStatus::DRAFT)->count(),
            'submitted' => Claim::where('status', ClaimStatus::SUBMITTED)->count(),
            'under_review' => Claim::where('status', ClaimStatus::UNDER_REVIEW)->count(),
            'approved' => Claim::where('status', ClaimStatus::APPROVED)->count(),
            'partially_approved' => Claim::where('status', ClaimStatus::PARTIALLY_APPROVED)->count(),
            'rejected' => Claim::where('status', ClaimStatus::REJECTED)->count(),
            'paid' => Claim::where('status', ClaimStatus::PAID)->count(),
            'total_approved_amount' => Claim::where('status', ClaimStatus::PAID)->sum('approved_amount'),
            'pending_amount' => Claim::whereIn('status', [
                ClaimStatus::SUBMITTED->value,
                ClaimStatus::UNDER_REVIEW->value,
                ClaimStatus::APPROVED->value,
            ])->sum('total_amount'),
        ];
    }

    /**
     * Map invoice item type to claim service type.
     */
    private function mapServiceType($item): string
    {
        $serviceType = $item->service_type ?? null;
        if ($serviceType instanceof ServiceType) {
            return $serviceType->value;
        }

        if (is_string($serviceType) && $serviceType !== '') {
            return $serviceType;
        }

        $category = strtolower((string) ($item->serviceCatalog?->category ?? ''));

        return match ($category) {
            'consultation' => ServiceType::CONSULTATION->value,
            'investigation', 'lab', 'laboratory', 'radiology', 'imaging' => ServiceType::INVESTIGATION->value,
            'procedure', 'procedures' => ServiceType::PROCEDURE->value,
            'pharmacy', 'drug', 'drugs', 'medication', 'medications' => ServiceType::MEDICATION->value,
            'bed', 'bed_charge', 'ward', 'admission' => ServiceType::BED_CHARGE->value,
            default => ServiceType::OTHER->value,
        };
    }

    /**
     * @return Collection<int, \App\Models\InvoiceItem>
     */
    private function claimableInvoiceItems(Invoice $invoice): Collection
    {
        return $invoice->items
            ->filter(fn ($item) => $item->is_nhis_covered && (float) $item->nhis_approved_amount > 0)
            ->values();
    }
}
