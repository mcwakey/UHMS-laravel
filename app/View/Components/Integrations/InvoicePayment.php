<?php

namespace App\View\Components\Integrations;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\PaymentProviderTransaction;
use App\Services\Integrations\Payment\PaymentProviderResolver;
use App\Services\ModuleService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

/**
 * Inline "Pay by Mobile Money" card for the existing invoice screen — the third
 * payment path. Self-contained: all gating + data lives here (integration-owned),
 * so the billing view only needs <x-integrations.invoice-payment :invoice="$invoice" />.
 *
 * Renders nothing unless the payment_gateway module is enabled with an active
 * provider (or there is already a pending gateway transaction to show), so it is
 * invisible and harmless when the gateway is off.
 */
class InvoicePayment extends Component
{
    public bool $enabled = false;
    public bool $canInitiate = false;
    public bool $canVerify = false;
    public ?string $providerName = null;
    public string $balance = '0.00';
    public string $currency = 'GHS';
    public $pending;

    public function __construct(public Invoice $invoice)
    {
        $user = Auth::user();
        $resolver = app(PaymentProviderResolver::class);
        $activeProvider = $resolver->activeProvider();

        $status = $invoice->status?->value ?? $invoice->status;
        $payable = (float) $invoice->balance > 0
            && ! in_array($status, [InvoiceStatus::PAID->value, InvoiceStatus::CANCELLED->value], true);

        $this->enabled = app(ModuleService::class)->enabled('payment_gateway') && $activeProvider !== null;
        $this->providerName = $activeProvider?->name;
        $this->canInitiate = $this->enabled && $payable && (bool) $user?->can('integrations.payments.transactions.initiate');
        $this->canVerify = (bool) $user?->can('integrations.payments.transactions.verify');
        $this->currency = (string) config('integrations.default_currency', 'GHS');
        $this->balance = number_format((float) $invoice->balance, 2);

        $this->pending = PaymentProviderTransaction::where('invoice_id', $invoice->id)
            ->whereIn('status', [
                PaymentProviderTransaction::STATUS_INITIATED,
                PaymentProviderTransaction::STATUS_PENDING,
                PaymentProviderTransaction::STATUS_REQUIRES_CUSTOMER_ACTION,
            ])
            ->latest()
            ->get();
    }

    /** Hide entirely when the gateway is off and there is nothing pending. */
    public function shouldRender(): bool
    {
        return $this->enabled || $this->pending->isNotEmpty();
    }

    public function render()
    {
        return view('components.integrations.invoice-payment');
    }
}
