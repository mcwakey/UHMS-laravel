@extends('layouts.app')
@section('title', 'Daily Collection')

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Daily Collection Report</h4>
    </div>
    <div>
        <form method="GET" action="{{ route('admin.accounts.daily-collection') }}" class="d-flex gap-2">
            <input type="date" name="date" class="form-control" value="{{ $date }}" onchange="this.form.submit()">
            @can('invoices.create')
            <a href="{{ route('admin.billing.invoices.create') }}" class="btn btn-primary text-nowrap">
                <i class="ti ti-file-invoice me-1"></i>Create Invoice
            </a>
            @endcan
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-3">
    <div class="col-md-4">
        <div class="card border-success">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-md bg-success bg-opacity-10 rounded me-3">
                        <i class="ti ti-cash fs-4 text-success"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">GH₵ {{ number_format($collection['payments_total'], 2) }}</h4>
                        <small class="text-muted">Patient Payments</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-info">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-md bg-info bg-opacity-10 rounded me-3">
                        <i class="ti ti-trending-up fs-4 text-info"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">GH₵ {{ number_format($collection['income_total'], 2) }}</h4>
                        <small class="text-muted">Other Income</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-primary">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-md bg-primary bg-opacity-10 rounded me-3">
                        <i class="ti ti-coins fs-4 text-primary"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">GH₵ {{ number_format($collection['grand_total'], 2) }}</h4>
                        <small class="text-muted">Grand Total Collection</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Patient Payments by Method -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ti ti-cash me-1"></i>Patient Payments by Method</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Payment Method</th>
                                <th class="text-center">Count</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($collection['payments'] as $pm)
                            <tr>
                                <td class="fw-medium">{{ $pm->payment_method instanceof \App\Enums\PaymentMethod ? $pm->payment_method->label() : (\App\Enums\PaymentMethod::tryFrom((string) $pm->payment_method)?->label() ?? $pm->payment_method) }}</td>
                                <td class="text-center">{{ $pm->count }}</td>
                                <td class="text-end">GH₵ {{ number_format($pm->total, 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3"><x-empty-state message="No payments recorded" /></td>
                            </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light">
                            <tr class="fw-bold">
                                <td colspan="2" class="text-end">Total:</td>
                                <td class="text-end">GH₵ {{ number_format($collection['payments_total'], 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Other Income -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ti ti-trending-up me-1"></i>Other Income</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Entry #</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($collection['income_entries'] as $entry)
                            <tr>
                                <td>{{ $entry->entry_number }}</td>
                                <td><span class="badge bg-light text-dark">{{ $entry->category->name }}</span></td>
                                <td>{{ Str::limit($entry->description, 30) }}</td>
                                <td class="text-end">GH₵ {{ number_format($entry->amount, 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4"><x-empty-state message="No income entries" /></td>
                            </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light">
                            <tr class="fw-bold">
                                <td colspan="3" class="text-end">Total:</td>
                                <td class="text-end">GH₵ {{ number_format($collection['income_total'], 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Expenses -->
@if($collection['expense_entries']->count() > 0)
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="ti ti-trending-down me-1 text-danger"></i>Expenses</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Entry #</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Method</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($collection['expense_entries'] as $entry)
                    <tr>
                        <td>{{ $entry->entry_number }}</td>
                        <td><span class="badge bg-light text-dark">{{ $entry->category->name }}</span></td>
                        <td>{{ Str::limit($entry->description, 40) }}</td>
                        <td>{{ $entry->payment_method?->label() ?? '-' }}</td>
                        <td class="text-end text-danger">GH₵ {{ number_format($entry->amount, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light">
                    <tr class="fw-bold">
                        <td colspan="4" class="text-end">Total Expenses:</td>
                        <td class="text-end text-danger">GH₵ {{ number_format($collection['expense_total'], 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endif
@endsection
