@extends('layouts.app')
@section('title', __('accounting.financial_reconciliation'))

@section('content')
<x-page-header :title="__('accounting.financial_reconciliation')" icon="ti-scale">
    <x-slot:actions>
        <form method="GET" action="{{ route('admin.accounts.reconciliation') }}" class="d-flex gap-2">
            <input type="date" name="from" class="form-control" value="{{ $from }}" placeholder="{{ __('accounting.from') }}">
            <input type="date" name="to" class="form-control" value="{{ $to }}" placeholder="{{ __('accounting.to') }}">
            <button type="submit" class="btn btn-primary text-nowrap"><i class="ti ti-filter me-1"></i>{{ __('accounting.filter') }}</button>
        </form>
    </x-slot:actions>
</x-page-header>

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
                        <small class="text-muted">{{ __('accounting.total_income') }}</small>
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
                        <small class="text-muted">{{ __('accounting.total_expenses') }}</small>
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
                        <small class="text-muted">{{ __('accounting.patient_revenue') }}</small>
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
                        <small class="text-muted">{{ __('accounting.net_position') }}</small>
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
                <h5 class="card-title mb-0"><i class="ti ti-category me-1 text-success"></i>{{ __('accounting.income_by_category') }}</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('accounting.category') }}</th>
                                <th class="text-center">{{ __('accounting.entries') }}</th>
                                <th class="text-end">{{ __('accounting.total') }}</th>
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
                                <td colspan="3"><x-empty-state :message="__('accounting.no_income_records')" /></td>
                            </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light">
                            <tr class="fw-bold">
                                <td colspan="2" class="text-end">{{ __('accounting.total') }}:</td>
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
                <h5 class="card-title mb-0"><i class="ti ti-category me-1 text-danger"></i>{{ __('accounting.expense_by_category') }}</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('accounting.category') }}</th>
                                <th class="text-center">{{ __('accounting.entries') }}</th>
                                <th class="text-end">{{ __('accounting.total') }}</th>
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
                                <td colspan="3"><x-empty-state :message="__('accounting.no_expense_records')" /></td>
                            </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light">
                            <tr class="fw-bold">
                                <td colspan="2" class="text-end">{{ __('accounting.total') }}:</td>
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
        <h5 class="card-title mb-0"><i class="ti ti-wallet me-1"></i>{{ __('accounting.patient_revenue_by_method') }}</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('accounting.payment_method') }}</th>
                        <th class="text-center">{{ __('accounting.transactions') }}</th>
                        <th class="text-end">{{ __('accounting.amount') }}</th>
                        <th class="text-end">{{ __('accounting.percent_of_total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @php $revTotal = collect($data['revenue_by_method'])->sum('total'); @endphp
                    @forelse($data['revenue_by_method'] as $row)
                    <tr>
                        <td class="fw-medium">{{ $row->payment_method instanceof \App\Enums\PaymentMethod ? $row->payment_method->translatedLabel() : (\App\Enums\PaymentMethod::tryFrom((string) $row->payment_method)?->translatedLabel() ?? $row->payment_method) }}</td>
                        <td class="text-center">{{ $row->count }}</td>
                        <td class="text-end">GH₵ {{ number_format($row->total, 2) }}</td>
                        <td class="text-end">{{ $revTotal > 0 ? number_format(($row->total / $revTotal) * 100, 1) : 0 }}%</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4"><x-empty-state :message="__('accounting.no_revenue_records')" /></td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light">
                    <tr class="fw-bold">
                        <td colspan="2" class="text-end">{{ __('accounting.total_revenue') }}:</td>
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
        <h5 class="card-title mb-0"><i class="ti ti-chart-line me-1"></i>{{ __('accounting.daily_trend') }}</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('accounting.date') }}</th>
                        <th class="text-end text-success">{{ __('accounting.income') }}</th>
                        <th class="text-end text-danger">{{ __('accounting.expense') }}</th>
                        <th class="text-end text-info">{{ __('accounting.revenue') }}</th>
                        <th class="text-end">{{ __('accounting.net') }}</th>
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
