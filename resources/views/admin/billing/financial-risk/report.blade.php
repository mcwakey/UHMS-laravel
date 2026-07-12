@extends('layouts.app')
@section('title', __('patient_financial_risk.report_title'))

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">{{ __('patient_financial_risk.report_title') }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('menu.dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.billing.financial-risk.index') }}">{{ __('patient_financial_risk.worklist_title') }}</a></li>
                <li class="breadcrumb-item active">{{ __('patient_financial_risk.report_title') }}</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row g-3">
    @php
        $cards = [
            ['key' => 'active_watchlist', 'color' => 'info', 'icon' => 'ti-eye'],
            ['key' => 'active_high_risk', 'color' => 'warning', 'icon' => 'ti-alert-triangle'],
            ['key' => 'active_blocked_credit', 'color' => 'danger', 'icon' => 'ti-lock'],
            ['key' => 'due_for_review', 'color' => 'primary', 'icon' => 'ti-calendar-time'],
            ['key' => 'expiring_soon', 'color' => 'secondary', 'icon' => 'ti-hourglass'],
            ['key' => 'cleared_this_month', 'color' => 'success', 'icon' => 'ti-checks'],
            ['key' => 'created_this_month', 'color' => 'dark', 'icon' => 'ti-plus'],
        ];
    @endphp
    @foreach($cards as $card)
        <div class="col-md-3">
            <div class="card border-{{ $card['color'] }} h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="ti {{ $card['icon'] }} fs-2 text-{{ $card['color'] }}"></i>
                    <div>
                        <div class="h4 mb-0 fw-bold">{{ $metrics[$card['key']] }}</div>
                        <div class="text-muted small">{{ __('patient_financial_risk.report.'.$card['key']) }}</div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
