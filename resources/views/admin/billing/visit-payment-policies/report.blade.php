@extends('layouts.app')
@section('title', __('visit_payment_policy.report_title'))

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">{{ __('visit_payment_policy.report_title') }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('menu.dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.billing.visit-payment-policies.index') }}">{{ __('visit_payment_policy.worklist_title') }}</a></li>
                <li class="breadcrumb-item active">{{ __('visit_payment_policy.report_title') }}</li>
            </ol>
        </nav>
    </div>
</div>

<div class="alert alert-info"><i class="ti ti-info-circle me-1"></i>{{ __('visit_payment_policy.observational_notice') }}</div>

<div class="row g-3">
    @php
        $cards = [
            ['key' => 'total', 'color' => 'primary', 'icon' => 'ti-file-text'],
            ['key' => 'finance_review', 'color' => 'warning', 'icon' => 'ti-eye-check'],
            ['key' => 'with_recommendation', 'color' => 'info', 'icon' => 'ti-bulb'],
            ['key' => 'high_risk_snapshot', 'color' => 'danger', 'icon' => 'ti-alert-triangle'],
            ['key' => 'blocked_credit_snapshot', 'color' => 'dark', 'icon' => 'ti-lock'],
            ['key' => 'materialized_this_month', 'color' => 'success', 'icon' => 'ti-calendar'],
        ];
    @endphp
    @foreach($cards as $card)
        <div class="col-md-4">
            <div class="card border-{{ $card['color'] }} h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="ti {{ $card['icon'] }} fs-2 text-{{ $card['color'] }}"></i>
                    <div>
                        <div class="h4 mb-0 fw-bold">{{ $metrics[$card['key']] }}</div>
                        <div class="text-muted small">{{ __('visit_payment_policy.report.'.$card['key']) }}</div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
