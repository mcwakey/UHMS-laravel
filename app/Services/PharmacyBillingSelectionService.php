<?php

namespace App\Services;

use App\Enums\DepartmentType;
use App\Enums\PrescriptionStatus;
use App\Models\Department;
use App\Models\InvoiceItem;
use App\Models\PharmacyBillingSelection;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Product;
use App\Models\StockLocation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PharmacyBillingSelectionService
{
    public function __construct(
        private BillingService $billing,
        private StockBalanceService $balances,
        private StockLocationService $locations,
    ) {}

    public function billSelectedItems(Prescription $prescription, array $items): Collection
    {
        $prescription->loadMissing(['visit', 'items.drug.product', 'items.billingSelections']);

        if (! $prescription->visit) {
            throw new RuntimeException('Prescription has no visit, so it cannot be billed.');
        }

        $selectedPayloads = collect($items)
            ->filter(fn ($payload) => ! empty($payload['selected']))
            ->all();

        if (empty($selectedPayloads)) {
            throw new RuntimeException('Select at least one prescription item to bill.');
        }

        $pharmacyDepartment = $this->pharmacyDepartment();
        $pharmacyLocation = $this->pharmacyLocation($pharmacyDepartment);
        $prepared = collect();
        $requestedByProduct = [];

        foreach ($selectedPayloads as $itemId => $payload) {
            $item = $prescription->items->firstWhere('id', (int) $itemId);

            if (! $item) {
                throw new RuntimeException('Selected prescription item was not found on this prescription.');
            }

            $product = $this->productForItem($item);
            $quantity = (float) ($payload['quantity'] ?? 0);
            $prescribedQuantity = (float) ($item->quantity ?? 0);
            $alreadyBilled = $this->billedQuantityForItem($item);
            $remainingToBill = max(0.0, $prescribedQuantity - $alreadyBilled);

            if ($quantity <= 0) {
                throw new RuntimeException("Selected quantity for {$item->drug_name} must be greater than zero.");
            }

            if ($quantity > $remainingToBill) {
                throw new RuntimeException("Selected quantity for {$item->drug_name} cannot exceed the remaining prescribed quantity ({$remainingToBill}).");
            }

            $requestedByProduct[$product->id] = ($requestedByProduct[$product->id] ?? 0.0) + $quantity;

            $prepared->push([
                'item' => $item,
                'product' => $product,
                'quantity' => $quantity,
                'prescribed_quantity' => $prescribedQuantity,
                'notes' => $payload['notes'] ?? null,
            ]);
        }

        $availableByProduct = $this->balances->getQuantitiesForProductsAtLocation(
            array_keys($requestedByProduct),
            $pharmacyLocation,
        );

        foreach ($requestedByProduct as $productId => $requestedQuantity) {
            $available = (float) ($availableByProduct[$productId] ?? 0);

            if ($requestedQuantity > $available) {
                $productName = Product::query()->whereKey($productId)->value('name') ?? "product #{$productId}";
                throw new RuntimeException("Cannot bill {$productName}: pharmacy stock is {$available}, selected quantity is {$requestedQuantity}.");
            }
        }

        return DB::transaction(function () use ($prepared, $prescription, $pharmacyDepartment) {
            $created = collect();
            $userId = Auth::id();

            foreach ($prepared as $row) {
                /** @var PrescriptionItem $item */
                $item = $row['item'];
                /** @var Product $product */
                $product = $row['product'];
                $quantity = $row['quantity'];

                $selection = PharmacyBillingSelection::create([
                    'visit_id' => $prescription->visit_id,
                    'patient_id' => $prescription->patient_id,
                    'prescription_id' => $prescription->id,
                    'prescription_item_id' => $item->id,
                    'product_id' => $product->id,
                    'prescribed_quantity' => $row['prescribed_quantity'],
                    'selected_quantity' => $quantity,
                    'billed_quantity' => 0,
                    'dispensed_quantity' => 0,
                    'selected_by' => $userId,
                    'status' => PharmacyBillingSelection::STATUS_SELECTED,
                    'notes' => $row['notes'],
                ]);

                $invoiceItem = $this->billing->addProductToVisitInvoice(
                    $prescription->visit,
                    $product,
                    InvoiceItem::SOURCE_PHARMACY_BILLING_SELECTION,
                    $selection->id,
                    $quantity,
                    $pharmacyDepartment?->id,
                    $product->name.' x '.rtrim(rtrim(number_format($quantity, 4, '.', ''), '0'), '.'),
                );

                $selection->forceFill([
                    'invoice_item_id' => $invoiceItem->id,
                    'billed_quantity' => $quantity,
                    'billed_by' => $userId,
                    'status' => PharmacyBillingSelection::STATUS_BILLED,
                ])->save();

                $created->push($selection->fresh(['invoiceItem', 'product', 'prescriptionItem']));
            }

            $this->updatePrescriptionStatus($prescription);

            app(VisitPathwayService::class)->record($prescription->visit, 'PHARMACY_BILLED', [
                'source' => $prescription,
                'department_id' => $pharmacyDepartment?->id,
                'title' => 'Pharmacy items billed',
                'description' => $created->count() . ' item(s) selected for dispensing',
                'created_by' => $userId,
            ]);

            return $created;
        });
    }

    public function remainingBilledQuantityForItem(PrescriptionItem $item): float
    {
        $totals = PharmacyBillingSelection::query()
            ->where('prescription_item_id', $item->id)
            ->where('status', '!=', PharmacyBillingSelection::STATUS_CANCELLED)
            ->selectRaw('COALESCE(SUM(billed_quantity), 0) as billed, COALESCE(SUM(dispensed_quantity), 0) as dispensed')
            ->first();

        return max(0.0, (float) $totals->billed - (float) $totals->dispensed);
    }

    public function billedQuantityForItem(PrescriptionItem $item): float
    {
        return (float) PharmacyBillingSelection::query()
            ->where('prescription_item_id', $item->id)
            ->where('status', '!=', PharmacyBillingSelection::STATUS_CANCELLED)
            ->sum('billed_quantity');
    }

    public function dispensedQuantityForItem(PrescriptionItem $item): float
    {
        return (float) PharmacyBillingSelection::query()
            ->where('prescription_item_id', $item->id)
            ->where('status', '!=', PharmacyBillingSelection::STATUS_CANCELLED)
            ->sum('dispensed_quantity');
    }

    public function recordDispensed(PrescriptionItem $item, float $quantity, ?int $userId = null): void
    {
        if ($quantity <= 0) {
            throw new RuntimeException('Dispense quantity must be greater than zero.');
        }

        DB::transaction(function () use ($item, $quantity, $userId) {
            $remaining = $quantity;
            $selections = PharmacyBillingSelection::query()
                ->where('prescription_item_id', $item->id)
                ->whereIn('status', [
                    PharmacyBillingSelection::STATUS_BILLED,
                    PharmacyBillingSelection::STATUS_PARTIALLY_DISPENSED,
                ])
                ->whereColumn('dispensed_quantity', '<', 'billed_quantity')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $available = $selections->sum(fn (PharmacyBillingSelection $selection) => $selection->remaining_quantity);

            if ($quantity > $available) {
                throw new RuntimeException("Cannot dispense {$quantity}; only {$available} billed quantity remains.");
            }

            foreach ($selections as $selection) {
                if ($remaining <= 0) {
                    break;
                }

                $deduct = min($remaining, $selection->remaining_quantity);
                $newDispensed = (float) $selection->dispensed_quantity + $deduct;

                $selection->forceFill([
                    'dispensed_quantity' => $newDispensed,
                    'dispensed_by' => $userId ?? Auth::id(),
                    'status' => $newDispensed >= (float) $selection->billed_quantity
                        ? PharmacyBillingSelection::STATUS_DISPENSED
                        : PharmacyBillingSelection::STATUS_PARTIALLY_DISPENSED,
                ])->save();

                $remaining -= $deduct;
            }
        });
    }

    public function updatePrescriptionStatus(Prescription $prescription): void
    {
        if (($prescription->status?->value ?? $prescription->status) === PrescriptionStatus::CANCELLED->value) {
            return;
        }

        $prescription->loadMissing('items');

        $prescribed = (float) $prescription->items->sum(fn (PrescriptionItem $item) => (float) ($item->quantity ?? 0));
        $totals = PharmacyBillingSelection::query()
            ->where('prescription_id', $prescription->id)
            ->where('status', '!=', PharmacyBillingSelection::STATUS_CANCELLED)
            ->selectRaw('COALESCE(SUM(billed_quantity), 0) as billed, COALESCE(SUM(dispensed_quantity), 0) as dispensed')
            ->first();

        $billed = (float) $totals->billed;
        $dispensed = (float) $totals->dispensed;

        $status = match (true) {
            $prescribed > 0 && $dispensed >= $prescribed => PrescriptionStatus::DISPENSED,
            $dispensed > 0 => PrescriptionStatus::PARTIALLY_DISPENSED,
            $prescribed > 0 && $billed >= $prescribed => PrescriptionStatus::BILLED,
            $billed > 0 => PrescriptionStatus::PARTIALLY_BILLED,
            default => PrescriptionStatus::PENDING,
        };

        if (($prescription->status?->value ?? $prescription->status) !== $status->value) {
            $prescription->forceFill(['status' => $status->value])->save();
        }
    }

    public function pharmacyLocation(?Department $department = null): StockLocation
    {
        $department ??= $this->pharmacyDepartment();

        $location = $department
            ? $this->locations->getDefaultLocationForDepartment($department)
            : null;

        $location ??= StockLocation::query()
            ->where('type', 'pharmacy')
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        if (! $location) {
            throw new RuntimeException('No active Pharmacy stock location is configured.');
        }

        return $location;
    }

    public function pharmacyDepartment(): ?Department
    {
        return Department::query()
            ->where('type', DepartmentType::PHARMACY->value)
            ->orderBy('id')
            ->first();
    }

    private function productForItem(PrescriptionItem $item): Product
    {
        $item->loadMissing('drug.product');

        if (! $item->drug || ! $item->drug->product) {
            throw new RuntimeException("{$item->drug_name} is not linked to a product and cannot be billed or dispensed.");
        }

        return $item->drug->product;
    }
}