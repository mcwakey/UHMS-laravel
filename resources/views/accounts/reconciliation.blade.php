@extends('layouts.app')
@section('title', 'Financial Reconciliation')

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Financial Reconciliation</h4>
    </div>
    <div>
        <form method="GET" action="{{ route('admin.accounts.reconciliation') }}" class="d-flex gap-2">
            <input type="date" name="from" class="form-control" value="{{ $from }}" placeholder="From">
            <input type="date" name="to" class="form-control" value="{{ $to }}" placeholder="To">
            <button type="submit" class="btn btn-primary text-nowrap"><i class="ti ti-filter me-1"></i>Filter</button>
        </form>
    </div>
</div>

<!-- Stats Cards -->
<div class="row mb-3">
    <div class="col-md-3">
        <div class="card border-success">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-md bg-success bg-opacity-10 rounded me-3">
                        <i class="ti ti-trending-up fs-4 text-success"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">GH₵ {{ number_format($stats['period_income'], 2) }}</h4>
                        <small class="text-muted">Total Income</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-danger">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-md bg-danger bg-opacity-10 rounded me-3">
                        <i class="ti ti-trending-down fs-4 text-danger"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">GH₵ {{ number_format($stats['period_expense'], 2) }}</h4>
                        <small class="text-muted">Total Expenses</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-info">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-md bg-info bg-opacity-10 rounded me-3">
                        <i class="ti ti-cash fs-4 text-info"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">GH₵ {{ number_format($stats['period_revenue'], 2) }}</h4>
                        <small class="text-muted">Patient Revenue</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-primary">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-md bg-primary bg-opacity-10 rounded me-3">
                        <i class="ti ti-report-money fs-4 text-primary"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">GH₵ {{ number_format($stats['period_net'], 2) }}</h4>
                        <small class="text-muted">Net Position</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Income by Category -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ti ti-category me-1 text-success"></i>Income by Category</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Category</th>
                                <th class="text-center">Entries</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data['income_by_category'] as $row)
                            <tr>
                                <td class="fw-medium">{{ $row->category_name }}</td>
                                <td class="text-center">{{ $row->count }}</td>
                                <td class="text-end text-success">GH₵ {{ number_format($row->total, 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">No income records</td>
                            </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light">
                            <tr class="fw-bold">
                                <td colspan="2" class="text-end">Total:</td>
                                <td class="text-end text-success">GH₵ {{ number_format($data['totals']['income'], 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Expense by Category -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ti ti-category me-1 text-danger"></i>Expense by Category</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Category</th>
                                <th class="text-center">Entries</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data['expense_by_category'] as $row)
                            <tr>
                                <td class="fw-medium">{{ $row->category_name }}</td>
                                <td class="text-center">{{ $row->count }}</td>
                                <td class="text-end text-danger">GH₵ {{ number_format($row->total, 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">No expense records</td>
                            </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light">
                            <tr class="fw-bold">
                                <td colspan="2" class="text-end">Total:</td>
                                <td class="text-end text-danger">GH₵ {{ number_format($data['totals']['expense'], 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Revenue by Payment Method -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="ti ti-wallet me-1"></i>Patient Revenue by Payment Method</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Payment Method</th>
                        <th class="text-center">Transactions</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">% of Total</th>
                    </tr>
                </thead>
                <tbody>
                    @php $revTotal = collect($data['revenue_by_method'])->sum('total'); @endphp
                    @forelse($data['revenue_by_method'] as $row)
                    <tr>
                        <td class="fw-medium">{{ \App\Enums\PaymentMethod::tryFrom($row->payment_method)?->label() ?? $row->payment_method }}</td>
                        <td class="text-center">{{ $row->count }}</td>
                        <td class="text-end">GH₵ {{ number_format($row->total, 2) }}</td>
                        <td class="text-end">{{ $revTotal > 0 ? number_format(($row->total / $revTotal) * 100, 1) : 0 }}%</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-3">No revenue records</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light">
                    <tr class="fw-bold">
                        <td colspan="2" class="text-end">Total Revenue:</td>
                        <td class="text-end">GH₵ {{ number_format($data['totals']['revenue'], 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Daily Trend -->
@if(count($data['daily_trend']) > 0)
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="ti ti-chart-line me-1"></i>Daily Trend</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th class="text-end text-success">Income</th>
                        <th class="text-end text-danger">Expense</th>
                        <th class="text-end text-info">Revenue</th>
                        <th class="text-end">Net</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['daily_trend'] as $day)
                    @php $net = ($day->income ?? 0) + ($day->revenue ?? 0) - ($day->expense ?? 0); @endphp
                    <tr>
                        <td class="fw-medium">{{ \Carbon\Carbon::parse($day->date)->format('d M Y') }}</td>
                        <td class="text-end text-success">GH₵ {{ number_format($day->income ?? 0, 2) }}</td>
                        <td class="text-end text-danger">GH₵ {{ number_format($day->expense ?? 0, 2) }}</td>
                        <td class="text-end text-info">GH₵ {{ number_format($day->revenue ?? 0, 2) }}</td>
                        <td class="text-end {{ $net >= 0 ? 'text-success' : 'text-danger' }}">
                            GH₵ {{ number_format($net, 2) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection
