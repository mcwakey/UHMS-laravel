@extends('layouts.app')
@section('title', 'Invoice ' . $invoice->invoice_number)

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3">
    <div class="flex-grow-1">
        <h6 class="fw-bold mb-0 d-flex align-items-center">
            <a href="{{ route('admin.billing.invoices.index') }}"><i class="ti ti-chevron-left me-1 fs-14"></i>Invoices</a>
        </h6>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.billing.invoices.print', $invoice) }}" target="_blank" class="btn btn-dark btn-md">
            <i class="ti ti-printer me-1"></i>Print
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div id="paymentFormFeedback" class="alert d-none" role="alert"></div>

<div class="row">
    <!-- Invoice Details -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <!-- Header -->
                <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3">
                    <div>
                        <img src="{{ URL::asset('build/img/logo.svg') }}" alt="UHMS" style="height:40px;">
                    </div>
                    <div class="text-end">
                        <span class="badge bg-{{ $invoice->status->color() }} fs-13 px-3 py-2">{{ $invoice->status->label() }}</span>
                    </div>
                </div>

                <!-- Invoice Info Row -->
                <div class="row mb-4 pb-3 border-bottom">
                    <div class="col-md-4">
                        <h6 class="fw-bold mb-2">Invoice Details</h6>
                        <p class="mb-1 text-muted">Invoice #: <span class="text-dark fw-medium">{{ $invoice->invoice_number }}</span></p>
                        <p class="mb-1 text-muted">Date: <span class="text-dark">{{ $invoice->created_at->format('d M Y') }}</span></p>
                        <p class="mb-1 text-muted">Due Date: <span class="text-dark">{{ $invoice->due_date?->format('d M Y') ?? '—' }}</span></p>
                        <p class="mb-0 text-muted">Type: <span class="badge bg-soft-{{ $invoice->billing_type->color() }}">{{ $invoice->billing_type->label() }}</span></p>
                    </div>
                    <div class="col-md-4">
                        <h6 class="fw-bold mb-2">Patient</h6>
                        <p class="fw-medium mb-1">{{ $invoice->patient->full_name }}</p>
                        <p class="text-muted mb-1">{{ $invoice->patient->patient_number }}</p>
                        <p class="text-muted mb-1">{{ $invoice->patient->phone }}</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <h6 class="fw-bold mb-2">Visit</h6>
                        <p class="text-muted mb-1">{{ $invoice->visit->visit_number }}</p>
                        <p class="text-muted mb-1">{{ $invoice->visit->status->label() }}</p>
                        <p class="text-muted mb-0">{{ $invoice->visit->visit_date->format('d M Y') }}</p>
                    </div>
                </div>

                <!-- Items Table -->
                <h6 class="fw-bold mb-3">Service Items</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Description</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">Total</th>
                                <th class="text-center">NHIS</th>
                                <th class="text-end">NHIS Amt</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->items as $idx => $item)
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td>
                                    {{ $item->description }}
                                    @if($item->serviceCatalog)
                                    <br><small class="text-muted">{{ $item->serviceCatalog->code }}</small>
                                    @endif
                                </td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-end">&#8373;{{ number_format($item->unit_price, 2) }}</td>
                                <td class="text-end">&#8373;{{ number_format($item->total_price, 2) }}</td>
                                <td class="text-center">
                                    @if($item->is_nhis_covered)
                                    <span class="badge bg-success">Yes</span>
                                    @else
                                    <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($item->nhis_approved_amount > 0)
                                    &#8373;{{ number_format($item->nhis_approved_amount, 2) }}
                                    @else
                                    —
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Totals -->
                <div class="row">
                    <div class="col-md-6">
                        @if($invoice->notes)
                        <div>
                            <h6 class="fw-bold mb-1">Notes</h6>
                            <p class="text-muted">{{ $invoice->notes }}</p>
                        </div>
                        @endif
                        <p class="text-muted mb-1">Created by: <span class="text-dark">{{ $invoice->createdBy->name ?? '—' }}</span></p>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal</span>
                            <span class="fw-medium">&#8373;{{ number_format($invoice->subtotal, 2) }}</span>
                        </div>
                        @if($invoice->tax_amount > 0)
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Tax</span>
                            <span>&#8373;{{ number_format($invoice->tax_amount, 2) }}</span>
                        </div>
                        @endif
                        @if($invoice->discount_amount > 0)
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Discount</span>
                            <span class="text-danger">-&#8373;{{ number_format($invoice->discount_amount, 2) }}</span>
                        </div>
                        @endif
                        @if($invoice->nhis_amount > 0)
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">NHIS Covered</span>
                            <span class="text-primary">&#8373;{{ number_format($invoice->nhis_amount, 2) }}</span>
                        </div>
                        @endif
                        <div class="d-flex justify-content-between mb-2 border-top pt-2">
                            <span class="fw-bold fs-5">Total</span>
                            <span class="fw-bold fs-5">&#8373;{{ number_format($invoice->total_amount, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-success fw-medium">Paid</span>
                            <span class="text-success fw-medium">&#8373;{{ number_format($invoice->amount_paid, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold text-danger">Balance</span>
                            <span class="fw-bold text-danger fs-5">&#8373;{{ number_format($invoice->balance, 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Payment History -->
                @if($invoice->payments->count() > 0)
                <hr>
                <h6 class="fw-bold mb-3"><i class="ti ti-cash me-1"></i>Payment History</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Payment #</th>
                                <th>Date</th>
                                <th>Method</th>
                                <th>Reference</th>
                                <th class="text-end">Amount</th>
                                <th>Received By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->payments as $payment)
                            <tr>
                                <td class="fw-medium">{{ $payment->payment_number }}</td>
                                <td>{{ $payment->paid_at->format('d M Y H:i') }}</td>
                                <td>{{ $payment->payment_method->label() }}</td>
                                <td>{{ $payment->reference_number ?? '—' }}</td>
                                <td class="text-end fw-medium text-success">&#8373;{{ number_format($payment->amount, 2) }}</td>
                                <td>{{ $payment->receivedBy->name ?? '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Right Sidebar: Record Payment -->
    <div class="col-lg-4">
        @if(!in_array($invoice->status, [\App\Enums\InvoiceStatus::PAID, \App\Enums\InvoiceStatus::CANCELLED, \App\Enums\InvoiceStatus::REFUNDED]))
        <div class="card border-primary">
            <div class="card-header bg-primary text-white">
                <h6 class="fw-bold mb-0"><i class="ti ti-cash me-1"></i>Record Payment</h6>
            </div>
            <div class="card-body">
                <div class="alert alert-warning py-2 mb-3">
                    <small><strong>Outstanding:</strong> &#8373;{{ number_format($invoice->balance, 2) }}</small>
                </div>

                <form method="POST" action="{{ route('admin.billing.payments.store', $invoice) }}" id="paymentForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-medium">Amount (&#8373;) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control @error('amount') is-invalid @enderror"
                            value="{{ old('amount', $invoice->balance) }}" step="0.01" min="0.01" max="{{ $invoice->balance }}" required>
                        @error('amount')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method" class="form-select @error('payment_method') is-invalid @enderror" required id="paymentMethodSelect">
                            @foreach(\App\Enums\PaymentMethod::cases() as $method)
                            <option value="{{ $method->value }}">{{ $method->label() }}</option>
                            @endforeach
                        </select>
                        @error('payment_method')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3" id="referenceGroup" style="display:none;">
                        <label class="form-label fw-medium">Reference / Transaction ID</label>
                        <input type="text" name="reference_number" class="form-control" placeholder="e.g. MoMo Transaction ID">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Optional"></textarea>
                    </div>

                    @can('payments.create')
                    <button type="submit" class="btn btn-primary w-100" id="recordPaymentBtn">
                        <i class="ti ti-check me-1"></i>Record Payment
                    </button>
                    @endcan
                </form>
            </div>
        </div>
        @else
        <div class="card">
            <div class="card-body text-center py-4">
                @if($invoice->status === \App\Enums\InvoiceStatus::PAID)
                <i class="ti ti-circle-check text-success fs-1 d-block mb-2"></i>
                <h5 class="text-success">Fully Paid</h5>
                @elseif($invoice->status === \App\Enums\InvoiceStatus::CANCELLED)
                <i class="ti ti-circle-x text-danger fs-1 d-block mb-2"></i>
                <h5 class="text-danger">Cancelled</h5>
                @endif
            </div>
        </div>
        @endif

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h6 class="fw-bold mb-0">Quick Actions</h6>
            </div>
            <div class="card-body d-grid gap-2">
                <a href="{{ route('admin.billing.invoices.print', $invoice) }}" target="_blank" class="btn btn-outline-dark">
                    <i class="ti ti-printer me-1"></i>Print Invoice
                </a>
                @if(!in_array($invoice->status, [\App\Enums\InvoiceStatus::PAID, \App\Enums\InvoiceStatus::CANCELLED]))
                @can('invoices.edit')
                <form method="POST" action="{{ route('admin.billing.invoices.cancel', $invoice) }}" onsubmit="return confirm('Cancel this invoice?')">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-outline-danger w-100">
                        <i class="ti ti-x me-1"></i>Cancel Invoice
                    </button>
                </form>
                @endcan
                @endif
                <a href="{{ route('admin.visits.show', $invoice->visit) }}" class="btn btn-outline-primary">
                    <i class="ti ti-calendar-check me-1"></i>View Visit
                </a>
                <a href="{{ route('admin.patients.show', $invoice->patient) }}" class="btn btn-outline-info">
                    <i class="ti ti-user me-1"></i>View Patient
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function() {
    const feedback = $('#paymentFormFeedback');
    const paymentForm = $('#paymentForm');
    const recordPaymentBtn = $('#recordPaymentBtn');
    const paymentMethodSelect = $('#paymentMethodSelect');
    const referenceGroup = $('#referenceGroup');
    const originalButtonHtml = recordPaymentBtn.html();

    function showFeedback(type, html) {
        feedback.removeClass('d-none alert-success alert-danger').addClass('alert-' + type).html(html);
        $('html, body').animate({ scrollTop: 0 }, 200);
    }

    function clearValidationErrors() {
        paymentForm.find('.is-invalid').removeClass('is-invalid');
        paymentForm.find('.dynamic-invalid-feedback').remove();
    }

    function applyValidationErrors(errors) {
        Object.entries(errors).forEach(function(entry) {
            const field = entry[0];
            const message = Array.isArray(entry[1]) ? entry[1][0] : entry[1];
            const input = paymentForm.find('[name="' + field + '"]');

            if (input.length === 0) {
                return;
            }

            input.addClass('is-invalid');
            $('<div class="invalid-feedback d-block dynamic-invalid-feedback"></div>')
                .text(message)
                .insertAfter(input);
        });

        if (feedback.hasClass('d-none')) {
            showFeedback('danger', 'Please correct the highlighted payment fields and try again.');
        }
    }

    paymentMethodSelect.on('change', function() {
        let method = $(this).val();
        let needsRef = ['mtn_momo', 'vodafone_cash', 'airteltigo_money', 'bank_transfer', 'card', 'cheque'].includes(method);
        referenceGroup.toggle(needsRef);
    }).trigger('change');

    paymentForm.on('submit', async function(event) {
        event.preventDefault();

        if (!window.confirm('Record this payment?')) {
            return;
        }

        clearValidationErrors();
        recordPaymentBtn.prop('disabled', true).html('<i class="ti ti-loader me-1"></i>Recording...');

        try {
            const response = await fetch(paymentForm.attr('action'), {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new FormData(paymentForm[0])
            });

            const payload = (response.headers.get('content-type') || '').includes('application/json')
                ? await response.json()
                : {};

            if (response.status === 422 && payload.errors) {
                applyValidationErrors(payload.errors);
                return;
            }

            if (!response.ok) {
                showFeedback('danger', payload.message || 'Unable to record payment right now.');
                return;
            }

            showFeedback(
                'success',
                '<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">'
                    + '<div><strong>' + (payload.message || 'Payment recorded successfully.') + '</strong><div class="small text-muted">Invoice: ' + (payload.invoice_status_label || '') + (payload.visit_status_label ? ' | Visit: ' + payload.visit_status_label : '') + '</div></div>'
                    + '<div class="d-flex gap-2"><a href="' + (payload.receipt_url || '#') + '" class="btn btn-sm btn-success">Receipt</a><a href="' + (payload.redirect_url || '#') + '" class="btn btn-sm btn-outline-success">Refresh Invoice</a></div>'
                    + '</div>'
            );

            window.setTimeout(function () {
                if (payload.redirect_url) {
                    window.location.assign(payload.redirect_url);
                }
            }, 1000);
        } catch (error) {
            showFeedback('danger', 'Network error while recording payment.');
        } finally {
            recordPaymentBtn.prop('disabled', false).html(originalButtonHtml);
        }
    });
});
</script>
@endsection
