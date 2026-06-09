<?php

namespace App\Services;

use App\Enums\BillingType;
use App\Enums\InvoiceStatus;
use App\Enums\LogModule;
use App\Enums\StockMovementType;
use App\Models\Drug;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\LabRequest;
use App\Models\ServiceCatalog;
use App\Models\StockBalance;
use App\Models\StockLocation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Walk-in "counter sale" for an external patient with no facility visit.
 *
 * Raises a single standalone cash invoice (patient/visit-less) with drug and/or
 * investigation line items, then fulfils each: pharmacy stock is decremented for
 * drugs and a visit-less lab request is raised for investigations. The invoice is
 * the sale record — payment + receipt go through the existing (null-safe) billing
 * screens.
 */
class CounterSaleService
{
    public function __construct(
        private ProductStockMovementService $movements,
        private ActivityLogService $log,
    ) {}

    /**
     * @param  array{external_party_name:string, external_party_contact?:?string,
     *               drugs?:array<int,array{id:int,quantity:int}>,
     *               services?:array<int,array{id:int,quantity:int}>}  $data
     */
    public function create(array $data, User $user): Invoice
    {
        $partyName = trim((string) ($data['external_party_name'] ?? ''));
        if ($partyName === '') {
            throw ValidationException::withMessages(['external_party_name' => 'Recipient name is required.']);
        }

        // Resolve + price every line before touching the database.
        $lines = $this->resolveLines($data);
        if (empty($lines)) {
            throw ValidationException::withMessages(['lines' => 'Add at least one drug or investigation.']);
        }

        $grandTotal = round(array_sum(array_map(fn ($l) => $l['line_total'], $lines)), 2);
        $sex = $data['external_party_sex'] ?? null;
        $age = $data['external_party_age'] ?? null;
        $demographics = trim(implode(' · ', array_filter([
            $sex,
            $age !== null && $age !== '' ? $age.'y' : null,
            $data['external_party_contact'] ?? null,
        ])));

        return DB::transaction(function () use ($lines, $grandTotal, $partyName, $data, $user, $sex, $age, $demographics) {
            $invoice = Invoice::create([
                'invoice_number' => Invoice::generateNumber('INV', 'invoices', 'invoice_number'),
                'visit_id' => null,
                'patient_id' => null,
                'external_party_name' => $partyName,
                'billing_type' => BillingType::CASH->value,
                'subtotal' => $grandTotal,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'nhis_amount' => 0,
                'total_amount' => $grandTotal,
                'amount_paid' => 0,
                'balance' => $grandTotal,
                'status' => InvoiceStatus::PENDING->value,
                'due_date' => now()->addDays(1),
                'notes' => 'Counter sale — '.$partyName.($demographics !== '' ? ' ('.$demographics.')' : ''),
                'created_by' => $user->id,
            ]);

            $serviceLines = [];
            foreach ($lines as $line) {
                $item = InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'visit_id' => null,
                    'patient_id' => null,
                    'service_catalog_id' => $line['service_catalog_id'] ?? null,
                    'product_id' => $line['product_id'] ?? null,
                    'department_id' => $line['department_id'] ?? null,
                    'source_type' => $line['source_type'],
                    'source_id' => null,
                    'description' => $line['description'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'cash_price' => $line['unit_price'],
                    'selected_price' => $line['unit_price'],
                    'insurance_covered' => 0,
                    'discount_amount' => 0,
                    'patient_payable' => $line['line_total'],
                    'paid_amount' => 0,
                    'balance' => $line['line_total'],
                    'payment_status' => 'unpaid',
                    'total_price' => $line['line_total'],
                    'payer_type' => 'cash',
                    'created_by' => $user->id,
                ]);

                if ($line['kind'] === 'drug') {
                    $this->dispenseStock($line['drug'], (int) $line['quantity'], $item, $invoice, $user);
                } elseif ($line['kind'] === 'service') {
                    $serviceLines[] = ['service' => $line['service'], 'item' => $item, 'quantity' => (int) $line['quantity']];
                }
                // 'procedure' lines are billed only — no lab request is raised.
            }

            // Raise a visit-less lab request per target department for the investigations.
            $this->raiseLabRequests($serviceLines, $partyName, $data['external_party_contact'] ?? null, $sex, $age, $user);

            $this->log->log(LogModule::BILLING, 'COUNTER_SALE_CREATED', [
                'description' => "Counter sale {$invoice->invoice_number} for {$partyName} (₵{$grandTotal}).",
                'causer' => $user,
            ], $invoice);

            return $invoice->load('items');
        });
    }

    /**
     * Resolve drug + service lines into priced specs.
     *
     * @return array<int,array<string,mixed>>
     */
    private function resolveLines(array $data): array
    {
        $lines = [];

        foreach ($data['drugs'] ?? [] as $row) {
            $qty = (int) ($row['quantity'] ?? 0);
            if (empty($row['id']) || $qty < 1) {
                continue;
            }
            $drug = Drug::with('product')->find($row['id']);
            if (! $drug) {
                continue;
            }
            if (! $drug->product_id) {
                throw ValidationException::withMessages([
                    'drugs' => "{$drug->display_name} is not linked to a stock product and cannot be sold.",
                ]);
            }
            $price = (float) ($drug->product->base_price ?? 0);
            $lines[] = [
                'kind' => 'drug',
                'drug' => $drug,
                'product_id' => $drug->product_id,
                'service_catalog_id' => null,
                'department_id' => null,
                'source_type' => InvoiceItem::SOURCE_PHARMACY_PRODUCT,
                'description' => $drug->display_name,
                'quantity' => $qty,
                'unit_price' => $price,
                'line_total' => round($price * $qty, 2),
            ];
        }

        foreach ($data['services'] ?? [] as $row) {
            $qty = (int) ($row['quantity'] ?? 0);
            if (empty($row['id']) || $qty < 1) {
                continue;
            }
            $service = ServiceCatalog::find($row['id']);
            if (! $service) {
                continue;
            }
            $price = (float) ($service->price ?? 0);
            $lines[] = [
                'kind' => 'service',
                'service' => $service,
                'product_id' => null,
                'service_catalog_id' => $service->id,
                'department_id' => $service->department_id,
                'source_type' => InvoiceItem::SOURCE_INVESTIGATION_SERVICE,
                'description' => $service->name,
                'quantity' => $qty,
                'unit_price' => $price,
                'line_total' => round($price * $qty, 2),
            ];
        }

        foreach ($data['procedures'] ?? [] as $row) {
            $qty = (int) ($row['quantity'] ?? 0);
            if (empty($row['id']) || $qty < 1) {
                continue;
            }
            $service = ServiceCatalog::find($row['id']);
            if (! $service) {
                continue;
            }
            $price = (float) ($service->price ?? 0);
            $lines[] = [
                'kind' => 'procedure',
                'service' => $service,
                'product_id' => null,
                'service_catalog_id' => $service->id,
                'department_id' => $service->department_id,
                'source_type' => InvoiceItem::SOURCE_PROCEDURE_SERVICE,
                'description' => $service->name,
                'quantity' => $qty,
                'unit_price' => $price,
                'line_total' => round($price * $qty, 2),
            ];
        }

        return $lines;
    }

    /**
     * Decrement pharmacy stock for a drug line across pharmacy-type locations.
     * Mirrors the deduction loop in PharmacyService::dispenseItem. Insufficient
     * stock throws — rolling back the whole sale.
     */
    private function dispenseStock(Drug $drug, int $quantity, InvoiceItem $item, Invoice $invoice, User $user): void
    {
        $pharmacyLocIds = StockLocation::where('type', 'pharmacy')->pluck('id');

        $balances = $pharmacyLocIds->isNotEmpty()
            ? StockBalance::where('product_id', $drug->product_id)
                ->whereIn('stock_location_id', $pharmacyLocIds)
                ->where('quantity_on_hand', '>', 0)
                ->orderByDesc('quantity_on_hand')
                ->get()
            : collect();

        $available = (float) $balances->sum('quantity_on_hand');
        if ($available < $quantity) {
            throw ValidationException::withMessages([
                'drugs' => "Insufficient stock for {$drug->display_name}. Available: {$available}, requested: {$quantity}.",
            ]);
        }

        $left = $quantity;
        foreach ($balances as $balance) {
            if ($left <= 0) {
                break;
            }
            $deduct = min($left, (float) $balance->quantity_on_hand);
            $this->movements->createMovement([
                'product_id' => $drug->product_id,
                'stock_location_id' => $balance->stock_location_id,
                'movement_type' => StockMovementType::PHARMACY_DISPENSED,
                'quantity' => $deduct,
                'source_type' => InvoiceItem::class,
                'source_id' => $item->id,
                'allow_negative' => false,
                'performed_by' => $user->id,
                'notes' => "Counter sale {$invoice->invoice_number}",
            ]);
            $left -= $deduct;
        }
    }

    /**
     * Create one visit-less lab request per target department for the investigation
     * lines, with each item linked to its (already billed) invoice item.
     *
     * @param  array<int,array{service:ServiceCatalog,item:InvoiceItem,quantity:int}>  $serviceLines
     */
    private function raiseLabRequests(array $serviceLines, string $partyName, ?string $contact, ?string $sex, $age, User $user): void
    {
        if (empty($serviceLines)) {
            return;
        }

        $byDepartment = collect($serviceLines)->groupBy(fn ($l) => $l['service']->department_id ?? 0);

        foreach ($byDepartment as $departmentId => $group) {
            $request = LabRequest::create([
                'request_number' => LabRequest::generateRequestNumber(),
                'visit_id' => null,
                'patient_id' => null,
                'external_party_name' => $partyName,
                'external_party_contact' => $contact,
                'external_party_sex' => $sex,
                'external_party_age' => $age !== null && $age !== '' ? (int) $age : null,
                'requested_by' => $user->id,
                'department_id' => null,
                'target_department_id' => $departmentId ?: null,
                'clinical_info' => 'Walk-in counter sale',
                'urgency' => 'routine',
                'status' => 'processing',
            ]);

            foreach ($group as $line) {
                $request->items()->create([
                    'service_id' => $line['service']->id,
                    'name' => $line['service']->name,
                    'status' => 'accepted',
                    'accepted_at' => now(),
                    'accepted_by' => $user->id,
                    'billed_at' => now(),
                    'invoice_item_id' => $line['item']->id,
                    'unit_price' => $line['item']->unit_price,
                ]);
            }

            $this->log->log(LogModule::INVESTIGATION, 'WALKIN_LAB_REQUEST_CREATED', [
                'description' => "Walk-in lab request {$request->request_number} for {$partyName}.",
                'causer' => $user,
            ], $request);
        }
    }
}
