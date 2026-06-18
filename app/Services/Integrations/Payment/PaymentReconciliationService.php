<?php

namespace App\Services\Integrations\Payment;

use App\Models\PaymentProviderCallback;
use App\Models\PaymentProviderTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Read-model for the payment reconciliation dashboard: summary cards + a
 * filtered transaction query. No mutations here — verification/expiry go through
 * PaymentVerificationService / PaymentGatewayService.
 */
class PaymentReconciliationService
{
    public function staleThresholdMinutes(): int
    {
        return (int) config('integrations.stale_pending_minutes', 30);
    }

    /** @return array<string,int> */
    public function metrics(): array
    {
        $T = PaymentProviderTransaction::query();
        $pendingStatuses = [
            PaymentProviderTransaction::STATUS_INITIATED,
            PaymentProviderTransaction::STATUS_PENDING,
            PaymentProviderTransaction::STATUS_REQUIRES_CUSTOMER_ACTION,
        ];
        $staleCutoff = now()->subMinutes($this->staleThresholdMinutes());

        return [
            'pending' => (clone $T)->whereIn('status', $pendingStatuses)->count(),
            'stale_pending' => (clone $T)->whereIn('status', $pendingStatuses)
                ->where('created_at', '<', $staleCutoff)->count(),
            'verified_not_linked' => (clone $T)->where('status', PaymentProviderTransaction::STATUS_VERIFIED)
                ->whereNull('uhms_payment_id')->count(),
            'paid_linked' => (clone $T)->whereNotNull('uhms_payment_id')->count(),
            'failed' => (clone $T)->where('status', PaymentProviderTransaction::STATUS_FAILED)->count(),
            'amount_mismatch' => (clone $T)->where('error_code', 'amount_mismatch')->count(),
            'unknown_callbacks' => PaymentProviderCallback::where('processing_error', 'unknown_reference')->count(),
            'callbacks_awaiting_verification' => PaymentProviderCallback::where('processed', false)->count(),
            'provider_errors' => (clone $T)->whereNotNull('error_code')
                ->whereNotIn('error_code', ['amount_mismatch'])->count(),
        ];
    }

    /** @param array $filters provider_code,status,from,to,invoice_number,patient_id,payer_phone,amount,reference,linked */
    public function query(array $filters = []): LengthAwarePaginator
    {
        return PaymentProviderTransaction::query()
            ->with(['provider', 'invoice', 'patient'])
            ->when(! empty($filters['provider_code']), fn (Builder $q) => $q->where('provider_code', $filters['provider_code']))
            ->when(! empty($filters['status']), fn (Builder $q) => $q->where('status', $filters['status']))
            ->when(! empty($filters['from']), fn (Builder $q) => $q->whereDate('created_at', '>=', $filters['from']))
            ->when(! empty($filters['to']), fn (Builder $q) => $q->whereDate('created_at', '<=', $filters['to']))
            ->when(! empty($filters['reference']), fn (Builder $q) => $q->where('payment_reference', 'like', '%' . $filters['reference'] . '%'))
            ->when(! empty($filters['payer_phone']), fn (Builder $q) => $q->where('payer_phone', 'like', '%' . $filters['payer_phone'] . '%'))
            ->when(! empty($filters['amount']), fn (Builder $q) => $q->where('amount', $filters['amount']))
            ->when(! empty($filters['patient_id']), fn (Builder $q) => $q->where('patient_id', $filters['patient_id']))
            ->when(! empty($filters['invoice_number']), fn (Builder $q) => $q->whereHas('invoice', fn (Builder $i) => $i->where('invoice_number', 'like', '%' . $filters['invoice_number'] . '%')))
            ->when(($filters['linked'] ?? '') === 'linked', fn (Builder $q) => $q->whereNotNull('uhms_payment_id'))
            ->when(($filters['linked'] ?? '') === 'unlinked', fn (Builder $q) => $q->whereNull('uhms_payment_id'))
            ->latest()
            ->paginate(25)
            ->withQueryString();
    }
}
