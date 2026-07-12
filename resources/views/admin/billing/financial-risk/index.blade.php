@extends('layouts.app')
@section('title', __('patient_financial_risk.worklist_title'))

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">{{ __('patient_financial_risk.worklist_title') }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('menu.dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ __('patient_financial_risk.worklist_title') }}</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        @can('patients.financial_risk.report')
            <a href="{{ route('admin.billing.financial-risk.report') }}" class="btn btn-outline-secondary"><i class="ti ti-chart-bar me-1"></i>{{ __('patient_financial_risk.report_title') }}</a>
            <a href="{{ route('admin.billing.financial-risk.export', request()->query()) }}" class="btn btn-outline-primary"><i class="ti ti-download me-1"></i>{{ __('patient_financial_risk.actions.export') }}</a>
        @endcan
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">{{ __('patient_financial_risk.filters.search') }}</label>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label small">{{ __('patient_financial_risk.filters.level') }}</label>
                <select name="level" class="form-select form-select-sm">
                    <option value="">{{ __('patient_financial_risk.filters.all') }}</option>
                    @foreach($levels as $level)
                        <option value="{{ $level->value }}" @selected(($filters['level'] ?? '') === $level->value)>{{ $level->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">{{ __('patient_financial_risk.filters.status') }}</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">{{ __('patient_financial_risk.filters.all') }}</option>
                    @foreach($statuses as $st)
                        <option value="{{ $st->value }}" @selected(($filters['status'] ?? '') === $st->value)>{{ $st->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5 d-flex flex-wrap gap-3 align-items-center">
                <div class="form-check"><input class="form-check-input" type="checkbox" name="restriction" value="1" id="f_restriction" @checked(! empty($filters['restriction']))><label class="form-check-label small" for="f_restriction">{{ __('patient_financial_risk.filters.restriction') }}</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="review_due" value="1" id="f_review" @checked(! empty($filters['review_due']))><label class="form-check-label small" for="f_review">{{ __('patient_financial_risk.filters.review_due') }}</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="expired" value="1" id="f_expired" @checked(! empty($filters['expired']))><label class="form-check-label small" for="f_expired">{{ __('patient_financial_risk.filters.expired') }}</label></div>
                <button type="submit" class="btn btn-sm btn-primary">{{ __('patient_financial_risk.filters.apply') }}</button>
                <a href="{{ route('admin.billing.financial-risk.index') }}" class="btn btn-sm btn-light">{{ __('patient_financial_risk.filters.reset') }}</a>
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
                        <th>{{ __('patient_financial_risk.fields.patient') }}</th>
                        <th>{{ __('patient_financial_risk.fields.risk_level') }}</th>
                        <th>{{ __('patient_financial_risk.fields.status') }}</th>
                        <th>{{ __('patient_financial_risk.fields.primary_reason') }}</th>
                        <th>{{ __('patient_financial_risk.fields.effective_from') }}</th>
                        <th>{{ __('patient_financial_risk.fields.review_due_at') }}</th>
                        <th>{{ __('patient_financial_risk.fields.expires_at') }}</th>
                        <th class="text-end">{{ __('patient_financial_risk.fields.credit_limit') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($profiles as $profile)
                        <tr>
                            <td>
                                <a href="{{ route('admin.patients.show', $profile->patient) }}#financial-risk">
                                    {{ $profile->patient?->full_name }}
                                </a>
                                <div class="text-muted small">{{ $profile->patient?->patient_number }}</div>
                            </td>
                            <td><span class="badge bg-{{ $profile->risk_level->color() }}">{{ $profile->risk_level->label() }}</span></td>
                            <td><span class="badge bg-{{ $profile->status->color() }}">{{ $profile->status->label() }}</span></td>
                            <td class="small">{{ $profile->primary_reason?->label() ?? '—' }}</td>
                            <td class="small text-nowrap">{{ optional($profile->effective_from)->format('d M Y') ?? '—' }}</td>
                            <td class="small text-nowrap">
                                {{ optional($profile->review_due_at)->format('d M Y') ?? '—' }}
                                @if($profile->isReviewOverdue())<span class="badge bg-warning text-dark ms-1">!</span>@endif
                            </td>
                            <td class="small text-nowrap">{{ optional($profile->expires_at)->format('d M Y') ?? '—' }}</td>
                            <td class="text-end">{{ $profile->credit_limit !== null ? number_format((float) $profile->credit_limit, 2) : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">{{ __('patient_financial_risk.no_results') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $profiles->links() }}
    </div>
</div>
@endsection
