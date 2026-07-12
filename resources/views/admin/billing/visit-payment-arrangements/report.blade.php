@extends('layouts.app')
@section('title', __('visit_payment_arrangement.report_title'))

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">{{ __('visit_payment_arrangement.report_title') }}</h4>
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('menu.dashboard') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.billing.visit-payment-arrangements.index') }}">{{ __('visit_payment_arrangement.worklist_title') }}</a></li>
            <li class="breadcrumb-item active">{{ __('visit_payment_arrangement.report_title') }}</li>
        </ol></nav>
    </div>
</div>

<div class="alert alert-warning"><i class="ti ti-alert-triangle me-1"></i>{{ __('visit_payment_arrangement.administrative_notice') }}</div>

<div class="row g-3">
    @php
        $cards = [
            ['key' => 'pending', 'color' => 'warning', 'icon' => 'ti-clock'],
            ['key' => 'approved', 'color' => 'success', 'icon' => 'ti-check'],
            ['key' => 'rejected', 'color' => 'danger', 'icon' => 'ti-x'],
            ['key' => 'revoked', 'color' => 'dark', 'icon' => 'ti-ban'],
            ['key' => 'expired', 'color' => 'secondary', 'icon' => 'ti-hourglass'],
            ['key' => 'pay_before', 'color' => 'info', 'icon' => 'ti-cash'],
            ['key' => 'pay_after', 'color' => 'info', 'icon' => 'ti-cash'],
            ['key' => 'running_bill', 'color' => 'info', 'icon' => 'ti-receipt'],
            ['key' => 'high_risk_deferred', 'color' => 'danger', 'icon' => 'ti-alert-triangle'],
            ['key' => 'overdue_review', 'color' => 'warning', 'icon' => 'ti-clock-exclamation'],
        ];
    @endphp
    @foreach($cards as $card)
        <div class="col-md-3">
            <div class="card border-{{ $card['color'] }} h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="ti {{ $card['icon'] }} fs-3 text-{{ $card['color'] }}"></i>
                    <div>
                        <div class="h4 mb-0 fw-bold">{{ $metrics[$card['key']] }}</div>
                        <div class="text-muted small">{{ __('visit_payment_arrangement.report.'.$card['key']) }}</div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
