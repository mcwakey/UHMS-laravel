<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\CorporateClient;
use App\Models\InsuranceProvider;
use App\Models\Invoice;
use App\Models\InvoiceReceivable;
use App\Models\Sponsor;
use App\Services\ReceivableAllocationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReceivableController extends Controller
{
    public function __construct(protected ReceivableAllocationService $allocationService) {}

    public function reallocate(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'from_receivable_id' => ['required', 'integer', 'exists:invoice_receivables,id'],
            'target_payer_type' => ['required', Rule::in([
                InvoiceReceivable::PAYER_PATIENT,
                InvoiceReceivable::PAYER_INSURANCE,
                InvoiceReceivable::PAYER_SPONSOR,
                InvoiceReceivable::PAYER_CORPORATE,
            ])],
            'target_payer_id' => ['nullable', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $from = InvoiceReceivable::where('invoice_id', $invoice->id)->findOrFail($data['from_receivable_id']);
        $targetPayerId = $this->validatedTargetPayerId($data['target_payer_type'], $data['target_payer_id'] ?? null, $invoice);

        try {
            $this->allocationService->reallocateResponsibility(
                $invoice,
                $from,
                $data['target_payer_type'],
                $targetPayerId,
                (float) $data['amount'],
                $data['reason'],
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', __('messages.billing.receivable_reallocated'));
    }

    private function validatedTargetPayerId(string $payerType, ?int $payerId, Invoice $invoice): ?int
    {
        return match ($payerType) {
            InvoiceReceivable::PAYER_PATIENT => $invoice->patient_id,
            InvoiceReceivable::PAYER_INSURANCE => $payerId && InsuranceProvider::whereKey($payerId)->exists()
                ? $payerId
                : throw \Illuminate\Validation\ValidationException::withMessages(['target_payer_id' => 'Choose an insurance provider.']),
            InvoiceReceivable::PAYER_SPONSOR => $payerId && Sponsor::whereKey($payerId)->exists()
                ? $payerId
                : throw \Illuminate\Validation\ValidationException::withMessages(['target_payer_id' => 'Choose a sponsor.']),
            InvoiceReceivable::PAYER_CORPORATE => $payerId && CorporateClient::whereKey($payerId)->exists()
                ? $payerId
                : throw \Illuminate\Validation\ValidationException::withMessages(['target_payer_id' => 'Choose a corporate client.']),
            default => null,
        };
    }
}
