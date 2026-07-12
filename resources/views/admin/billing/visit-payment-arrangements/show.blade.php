@extends('layouts.app')
@section('title', __('visit_payment_arrangement.section_title'))

@section('content')
@php $a = $arrangement; $status = $a->status; @endphp
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">{{ __('visit_payment_arrangement.section_title') }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('menu.dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.billing.visit-payment-arrangements.index') }}">{{ __('visit_payment_arrangement.worklist_title') }}</a></li>
                <li class="breadcrumb-item active">{{ $a->visit?->visit_number }}</li>
            </ol>
        </nav>
    </div>
    <span class="badge bg-{{ $status->color() }} fs-6">{{ $status->label() }}</span>
</div>

@if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
@if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

<div class="alert alert-warning"><i class="ti ti-alert-triangle me-1"></i>{{ __('visit_payment_arrangement.administrative_notice') }}</div>
@if($riskStale)<div class="alert alert-info"><i class="ti ti-refresh-alert me-1"></i>{{ __('visit_payment_arrangement.stale_context') }}</div>@endif

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">{{ __('visit_payment_arrangement.cards.observed_baseline') }}</div><div class="fw-bold">{{ $a->baseline_policy_snapshot?->label() ?? '—' }}</div></div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">{{ __('visit_payment_arrangement.cards.risk_recommendation') }}</div><div class="fw-bold">{{ $a->recommended_policy_snapshot?->label() ?? __('visit_payment_policy.labels.no_recommendation') }}</div></div></div></div>
    <div class="col-md-3"><div class="card h-100 border-info"><div class="card-body"><div class="text-muted small">{{ __('visit_payment_arrangement.cards.approved_arrangement') }}</div><div class="fw-bold">{{ $a->approved_policy?->label() ?? $a->requested_policy->label() }}</div></div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">{{ __('visit_payment_arrangement.cards.operational_legacy_gate') }}</div><div class="small">{{ __('visit_payment_arrangement.cards.legacy_gate_note') }}</div></div></div></div>
</div>

<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h6 class="fw-bold mb-0">{{ __('visit_payment_arrangement.title') }}</h6></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5 text-muted">{{ __('visit_payment_arrangement.fields.requested_policy') }}</dt>
                    <dd class="col-sm-7">{{ $a->requested_policy->label() }}</dd>
                    <dt class="col-sm-5 text-muted">{{ __('visit_payment_arrangement.fields.approved_policy') }}</dt>
                    <dd class="col-sm-7">{{ $a->approved_policy?->label() ?? '—' }}</dd>
                    <dt class="col-sm-5 text-muted">{{ __('visit_payment_arrangement.fields.source') }}</dt>
                    <dd class="col-sm-7">{{ $a->source->label() }}</dd>
                    <dt class="col-sm-5 text-muted">{{ __('visit_payment_arrangement.fields.requires_separate_approver') }}</dt>
                    <dd class="col-sm-7">{{ $a->requires_separate_approver ? __('visit_payment_policy.labels.considered') : '—' }}</dd>
                    <dt class="col-sm-5 text-muted">{{ __('visit_payment_arrangement.fields.request_reason') }}</dt>
                    <dd class="col-sm-7">{{ $a->request_reason ?? '—' }}</dd>
                    <dt class="col-sm-5 text-muted">{{ __('visit_payment_arrangement.fields.supporting_reference') }}</dt>
                    <dd class="col-sm-7">{{ $a->supporting_reference ?? '—' }}</dd>
                    <dt class="col-sm-5 text-muted">{{ __('visit_payment_arrangement.fields.requester') }}</dt>
                    <dd class="col-sm-7">{{ $a->requester?->name ?? '—' }}</dd>
                    <dt class="col-sm-5 text-muted">{{ __('visit_payment_arrangement.fields.approver') }}</dt>
                    <dd class="col-sm-7">{{ $a->approver?->name ?? '—' }}</dd>
                    <dt class="col-sm-5 text-muted">{{ __('visit_payment_arrangement.fields.effective_from') }}</dt>
                    <dd class="col-sm-7">{{ optional($a->effective_from)->format('d M Y') ?? '—' }}</dd>
                    <dt class="col-sm-5 text-muted">{{ __('visit_payment_arrangement.fields.expires_at') }}</dt>
                    <dd class="col-sm-7">{{ optional($a->expires_at)->format('d M Y') ?? '—' }}</dd>
                    <dt class="col-sm-5 text-muted">{{ __('visit_payment_arrangement.fields.risk_snapshot') }}</dt>
                    <dd class="col-sm-7">{{ $a->risk_level_snapshot?->label() ?? '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h6 class="fw-bold mb-0">{{ __('menu.actions') ?? 'Actions' }}</h6></div>
            <div class="card-body d-flex flex-column gap-2">
                @if($status === \App\Enums\VisitPaymentArrangementStatus::PENDING)
                    @can('visits.payment_arrangement.approve')
                        <form method="POST" action="{{ route('admin.billing.visit-payment-arrangements.approve', $a) }}">
                            @csrf
                            <input type="text" name="decision_reason" class="form-control form-control-sm mb-2" maxlength="1000" placeholder="{{ __('visit_payment_arrangement.fields.request_reason') }}">
                            @if($riskStale)
                                <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="confirm_stale_risk" value="1" id="confirmStale" required><label class="form-check-label small" for="confirmStale">{{ __('visit_payment_arrangement.actions.confirm_stale_risk') }}</label></div>
                            @endif
                            <button type="submit" class="btn btn-success btn-sm w-100">{{ __('visit_payment_arrangement.actions.approve') }}</button>
                        </form>
                    @endcan
                    @can('visits.payment_arrangement.reject')
                        @include('admin.billing.visit-payment-arrangements._reason-action', ['route' => route('admin.billing.visit-payment-arrangements.reject', $a), 'label' => __('visit_payment_arrangement.actions.reject'), 'btn' => 'btn-outline-danger'])
                    @endcan
                    @can('visits.payment_arrangement.withdraw')
                        @include('admin.billing.visit-payment-arrangements._reason-action', ['route' => route('admin.billing.visit-payment-arrangements.withdraw', $a), 'label' => __('visit_payment_arrangement.actions.withdraw'), 'btn' => 'btn-outline-secondary'])
                    @endcan
                @elseif($status === \App\Enums\VisitPaymentArrangementStatus::APPROVED)
                    @can('visits.payment_arrangement.revoke')
                        @include('admin.billing.visit-payment-arrangements._reason-action', ['route' => route('admin.billing.visit-payment-arrangements.revoke', $a), 'label' => __('visit_payment_arrangement.actions.revoke'), 'btn' => 'btn-outline-danger'])
                    @endcan
                @else
                    <p class="text-muted small mb-0">{{ $status->label() }}</p>
                @endif
            </div>
        </div>
    </div>
</div>

@can('visits.payment_arrangement.history')
    <div class="card mt-3">
        <div class="card-header"><h6 class="fw-bold mb-0">{{ __('visit_payment_arrangement.history.title') }}</h6></div>
        <div class="card-body">
            @if($history->isEmpty())
                <p class="text-muted mb-0">{{ __('visit_payment_arrangement.history.empty') }}</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead><tr>
                            <th>{{ __('visit_payment_arrangement.history.datetime') }}</th>
                            <th>{{ __('visit_payment_arrangement.history.event') }}</th>
                            <th>{{ __('visit_payment_arrangement.history.new_status') }}</th>
                            <th>{{ __('visit_payment_arrangement.history.approved_policy') }}</th>
                            <th>{{ __('visit_payment_arrangement.history.performed_by') }}</th>
                            <th>{{ __('visit_payment_arrangement.history.reason_code') }}</th>
                        </tr></thead>
                        <tbody>
                            @foreach($history as $entry)
                                @php $new = $entry->new_values ?? []; @endphp
                                <tr>
                                    <td class="text-nowrap">{{ $entry->performed_at?->format('d M Y H:i') }}</td>
                                    <td>{{ $entry->event_type?->label() }}</td>
                                    <td>{{ $new['status'] ?? '—' }}</td>
                                    <td>{{ $new['approved_policy'] ?? '—' }}</td>
                                    <td>{{ $entry->performer?->name ?? __('visit_payment_arrangement.history.system') }}</td>
                                    <td><code class="small">{{ $entry->reason_code ?? '—' }}</code></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endcan
@endsection
