@extends('layouts.app')
@section('title', __('visit_payment_policy.worklist_title'))

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">{{ __('visit_payment_policy.worklist_title') }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('menu.dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ __('visit_payment_policy.worklist_title') }}</li>
            </ol>
        </nav>
    </div>
    @can('visits.payment_policy.report')
        <a href="{{ route('admin.billing.visit-payment-policies.report') }}" class="btn btn-outline-secondary"><i class="ti ti-chart-bar me-1"></i>{{ __('visit_payment_policy.actions.report') }}</a>
    @endcan
</div>

<div class="alert alert-info"><i class="ti ti-info-circle me-1"></i>{{ __('visit_payment_policy.observational_notice') }}</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small">{{ __('visit_payment_policy.filters.resolved_policy') }}</label>
                <select name="resolved_policy" class="form-select form-select-sm">
                    <option value="">{{ __('visit_payment_policy.filters.all') }}</option>
                    @foreach($policies_enum as $p)
                        <option value="{{ $p->value }}" @selected(($filters['resolved_policy'] ?? '') === $p->value)>{{ $p->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">{{ __('visit_payment_policy.filters.recommended_policy') }}</label>
                <select name="recommended_policy" class="form-select form-select-sm">
                    <option value="">{{ __('visit_payment_policy.filters.all') }}</option>
                    @foreach($policies_enum as $p)
                        <option value="{{ $p->value }}" @selected(($filters['recommended_policy'] ?? '') === $p->value)>{{ $p->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">{{ __('visit_payment_policy.filters.risk_level') }}</label>
                <select name="risk_level" class="form-select form-select-sm">
                    <option value="">{{ __('visit_payment_policy.filters.all') }}</option>
                    @foreach($levels as $l)
                        <option value="{{ $l->value }}" @selected(($filters['risk_level'] ?? '') === $l->value)>{{ $l->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">{{ __('visit_payment_policy.filters.visit_type') }}</label>
                <input type="text" name="visit_type" value="{{ $filters['visit_type'] ?? '' }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-4 d-flex flex-wrap gap-3 align-items-center">
                <div class="form-check"><input class="form-check-input" type="checkbox" name="finance_review" value="1" id="f_fr" @checked(! empty($filters['finance_review']))><label class="form-check-label small" for="f_fr">{{ __('visit_payment_policy.filters.finance_review') }}</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="active_only" value="1" id="f_ao" @checked(! empty($filters['active_only']))><label class="form-check-label small" for="f_ao">{{ __('visit_payment_policy.filters.active_only') }}</label></div>
                <button type="submit" class="btn btn-sm btn-primary">{{ __('visit_payment_policy.filters.apply') }}</button>
                <a href="{{ route('admin.billing.visit-payment-policies.index') }}" class="btn btn-sm btn-light">{{ __('visit_payment_policy.filters.reset') }}</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>{{ __('visit_payment_policy.labels.visit') }}</th>
                        <th>{{ __('visit_payment_policy.labels.patient') }}</th>
                        <th>{{ __('visit_payment_policy.labels.visit_type_snapshot') }}</th>
                        <th>{{ __('visit_payment_policy.labels.observed_policy') }}</th>
                        <th>{{ __('visit_payment_policy.labels.policy_source') }}</th>
                        <th>{{ __('visit_payment_policy.labels.risk_level_snapshot') }}</th>
                        <th>{{ __('visit_payment_policy.labels.recommended_policy') }}</th>
                        <th>{{ __('visit_payment_policy.labels.finance_review_required') }}</th>
                        <th>{{ __('visit_payment_policy.labels.snapshot_freshness') }}</th>
                        <th>{{ __('visit_payment_policy.labels.materialized_at') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($policies as $policy)
                        <tr>
                            <td><a href="{{ route('admin.billing.visit-payment-policies.show', $policy->visit) }}">{{ $policy->visit?->visit_number }}</a></td>
                            <td class="small">{{ $policy->visit?->patient?->full_name }}<div class="text-muted">{{ $policy->visit?->patient?->patient_number }}</div></td>
                            <td class="small">{{ $policy->visit_type_snapshot ?? '—' }}</td>
                            <td><span class="badge bg-primary">{{ $policy->resolved_policy->label() }}</span></td>
                            <td class="small">{{ $policy->resolution_source->label() }}</td>
                            <td class="small">{{ $policy->patient_risk_level_snapshot?->label() ?? '—' }}</td>
                            <td class="small">{{ $policy->recommended_policy?->label() ?? '—' }}</td>
                            <td>{!! $policy->requires_finance_review ? '<span class="badge bg-warning text-dark">'.__('visit_payment_policy.labels.finance_review_required').'</span>' : '—' !!}</td>
                            <td>
                                <span class="badge bg-{{ ($stale[$policy->id] ?? false) ? 'warning text-dark' : 'success' }}">
                                    {{ ($stale[$policy->id] ?? false) ? __('visit_payment_policy.freshness.stale') : __('visit_payment_policy.freshness.current') }}
                                </span>
                            </td>
                            <td class="small text-nowrap">{{ $policy->materialized_at?->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted py-4">{{ __('visit_payment_policy.no_results') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $policies->links() }}
    </div>
</div>
@endsection
