@extends('layouts.app')
@section('title', __('visit_payment_arrangement.worklist_title'))

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">{{ __('visit_payment_arrangement.worklist_title') }}</h4>
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('menu.dashboard') }}</a></li>
            <li class="breadcrumb-item active">{{ __('visit_payment_arrangement.worklist_title') }}</li>
        </ol></nav>
    </div>
    @can('visits.payment_arrangement.report')
        <a href="{{ route('admin.billing.visit-payment-arrangements.report') }}" class="btn btn-outline-secondary"><i class="ti ti-chart-bar me-1"></i>{{ __('visit_payment_arrangement.actions.report') }}</a>
    @endcan
</div>

<div class="alert alert-warning"><i class="ti ti-alert-triangle me-1"></i>{{ __('visit_payment_arrangement.administrative_notice') }}</div>

<div class="card mb-3"><div class="card-body">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-2">
            <label class="form-label small">{{ __('visit_payment_arrangement.filters.status') }}</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">{{ __('visit_payment_arrangement.filters.all') }}</option>
                @foreach($statuses as $s)<option value="{{ $s->value }}" @selected(($filters['status'] ?? '') === $s->value)>{{ $s->label() }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">{{ __('visit_payment_arrangement.filters.requested_policy') }}</label>
            <select name="requested_policy" class="form-select form-select-sm">
                <option value="">{{ __('visit_payment_arrangement.filters.all') }}</option>
                @foreach($policies as $p)<option value="{{ $p->value }}" @selected(($filters['requested_policy'] ?? '') === $p->value)>{{ $p->label() }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">{{ __('visit_payment_arrangement.filters.risk_level') }}</label>
            <select name="risk_level" class="form-select form-select-sm">
                <option value="">{{ __('visit_payment_arrangement.filters.all') }}</option>
                @foreach($levels as $l)<option value="{{ $l->value }}" @selected(($filters['risk_level'] ?? '') === $l->value)>{{ $l->label() }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-6 d-flex flex-wrap gap-3 align-items-center">
            <div class="form-check"><input class="form-check-input" type="checkbox" name="finance_review" value="1" id="f_fr" @checked(! empty($filters['finance_review']))><label class="form-check-label small" for="f_fr">{{ __('visit_payment_arrangement.filters.finance_review') }}</label></div>
            <div class="form-check"><input class="form-check-input" type="checkbox" name="expiring_soon" value="1" id="f_es" @checked(! empty($filters['expiring_soon']))><label class="form-check-label small" for="f_es">{{ __('visit_payment_arrangement.filters.expiring_soon') }}</label></div>
            <button type="submit" class="btn btn-sm btn-primary">{{ __('visit_payment_arrangement.filters.apply') }}</button>
            <a href="{{ route('admin.billing.visit-payment-arrangements.index') }}" class="btn btn-sm btn-light">{{ __('visit_payment_arrangement.filters.reset') }}</a>
        </div>
    </form>
</div></div>

<div class="card"><div class="card-body">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr>
                <th>{{ __('visit_payment_arrangement.fields.visit') }}</th>
                <th>{{ __('visit_payment_arrangement.fields.patient') }}</th>
                <th>{{ __('visit_payment_arrangement.fields.requested_policy') }}</th>
                <th>{{ __('visit_payment_arrangement.fields.approved_policy') }}</th>
                <th>{{ __('visit_payment_arrangement.fields.status') }}</th>
                <th>{{ __('visit_payment_arrangement.fields.risk_snapshot') }}</th>
                <th>{{ __('visit_payment_arrangement.fields.requester') }}</th>
                <th>{{ __('visit_payment_arrangement.fields.approver') }}</th>
                <th>{{ __('visit_payment_arrangement.fields.expires_at') }}</th>
            </tr></thead>
            <tbody>
                @forelse($arrangements as $a)
                    <tr>
                        <td><a href="{{ route('admin.billing.visit-payment-arrangements.show', $a) }}">{{ $a->visit?->visit_number }}</a></td>
                        <td class="small">{{ $a->visit?->patient?->full_name }}<div class="text-muted">{{ $a->visit?->patient?->patient_number }}</div></td>
                        <td class="small">{{ $a->requested_policy->label() }}</td>
                        <td class="small">{{ $a->approved_policy?->label() ?? '—' }}</td>
                        <td><span class="badge bg-{{ $a->status->color() }}">{{ $a->status->label() }}</span></td>
                        <td class="small">{{ $a->risk_level_snapshot?->label() ?? '—' }}</td>
                        <td class="small">{{ $a->requester?->name ?? '—' }}</td>
                        <td class="small">{{ $a->approver?->name ?? '—' }}</td>
                        <td class="small text-nowrap">{{ optional($a->expires_at)->format('d M Y') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">{{ __('visit_payment_arrangement.no_results') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $arrangements->links() }}
</div></div>
@endsection
