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

@can('visits.payment_arrangement.view')
    @php $isEmergency = ($policy->emergency_protection_snapshot) || ((is_object($visit->visit_type) ? $visit->visit_type->value : $visit->visit_type) === 'emergency'); @endphp
    <div class="card mt-3 border-info">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0">{{ __('visit_payment_arrangement.cards.approved_arrangement') }}</h6>
            @if(($approvedArrangement ?? null))
                <a href="{{ route('admin.billing.visit-payment-arrangements.show', $approvedArrangement) }}" class="btn btn-sm btn-outline-secondary">{{ __('visit_payment_arrangement.actions.view_history') }}</a>
            @endif
        </div>
        <div class="card-body">
            <div class="alert alert-warning py-2 mb-3"><i class="ti ti-alert-triangle me-1"></i>{{ __('visit_payment_arrangement.administrative_notice') }}</div>
            @if($isEmergency)
                <div class="alert alert-danger py-2 mb-3"><i class="ti ti-urgent me-1"></i>{{ __('visit_payment_arrangement.emergency_notice') }}</div>
            @endif

            @if(($approvedArrangement ?? null))
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted">{{ __('visit_payment_arrangement.fields.approved_policy') }}</dt>
                    <dd class="col-sm-8"><span class="badge bg-info">{{ $approvedArrangement->approved_policy->label() }}</span></dd>
                    <dt class="col-sm-4 text-muted">{{ __('visit_payment_arrangement.fields.approver') }}</dt>
                    <dd class="col-sm-8">{{ $approvedArrangement->approver?->name ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">{{ __('visit_payment_arrangement.fields.expires_at') }}</dt>
                    <dd class="col-sm-8">{{ optional($approvedArrangement->expires_at)->format('d M Y') ?? '—' }}</dd>
                </dl>
                @can('visits.payment_arrangement.restore_baseline')
                    <form method="POST" action="{{ route('admin.billing.visits.payment-arrangements.restore-baseline', $visit) }}" class="mt-3 d-flex gap-2">
                        @csrf
                        <input type="text" name="reason" class="form-control form-control-sm" maxlength="1000" placeholder="{{ __('visit_payment_arrangement.fields.request_reason') }}" required>
                        <button type="submit" class="btn btn-sm btn-outline-danger text-nowrap">{{ __('visit_payment_arrangement.actions.restore_baseline') }}</button>
                    </form>
                @endcan
            @elseif(($pendingArrangement ?? null))
                <p class="mb-2">{{ __('visit_payment_arrangement.fields.status') }}:
                    <span class="badge bg-{{ $pendingArrangement->status->color() }}">{{ $pendingArrangement->status->label() }}</span>
                    — <a href="{{ route('admin.billing.visit-payment-arrangements.show', $pendingArrangement) }}">{{ __('visit_payment_arrangement.actions.view_history') }}</a>
                </p>
            @else
                <p class="text-muted">{{ __('visit_payment_arrangement.empty_state') }}</p>
                @can('visits.payment_arrangement.request')
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#vpaRequestModal">{{ __('visit_payment_arrangement.actions.request') }}</button>
                @endcan
            @endif
        </div>
    </div>

    @can('visits.payment_arrangement.request')
    @unless(($approvedArrangement ?? null) || ($pendingArrangement ?? null))
    <div class="modal fade" id="vpaRequestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form method="POST" action="{{ route('admin.billing.visits.payment-arrangements.store', $visit) }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title">{{ __('visit_payment_arrangement.actions.request') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="alert alert-info py-2">{{ __('visit_payment_arrangement.does_not_control') }}</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="vpa_policy">{{ __('visit_payment_arrangement.fields.requested_policy') }}</label>
                                <select name="requested_policy" id="vpa_policy" class="form-select" required>
                                    @foreach(\App\Enums\VisitPaymentTimingPolicy::operationalPolicies() as $p)
                                        <option value="{{ $p->value }}">{{ $p->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="vpa_effective">{{ __('visit_payment_arrangement.fields.effective_from') }}</label>
                                <input type="date" name="effective_from" id="vpa_effective" class="form-control" value="{{ now()->toDateString() }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="vpa_expires">{{ __('visit_payment_arrangement.fields.expires_at') }}</label>
                                <input type="date" name="expires_at" id="vpa_expires" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="vpa_ref">{{ __('visit_payment_arrangement.fields.supporting_reference') }}</label>
                                <input type="text" name="supporting_reference" id="vpa_ref" class="form-control" maxlength="191">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="vpa_reason">{{ __('visit_payment_arrangement.fields.request_reason') }}</label>
                                <textarea name="request_reason" id="vpa_reason" rows="2" class="form-control" maxlength="1000" required></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('visit_payment_arrangement.actions.cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('visit_payment_arrangement.actions.submit') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endunless
    @endcan
@endcan

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
