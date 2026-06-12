@extends('layouts.app')
@section('title', __('payments.title'))

@section('content')
<!-- Page Header -->
<div class="uhms-page-header d-flex align-items-sm-center flex-sm-row flex-column gap-2">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0"><i class="ti ti-cash me-2"></i>{{ __('payments.title') }}</h4>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-4 col-sm-6">
        <div class="card uhms-stat-card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-lg bg-soft-success rounded me-3">
                        <i class="ti ti-cash fs-4 text-success"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0">&#8373;{{ number_format($totalToday, 2) }}</h3>
                        <p class="text-muted mb-0">{{ __('payments.todays_collections') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-6">
        <div class="card uhms-stat-card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-lg bg-soft-primary rounded me-3">
                        <i class="ti ti-calendar-stats fs-4 text-primary"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0">&#8373;{{ number_format($totalMonth, 2) }}</h3>
                        <p class="text-muted mb-0">{{ __('payments.this_month') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-6">
        <div class="card uhms-stat-card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-lg bg-soft-info rounded me-3">
                        <i class="ti ti-receipt fs-4 text-info"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0">{{ $payments->total() }}</h3>
                        <p class="text-muted mb-0">{{ __('payments.total_payments') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.billing.payments.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">{{ __('common.search') }}</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="{{ __('payments.search_placeholder') }}" value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small">{{ __('payments.method_col') }}</label>
                <select name="payment_method" class="form-select form-select-sm">
                    <option value="">{{ __('payments.all_methods') }}</option>
                    @foreach($paymentMethods as $method)
                    <option value="{{ $method->value }}" {{ request('payment_method') === $method->value ? 'selected' : '' }}>
                        {{ $method->label() }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">{{ __('common.from') }}</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small">{{ __('common.to') }}</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-3 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary"><i class="ti ti-filter me-1"></i>{{ __('common.filter') }}</button>
                <a aria-label="{{ __('common.close') }}" title="{{ __('common.close') }}" href="{{ route('admin.billing.payments.index') }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-x"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Payments Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-nowrap mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('payments.payment_number_short') }}</th>
                        <th>{{ __('payments.patient_col') }}</th>
                        <th>{{ __('payments.invoice_col') }}</th>
                        <th>{{ __('payments.method_col') }}</th>
                        <th>{{ __('payments.reference_col') }}</th>
                        <th class="text-end">{{ __('payments.amount_col') }}</th>
                        <th>{{ __('payments.received_by_col') }}</th>
                        <th>{{ __('payments.date_col') }}</th>
                        <th class="text-center">{{ __('payments.actions_col') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                    <tr>
                        <td class="fw-medium">
                            {{ $payment->payment_number }}
                            @if($payment->is_reversal)
                                <span class="badge bg-danger ms-1">{{ __('payments.reversal_label') }}</span>
                            @elseif($payment->status === \App\Enums\PaymentStatus::REVERSED)
                                <span class="badge bg-secondary ms-1">{{ __('payments.reversed_label') }}</span>
                            @endif
                        </td>
                        <td>
                            <div class="fw-medium">{{ $payment->patient?->full_name ?? $payment->invoice?->external_party_name ?? '—' }}</div>
                            <small class="text-muted">{{ $payment->patient?->patient_number ?? __('payments.external_referral') }}</small>
                        </td>
                        <td>
                            <a href="{{ route('admin.billing.invoices.show', $payment->invoice) }}" class="text-primary">
                                {{ $payment->invoice->invoice_number }}
                            </a>
                        </td>
                        <td>
                            <span class="badge bg-soft-primary">{{ $payment->payment_method->label() }}</span>
                        </td>
                        <td>{{ $payment->reference_number ?? '—' }}</td>
                        <td class="text-end fw-bold {{ $payment->is_reversal ? 'text-danger' : 'text-success' }}">&#8373;{{ number_format($payment->amount, 2) }}</td>
                        <td>{{ $payment->receivedBy->name ?? '—' }}</td>
                        <td>{{ $payment->paid_at->format('d M Y H:i') }}</td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button aria-label="{{ __('common.actions') }}" title="{{ __('common.actions') }}" type="button" class="btn btn-sm btn-white border dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('admin.billing.payments.receipt', $payment) }}" target="_blank">
                                            <i class="ti ti-eye me-1"></i>{{ __('payments.view_receipt') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('admin.billing.payments.receipt-pdf', $payment) }}">
                                            <i class="ti ti-file-type-pdf me-1"></i>{{ __('payments.download_pdf') }}
                                        </a>
                                    </li>
                                    @can('payments.refund')
                                    @if($payment->can_reverse)
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <button type="button" class="dropdown-item text-danger"
                                                data-bs-toggle="modal" data-bs-target="#reversePaymentModal"
                                                data-payment-url="{{ route('admin.billing.payments.reverse', $payment) }}"
                                                data-payment-number="{{ $payment->payment_number }}">
                                            <i class="ti ti-arrow-back-up me-1"></i>{{ __('payments.reverse_payment') }}
                                        </button>
                                    </li>
                                    @endif
                                    @endcan
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-4">
                            <div class="text-muted">
                                <i class="ti ti-cash fs-1 d-block mb-2"></i>
                                {{ __('payments.no_payments_found') }}
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($payments->hasPages())
    <div class="card-footer">
        {{ $payments->links() }}
    </div>
    @endif
</div>

@can('payments.refund')
<!-- Reverse Payment Modal -->
<div class="modal fade" id="reversePaymentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="reversePaymentForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('payments.reverse_modal_title') }} <span id="reversePaymentNumber"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">
                        {{ __('payments.reverse_modal_warning') }}
                    </p>
                    <label class="form-label small">{{ __('payments.reversal_reason') }} <span class="text-danger">*</span></label>
                    <textarea name="reason" class="form-control" rows="3" required maxlength="500"
                              placeholder="{{ __('payments.reversal_reason') }}"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="ti ti-arrow-back-up me-1"></i>{{ __('payments.reverse_payment') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endcan
@endsection

@can('payments.refund')
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('reversePaymentModal');
        if (!modal) return;
        modal.addEventListener('show.bs.modal', function (event) {
            const trigger = event.relatedTarget;
            if (!trigger) return;
            document.getElementById('reversePaymentForm').setAttribute('action', trigger.getAttribute('data-payment-url'));
            document.getElementById('reversePaymentNumber').textContent = trigger.getAttribute('data-payment-number') || '';
        });
    });
</script>
@endpush
@endcan
