@extends('layouts.app')
@section('title', __('payments.receive_payment'))

@section('content')
@php
    $paymentGatewayAvailable = app(\App\Services\ModuleService::class)->enabled('payment_gateway')
        && app(\App\Services\Integrations\Payment\PaymentProviderResolver::class)->activeProvider() !== null
        && (bool) auth()->user()?->can('integrations.payments.transactions.initiate');
@endphp
<div class="uhms-page-header d-flex align-items-sm-center flex-sm-row flex-column gap-2">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-1"><i class="ti ti-cash me-2"></i>{{ __('payments.receive_payment') }}</h4>
        <p class="text-muted mb-0">{{ __('payments.cash_disabled_msg') }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.billing.payments.index') }}" class="btn btn-outline-secondary">
            <i class="ti ti-list me-1"></i>{{ __('payments.payment_history_link') }}
        </a>
        @can('accounts.cashier')
        <a href="{{ route('admin.accounts.handover.index') }}" class="btn btn-outline-primary">
            <i class="ti ti-cash me-1"></i>{{ __('payments.cashier_handover') }}
        </a>
        @endcan
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-3 col-md-6">
        <div class="card uhms-stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <span class="avatar avatar-lg bg-soft-warning rounded me-3"><i class="ti ti-file-invoice fs-4 text-warning"></i></span>
                    <div>
                        <p class="text-muted mb-1">{{ __('payments.invoices_waiting') }}</p>
                        <h3 class="fw-bold mb-0">{{ number_format($stats['waiting_invoices']) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card uhms-stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <span class="avatar avatar-lg bg-soft-danger rounded me-3"><i class="ti ti-alert-circle fs-4 text-danger"></i></span>
                    <div>
                        <p class="text-muted mb-1">{{ __('payments.outstanding_balance') }}</p>
                        <h3 class="fw-bold mb-0">&#8373;{{ number_format($stats['outstanding_balance'], 2) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card uhms-stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <span class="avatar avatar-lg bg-soft-success rounded me-3"><i class="ti ti-cash fs-4 text-success"></i></span>
                    <div>
                        <p class="text-muted mb-1">{{ __('payments.my_collections_today') }}</p>
                        <h3 class="fw-bold mb-0">&#8373;{{ number_format($stats['cashier_today'], 2) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card uhms-stat-card h-100 {{ $openShift ? 'border-success' : 'border-warning' }}">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <span class="avatar avatar-lg {{ $openShift ? 'bg-soft-success' : 'bg-soft-warning' }} rounded me-3">
                        <i class="ti ti-clock-dollar fs-4 {{ $openShift ? 'text-success' : 'text-warning' }}"></i>
                    </span>
                    <div>
                        <p class="text-muted mb-1">{{ __('payments.cash_shift') }}</p>
                        @if($openShift)
                            <h6 class="fw-bold mb-0 text-success">{{ __('payments.shift_open_since', ['time' => $openShift->started_at?->format('h:i A')]) }}</h6>
                            <small class="text-muted">{{ __('payments.opening_balance') }}: &#8373;{{ number_format($openShift->opening_balance, 2) }}</small>
                        @else
                            <h6 class="fw-bold mb-0 text-warning">{{ __('payments.shift_required') }}</h6>
                            <small class="text-muted">{{ __('payments.non_cash_available') }}</small>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if(! $openShift)
<div class="alert alert-warning d-flex align-items-center gap-2" role="alert">
    <i class="ti ti-alert-triangle fs-5"></i>
    <div>{{ __('payments.cash_disabled_msg') }}</div>
</div>
@endif

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.billing.payments.receive') }}" class="row g-2 align-items-end">
            <div class="col-lg-4 col-md-6">
                <label class="form-label">{{ __('common.search') }}</label>
                <input type="text" name="search" class="form-control" placeholder="{{ __('payments.search_placeholder') }}" value="{{ request('search') }}">
            </div>
            <div class="col-lg-2 col-md-3">
                <label class="form-label">{{ __('common.status') }}</label>
                <select name="status" class="form-select">
                    <option value="">{{ __('payments.all_unpaid') }}</option>
                    @foreach($invoiceStatuses as $status)
                    <option value="{{ $status->value }}" {{ request('status') === $status->value ? 'selected' : '' }}>{{ $status->translatedLabel() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2 col-md-3">
                <label class="form-label">{{ __('invoices.billing_type_label') }}</label>
                <select name="billing_type" class="form-select">
                    <option value="">{{ __('payments.all_types') }}</option>
                    @foreach(\App\Enums\BillingType::cases() as $type)
                    <option value="{{ $type->value }}" {{ request('billing_type') === $type->value ? 'selected' : '' }}>{{ $type->translatedLabel() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-4 col-md-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="ti ti-filter me-1"></i>{{ __('common.filter') }}</button>
                <a href="{{ route('admin.billing.payments.receive') }}" class="btn btn-outline-secondary"><i class="ti ti-x me-1"></i>{{ __('common.clear') }}</a>
            </div>
        </form>
    </div>
</div>

<div class="card uhms-payment-queue">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0"><i class="ti ti-receipt me-1"></i>{{ __('payments.outstanding_invoices') }}</h5>
        <span class="badge bg-soft-primary text-primary">{{ $invoices->total() }} invoice{{ $invoices->total() === 1 ? '' : 's' }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-nowrap mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('payments.invoice_col_hdr') }}</th>
                        <th>{{ __('payments.patient_col') }}</th>
                        <th>{{ __('payments.visit_col') }}</th>
                        <th class="text-end">{{ __('payments.billed_col') }}</th>
                        <th class="text-end">{{ __('payments.paid_col') }}</th>
                        <th class="text-end">{{ __('payments.balance_col') }}</th>
                        <th>{{ __('payments.receive_col') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                    @php
                        $cashierBalance = (float) ($invoice->cashier_balance ?? $invoice->balance);
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('admin.billing.invoices.show', $invoice) }}" class="fw-bold text-primary">{{ $invoice->invoice_number }}</a>
                            <div class="small text-muted">{{ $invoice->created_at->format('d M Y') }}</div>
                            <x-status-badge :status="$invoice->status" />
                            <span class="badge bg-soft-{{ $invoice->billing_type->color() }} text-{{ $invoice->billing_type->color() }}">{{ $invoice->billing_type->translatedLabel() }}</span>
                        </td>
                        <td>
                            <div class="fw-medium">{{ $invoice->patient?->full_name ?? $invoice->external_party_name ?? '—' }}</div>
                            <small class="text-muted">
                                {{ $invoice->patient?->patient_number ?? __('payments.external_referral') }}
                                @if($invoice->patient?->phone)
                                    | <x-patient-protected-field field="phone" :value="$invoice->patient->phone" />
                                @endif
                            </small>
                        </td>
                        <td>
                            <div>{{ $invoice->visit?->visit_number ?? __('payments.direct_invoice') }}</div>
                            <small class="text-muted">{{ $invoice->visit?->department?->name ?? __('payments.no_department') }}</small>
                        </td>
                        <td class="text-end">
                            @php
                                $insuranceShare = (float) $invoice->nhis_amount;
                                $patientShare = (float) $invoice->total_amount;
                                $combinedResponsibility = $insuranceShare + $patientShare;
                            @endphp
                            <div>&#8373;{{ number_format($combinedResponsibility, 2) }}</div>
                            @if($invoice->nhis_amount > 0)
                                <small class="text-success d-block"><i class="ti ti-shield-check me-1"></i>Ins: &#8373;{{ number_format($insuranceShare, 2) }}</small>
                                <small class="text-muted d-block"><i class="ti ti-user me-1"></i>Pt: &#8373;{{ number_format($patientShare, 2) }}</small>
                            @else
                                <small class="text-muted d-block"><i class="ti ti-cash me-1"></i>{{ __('payments.cash_and_carry') }}</small>
                            @endif
                        </td>
                        <td class="text-end text-success">&#8373;{{ number_format($invoice->amount_paid, 2) }}</td>
                        <td class="text-end fw-bold text-danger">&#8373;{{ number_format($cashierBalance, 2) }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.billing.payments.store', $invoice) }}" class="payment-inline-form"
                                @if($paymentGatewayAvailable) data-gateway-charge="{{ route('admin.integrations.payments.invoices.charge', $invoice) }}" @endif>
                                @csrf
                                <input type="hidden" name="return_to" value="receive">
                                <input type="hidden" name="payer_type" value="patient">
                                <input type="hidden" name="payer_id" value="{{ $invoice->patient_id }}">
                                <div class="row g-2 align-items-end">
                                    <div class="col-sm-3">
                                        <label class="form-label small">{{ __('payments.amount_lbl') }}</label>
                                        <input type="number" name="amount" class="form-control form-control-sm" value="{{ number_format($cashierBalance, 2, '.', '') }}" min="0.01" max="{{ $cashierBalance }}" step="0.01" required>
                                    </div>
                                    <div class="col-sm-3">
                                        <label class="form-label small">{{ __('payments.method_lbl') }}</label>
                                        <select name="payment_method" class="form-select form-select-sm js-method" required>
                                            @if(! $openShift)
                                            <option value="" selected disabled>{{ __('payments.select_method') }}</option>
                                            @endif
                                            <option value="cash" {{ ! $openShift ? 'disabled' : '' }}>{{ __('payments.cash') }}{{ ! $openShift ? ' ' . __('payments.shift_closed_suffix') : '' }}</option>
                                            @if($paymentGatewayAvailable)
                                            <option value="mobile_money">{{ __('payments.mobile_money') }}</option>
                                            @endif
                                            <option value="bank_transfer">{{ __('payments.bank_transfer') }}</option>
                                            <option value="card">{{ __('payments.card') }}</option>
                                            <option value="cheque">{{ __('payments.cheque') }}</option>
                                        </select>
                                    </div>
                                    <div class="col-sm-4">
                                        <label class="form-label small">{{ __('payments.reference_lbl') }}</label>
                                        <input type="text" name="reference_number" class="form-control form-control-sm" placeholder="{{ __('common.optional') }}">
                                    </div>
                                    <div class="col-sm-2 d-grid">
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            <i class="ti ti-check me-1"></i>{{ __('payments.receive_btn') }}
                                        </button>
                                    </div>
                                </div>
                                @if($paymentGatewayAvailable)
                                <div class="row g-2 mt-1 js-momo-fields" style="display:none;">
                                    <div class="col-sm-6">
                                        <input type="tel" name="payer_phone" class="form-control form-control-sm js-momo-phone"
                                            value="{{ $invoice->patient?->phone }}" placeholder="{{ __('payments.gateway.payer_phone') }}">
                                    </div>
                                    <div class="col-sm-6">
                                        <select name="mobile_network" class="form-select form-select-sm">
                                            <option value="mtn_momo">{{ __('payments.gateway.method_mtn_momo') }}</option>
                                            <option value="vodafone_cash">{{ __('payments.gateway.method_telecel_cash') }}</option>
                                            <option value="airteltigo_money">{{ __('payments.gateway.method_airteltigo_money') }}</option>
                                        </select>
                                    </div>
                                </div>
                                @endif
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <i class="ti ti-circle-check fs-1 text-success d-block mb-2"></i>
                            <h5 class="mb-1">{{ __('payments.no_unpaid_invoices') }}</h5>
                            <p class="text-muted mb-0">{{ __('payments.all_invoices_settled') }}</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($invoices->hasPages())
    <div class="card-footer">
        {{ $invoices->links() }}
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
// Mobile Money inline on the receive page: reveal phone + network when chosen,
// and post that row to the payment gateway (charge) instead of the manual store.
document.querySelectorAll('.payment-inline-form[data-gateway-charge]').forEach(function (form) {
    var sel = form.querySelector('.js-method');
    var momo = form.querySelector('.js-momo-fields');
    var phone = form.querySelector('.js-momo-phone');
    if (!sel) { return; }
    function sync() {
        var isMomo = sel.value === 'mobile_money';
        if (momo) { momo.style.display = isMomo ? '' : 'none'; }
        if (phone) { phone.required = isMomo; }
    }
    sel.addEventListener('change', sync);
    sync();
    form.addEventListener('submit', function () {
        if (sel.value === 'mobile_money') {
            form.action = form.getAttribute('data-gateway-charge');
        }
    });
});
</script>
@endpush
