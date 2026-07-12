@extends('layouts.app')
@section('title', __('visit_payment_policy.section_title'))

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">{{ __('visit_payment_policy.section_title') }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('menu.dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.billing.visit-payment-policies.index') }}">{{ __('visit_payment_policy.worklist_title') }}</a></li>
                <li class="breadcrumb-item active">{{ $visit->visit_number }}</li>
            </ol>
        </nav>
    </div>
    @can('visits.payment_policy.refresh')
        <form method="POST" action="{{ route('admin.billing.visit-payment-policies.refresh', $visit) }}">
            @csrf
            <button type="submit" class="btn btn-outline-primary"><i class="ti ti-refresh me-1"></i>{{ __('visit_payment_policy.actions.refresh') }}</button>
        </form>
    @endcan
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="alert alert-info"><i class="ti ti-info-circle me-1"></i>{{ __('visit_payment_policy.observational_notice') }}</div>

<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0">{{ __('visit_payment_policy.title') }}</h6>
                <span class="badge bg-{{ $isStale ? 'warning text-dark' : 'success' }}">
                    {{ $isStale ? __('visit_payment_policy.freshness.stale') : __('visit_payment_policy.freshness.current') }}
                </span>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5 text-muted">{{ __('visit_payment_policy.labels.observed_policy') }}</dt>
                    <dd class="col-sm-7"><span class="badge bg-primary">{{ $policy->resolved_policy->label() }}</span></dd>

                    <dt class="col-sm-5 text-muted">{{ __('visit_payment_policy.labels.policy_source') }}</dt>
                    <dd class="col-sm-7">{{ $policy->resolution_source->label() }}</dd>

                    <dt class="col-sm-5 text-muted">{{ __('visit_payment_policy.labels.resolution_reason') }}</dt>
                    <dd class="col-sm-7"><code>{{ $policy->resolution_reason_code ?? '—' }}</code></dd>

                    <dt class="col-sm-5 text-muted">{{ __('visit_payment_policy.labels.recommended_policy') }}</dt>
                    <dd class="col-sm-7">
                        @if($policy->recommended_policy)
                            <span class="badge bg-info">{{ $policy->recommended_policy->label() }}</span>
                        @else
                            <span class="text-muted">{{ __('visit_payment_policy.labels.no_recommendation') }}</span>
                        @endif
                    </dd>

                    <dt class="col-sm-5 text-muted">{{ __('visit_payment_policy.labels.finance_review_required') }}</dt>
                    <dd class="col-sm-7">{!! $policy->requires_finance_review ? '<span class="badge bg-warning text-dark">'.__('visit_payment_policy.labels.finance_review_required').'</span>' : '<span class="text-muted">—</span>' !!}</dd>

                    <dt class="col-sm-5 text-muted">{{ __('visit_payment_policy.labels.global_default_snapshot') }}</dt>
                    <dd class="col-sm-7">{{ $policy->global_default_snapshot ?? '—' }}</dd>

                    <dt class="col-sm-5 text-muted">{{ __('visit_payment_policy.labels.visit_type_policy_snapshot') }}</dt>
                    <dd class="col-sm-7">{{ $policy->visit_type_policy_snapshot ?? '—' }}</dd>

                    <dt class="col-sm-5 text-muted">{{ __('visit_payment_policy.labels.emergency_protection') }}</dt>
                    <dd class="col-sm-7">{{ $policy->emergency_protection_snapshot ? __('visit_payment_policy.labels.considered') : __('visit_payment_policy.labels.not_considered') }}</dd>

                    <dt class="col-sm-5 text-muted">{{ __('visit_payment_policy.labels.compatible_override') }}</dt>
                    <dd class="col-sm-7">{{ $policy->compatible_override_type_snapshot ? ($policy->compatible_override_type_snapshot.' ('.$policy->compatible_override_scope_snapshot.')') : __('visit_payment_policy.labels.none') }}</dd>

                    <dt class="col-sm-5 text-muted">{{ __('visit_payment_policy.labels.resolution_version') }}</dt>
                    <dd class="col-sm-7"><code>{{ $policy->resolution_version }}</code></dd>

                    <dt class="col-sm-5 text-muted">{{ __('visit_payment_policy.labels.materialized_at') }}</dt>
                    <dd class="col-sm-7">{{ $policy->materialized_at?->format('d M Y H:i') }}</dd>

                    <dt class="col-sm-5 text-muted">{{ __('visit_payment_policy.labels.last_refreshed') }}</dt>
                    <dd class="col-sm-7">{{ $policy->last_refreshed_at?->format('d M Y H:i') ?? '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h6 class="fw-bold mb-0">{{ __('visit_payment_policy.labels.risk_snapshot') }}</h6></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-6 text-muted">{{ __('visit_payment_policy.labels.risk_level_snapshot') }}</dt>
                    <dd class="col-sm-6">{{ $policy->patient_risk_level_snapshot?->label() ?? '—' }}</dd>
                    <dt class="col-sm-6 text-muted">{{ __('visit_payment_policy.labels.risk_status_snapshot') }}</dt>
                    <dd class="col-sm-6">{{ $policy->patient_risk_status_snapshot?->label() ?? '—' }}</dd>
                    <dt class="col-sm-6 text-muted">{{ __('visit_payment_policy.labels.risk_recommendation') }}</dt>
                    <dd class="col-sm-6"><code>{{ $policy->recommendation_reason_code ?? '—' }}</code></dd>
                </dl>
            </div>
        </div>
    </div>
</div>

@can('visits.payment_policy.history')
    <div class="card mt-3">
        <div class="card-header"><h6 class="fw-bold mb-0">{{ __('visit_payment_policy.history.title') }}</h6></div>
        <div class="card-body">
            @if($history->isEmpty())
                <p class="text-muted mb-0">{{ __('visit_payment_policy.history.empty') }}</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>{{ __('visit_payment_policy.history.datetime') }}</th>
                                <th>{{ __('visit_payment_policy.history.event') }}</th>
                                <th>{{ __('visit_payment_policy.history.new_policy') }}</th>
                                <th>{{ __('visit_payment_policy.history.new_recommendation') }}</th>
                                <th>{{ __('visit_payment_policy.history.performed_by') }}</th>
                                <th>{{ __('visit_payment_policy.history.reason_code') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($history as $entry)
                                @php $new = $entry->new_values ?? []; @endphp
                                <tr>
                                    <td class="text-nowrap">{{ $entry->performed_at?->format('d M Y H:i') }}</td>
                                    <td>{{ $entry->event_type?->label() }}</td>
                                    <td>{{ $new['resolved_policy'] ?? '—' }}</td>
                                    <td>{{ $new['recommended_policy'] ?? '—' }}</td>
                                    <td>{{ $entry->performer?->name ?? __('visit_payment_policy.history.system') }}</td>
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
