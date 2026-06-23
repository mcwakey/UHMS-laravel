@extends('layouts.app')
@section('title', __('billing.dashboard'))

@php
    $money = fn ($value) => '&#8373;'.number_format((float) ($value ?? 0), 2);
@endphp

@section('content')
<x-page-header :title="__('billing.dashboard')" icon="ti-chart-bar">
    <x-slot:actions>
        <a href="{{ route('admin.billing.counter-sale.create') }}" class="btn btn-outline-primary"><i class="ti ti-cash-register me-1"></i>{{ __('billing.counter_sale') }}</a>
        <a href="{{ route('admin.billing.payments.receive') }}" class="btn btn-primary"><i class="ti ti-cash me-1"></i>{{ __('billing.receive_payment') }}</a>
        <a href="{{ route('admin.billing.reports.aging') }}" class="btn btn-outline-secondary"><i class="ti ti-clock-dollar me-1"></i>{{ __('billing.ar_aging') }}</a>
    </x-slot:actions>
</x-page-header>

<div class="row g-3 mb-4">
    @foreach([
        ['icon' => 'ti-currency-dollar', 'color' => 'success', 'label' => __('billing.collected_today'), 'value' => $metrics['collected_today'] ?? 0],
        ['icon' => 'ti-calendar-stats', 'color' => 'primary', 'label' => __('billing.collected_month'), 'value' => $metrics['collected_month'] ?? 0],
        ['icon' => 'ti-file-invoice', 'color' => 'info', 'label' => __('billing.billed_month'), 'value' => $metrics['billed_month'] ?? 0],
        ['icon' => 'ti-alert-triangle', 'color' => 'danger', 'label' => __('billing.outstanding'), 'value' => $metrics['outstanding'] ?? 0],
    ] as $card)
    <div class="col-md-3 col-sm-6">
        <div class="card uhms-stat-card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-lg bg-soft-{{ $card['color'] }} rounded me-3">
                        <i class="ti {{ $card['icon'] }} fs-4 text-{{ $card['color'] }}"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0">{!! $money($card['value']) !!}</h3>
                        <p class="text-muted mb-0">{{ $card['label'] }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">{{ __('billing.recent_payments') }}</h6></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light"><tr><th>{{ __('billing.payment_number') }}</th><th>{{ __('common.patient') }}</th><th>{{ __('billing.method') }}</th><th class="text-end">{{ __('billing.amount') }}</th><th>{{ __('common.date') }}</th></tr></thead>
                        <tbody>
                            @forelse($metrics['recent_payments'] ?? [] as $payment)
                                <tr>
                                    <td class="fw-medium">{{ $payment['payment_number'] ?? '' }}</td>
                                    <td>{{ $payment['patient_name'] ?? '' }}</td>
                                    <td>{{ $payment['method'] ?? '' }}</td>
                                    <td class="text-end">{!! $money($payment['amount'] ?? 0) !!}</td>
                                    <td>{{ $payment['paid_at'] ?? '' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">{{ __('billing.no_payments_yet') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">{{ __('billing.top_outstanding_patients') }}</h6></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light"><tr><th>{{ __('common.patient') }}</th><th class="text-center">{{ __('billing.invoices_count') }}</th><th class="text-end">{{ __('billing.balance') }}</th></tr></thead>
                        <tbody>
                            @forelse($metrics['top_debtors'] ?? [] as $debtor)
                                <tr>
                                    <td><div class="fw-medium">{{ $debtor['patient_name'] ?? '' }}</div><small class="text-muted">{{ $debtor['patient_number'] ?? '' }}</small></td>
                                    <td class="text-center">{{ $debtor['invoices'] ?? 0 }}</td>
                                    <td class="text-end text-danger fw-bold">{!! $money($debtor['balance'] ?? 0) !!}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-4">{{ __('billing.no_outstanding_balances') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
