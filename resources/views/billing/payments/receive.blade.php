@extends('layouts.app')
@section('title', 'Receive Payments')

@section('content')
<div class="uhms-page-header d-flex align-items-sm-center flex-sm-row flex-column gap-2">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-1"><i class="ti ti-cash me-2"></i>Receive Payments</h4>
        <p class="text-muted mb-0">Collect outstanding invoice balances and keep billing revenue aligned with accounting reports.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.billing.payments.index') }}" class="btn btn-outline-secondary">
            <i class="ti ti-list me-1"></i>Payment History
        </a>
        @can('accounts.cashier')
        <a href="{{ route('admin.accounts.handover.index') }}" class="btn btn-outline-primary">
            <i class="ti ti-cash me-1"></i>Cashier Handover
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
                        <p class="text-muted mb-1">Invoices Waiting</p>
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
                        <p class="text-muted mb-1">Outstanding Balance</p>
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
                        <p class="text-muted mb-1">My Collections Today</p>
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
                        <p class="text-muted mb-1">Cash Shift</p>
                        @if($openShift)
                            <h6 class="fw-bold mb-0 text-success">Open since {{ $openShift->started_at?->format('h:i A') }}</h6>
                            <small class="text-muted">Opening: &#8373;{{ number_format($openShift->opening_balance, 2) }}</small>
                        @else
                            <h6 class="fw-bold mb-0 text-warning">Open shift required for cash</h6>
                            <small class="text-muted">Mobile money, card, and bank payments can still be recorded.</small>
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
    <div>Cash is disabled until the cashier opens a shift. This keeps cash collections in sync with handover and reconciliation.</div>
</div>
@endif

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.billing.payments.receive') }}" class="row g-2 align-items-end">
            <div class="col-lg-4 col-md-6">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Invoice, visit, patient, phone..." value="{{ request('search') }}">
            </div>
            <div class="col-lg-2 col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All unpaid</option>
                    @foreach($invoiceStatuses as $status)
                    <option value="{{ $status->value }}" {{ request('status') === $status->value ? 'selected' : '' }}>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2 col-md-3">
                <label class="form-label">Billing Type</label>
                <select name="billing_type" class="form-select">
                    <option value="">All types</option>
                    @foreach(\App\Enums\BillingType::cases() as $type)
                    <option value="{{ $type->value }}" {{ request('billing_type') === $type->value ? 'selected' : '' }}>{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-4 col-md-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="ti ti-filter me-1"></i>Filter</button>
                <a href="{{ route('admin.billing.payments.receive') }}" class="btn btn-outline-secondary"><i class="ti ti-x me-1"></i>Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card uhms-payment-queue">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0"><i class="ti ti-receipt me-1"></i>Outstanding Invoices</h5>
        <span class="badge bg-soft-primary text-primary">{{ $invoices->total() }} invoice{{ $invoices->total() === 1 ? '' : 's' }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-nowrap mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Invoice</th>
                        <th>Patient</th>
                        <th>Visit</th>
                        <th class="text-end">Billed</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Balance</th>
                        <th>Receive</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                    <tr>
                        <td>
                            <a href="{{ route('admin.billing.invoices.show', $invoice) }}" class="fw-bold text-primary">{{ $invoice->invoice_number }}</a>
                            <div class="small text-muted">{{ $invoice->created_at->format('d M Y') }}</div>
                            <span class="badge bg-{{ $invoice->status->color() }}">{{ $invoice->status->label() }}</span>
                            <span class="badge bg-soft-{{ $invoice->billing_type->color() }} text-{{ $invoice->billing_type->color() }}">{{ $invoice->billing_type->label() }}</span>
                        </td>
                        <td>
                            <div class="fw-medium">{{ $invoice->patient->full_name }}</div>
                            <small class="text-muted">{{ $invoice->patient->patient_number }}{{ $invoice->patient->phone ? ' | ' . $invoice->patient->phone : '' }}</small>
                        </td>
                        <td>
                            <div>{{ $invoice->visit?->visit_number ?? 'Direct invoice' }}</div>
                            <small class="text-muted">{{ $invoice->visit?->department?->name ?? 'No department' }}</small>
                        </td>
                        <td class="text-end">
                            <div>&#8373;{{ number_format($invoice->total_amount, 2) }}</div>
                            @if($invoice->nhis_amount > 0)
                                @php $patientShare = max(0, $invoice->total_amount - $invoice->nhis_amount); @endphp
                                <small class="text-success d-block"><i class="ti ti-shield-check me-1"></i>Ins: &#8373;{{ number_format($invoice->nhis_amount, 2) }}</small>
                                <small class="text-muted d-block"><i class="ti ti-user me-1"></i>Pt: &#8373;{{ number_format($patientShare, 2) }}</small>
                            @else
                                <small class="text-muted d-block"><i class="ti ti-cash me-1"></i>Cash & Carry</small>
                            @endif
                        </td>
                        <td class="text-end text-success">&#8373;{{ number_format($invoice->amount_paid, 2) }}</td>
                        <td class="text-end fw-bold text-danger">&#8373;{{ number_format($invoice->balance, 2) }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.billing.payments.store', $invoice) }}" class="payment-inline-form">
                                @csrf
                                <input type="hidden" name="return_to" value="receive">
                                <div class="row g-2 align-items-end">
                                    <div class="col-sm-3">
                                        <label class="form-label small">Amount</label>
                                        <input type="number" name="amount" class="form-control form-control-sm" value="{{ number_format($invoice->balance, 2, '.', '') }}" min="0.01" max="{{ $invoice->balance }}" step="0.01" required>
                                    </div>
                                    <div class="col-sm-3">
                                        <label class="form-label small">Method</label>
                                        <select name="payment_method" class="form-select form-select-sm" required>
                                            @if(! $openShift)
                                            <option value="" selected disabled>Select method</option>
                                            @endif
                                            @foreach($paymentMethods as $method)
                                            <option value="{{ $method->value }}" {{ $method === \App\Enums\PaymentMethod::CASH && ! $openShift ? 'disabled' : '' }}>
                                                {{ $method->label() }}{{ $method === \App\Enums\PaymentMethod::CASH && ! $openShift ? ' (shift closed)' : '' }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-sm-4">
                                        <label class="form-label small">Reference</label>
                                        <input type="text" name="reference_number" class="form-control form-control-sm" placeholder="Optional transaction ID">
                                    </div>
                                    <div class="col-sm-2 d-grid">
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            <i class="ti ti-check me-1"></i>Receive
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <i class="ti ti-circle-check fs-1 text-success d-block mb-2"></i>
                            <h5 class="mb-1">No unpaid invoices found</h5>
                            <p class="text-muted mb-0">All matching invoices are settled.</p>
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