{{-- Inline mobile-money / online payment on the invoice screen (third path). --}}
<div class="card border-success-subtle">
    <div class="card-header bg-success-subtle d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0"><i class="ti ti-device-mobile me-1"></i>{{ __('payments.gateway.pay_with_mobile_money') }}</h6>
        @if($providerName)<span class="badge badge-soft-success">{{ $providerName }}</span>@endif
    </div>
    <div class="card-body">

        {{-- Pending gateway transactions for this invoice --}}
        @forelse($pending as $txn)
            <div class="border rounded p-2 mb-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small"><code>{{ $txn->payment_reference }}</code></div>
                        <div class="fw-semibold">{{ $txn->currency }} {{ number_format((float) $txn->amount, 2) }}</div>
                    </div>
                    <x-status-badge :status="$txn->status" domain="payment_transaction" size="sm" />
                </div>
                @if($txn->payer_phone)<div class="text-muted small mt-1">{{ $txn->payer_phone }}</div>@endif
                @if($canVerify)
                    <form method="POST" action="{{ route('admin.integrations.payments.transactions.verify-inline', $txn) }}" class="mt-2">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                            <i class="ti ti-refresh me-1"></i>{{ __('payments.gateway.recheck') }}
                        </button>
                    </form>
                @endif
            </div>
        @empty
        @endforelse

        {{-- Start a new mobile-money charge for the outstanding balance --}}
        @if($canInitiate)
            <form method="POST" action="{{ route('admin.integrations.payments.invoices.charge', $invoice) }}">
                @csrf
                <div class="alert alert-light border py-2 small mb-2">
                    {{ __('payments.gateway.amount_due') }}: <strong>{{ $currency }} {{ $balance }}</strong>
                </div>
                <div class="mb-2">
                    <label class="form-label small mb-1">{{ __('payments.gateway.payer_phone') }} <span class="text-danger">*</span></label>
                    <input type="tel" name="payer_phone" value="{{ old('payer_phone', $invoice->patient?->phone) }}" class="form-control form-control-sm" required>
                </div>
                <div class="mb-2">
                    <label class="form-label small mb-1">{{ __('payments.gateway.method') }}</label>
                    <select name="payment_method" class="form-select form-select-sm">
                        <option value="mtn_momo">{{ __('payments.gateway.method_mtn_momo') }}</option>
                        <option value="vodafone_cash">{{ __('payments.gateway.method_telecel_cash') }}</option>
                        <option value="airteltigo_money">{{ __('payments.gateway.method_airteltigo_money') }}</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-success btn-sm w-100">
                    <i class="ti ti-device-mobile me-1"></i>{{ __('payments.gateway.charge_now') }}
                </button>
                <p class="text-muted mt-2 mb-0" style="font-size:.75rem;">{{ __('payments.gateway.inline_hint') }}</p>
            </form>
        @elseif($pending->isEmpty())
            <p class="text-muted small mb-0">{{ __('payments.gateway.inline_unavailable') }}</p>
        @endif
    </div>
</div>
