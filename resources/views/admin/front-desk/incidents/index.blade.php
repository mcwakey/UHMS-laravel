@extends('layouts.app')
@section('title', __('front_desk.incidents.title'))
@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ __('front_desk.incidents.title') }}
            <span class="badge badge-soft-primary border border-primary fs-13 fw-medium ms-2">{{ $logs->total() }}</span>
        </h4>
    </div>
    @can('front_desk.incidents.create')
    <a href="{{ route('admin.front-desk.incidents.create') }}" class="btn btn-primary btn-md fs-13"><i class="ti ti-plus me-1"></i>{{ __('front_desk.incidents.new') }}</a>
    @endcan
</div>

<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('admin.front-desk.incidents.index') }}" class="btn btn-sm {{ empty(array_filter($filters)) ? 'btn-primary' : 'btn-outline-secondary' }}">{{ __('front_desk.quick.all') }}</a>
    <a href="{{ route('admin.front-desk.incidents.index', ['open' => 1]) }}" class="btn btn-sm {{ !empty($filters['open']) ? 'btn-primary' : 'btn-outline-secondary' }}">{{ __('front_desk.incidents.open_only') }}</a>
    <a href="{{ route('admin.front-desk.incidents.index', ['critical' => 1]) }}" class="btn btn-sm {{ !empty($filters['critical']) ? 'btn-danger' : 'btn-outline-danger' }}">{{ __('front_desk.incidents.critical_only') }}</a>
</div>

<div class="card mb-3"><div class="card-body">
    <form method="GET" action="{{ route('admin.front-desk.incidents.index') }}" class="row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label fs-13">{{ __('front_desk.actions.search') }}</label><input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}"></div>
        <div class="col-md-2"><label class="form-label fs-13">{{ __('front_desk.fields.incident_type') }}</label>
            <select name="incident_type" class="form-select"><option value="">{{ __('front_desk.filters.all_types') }}</option>@foreach($types as $t)<option value="{{ $t->value }}" @selected(($filters['incident_type'] ?? '') === $t->value)>{{ $t->translatedLabel() }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label fs-13">{{ __('front_desk.fields.severity') }}</label>
            <select name="severity" class="form-select"><option value="">{{ __('front_desk.quick.all') }}</option>@foreach($severities as $s)<option value="{{ $s->value }}" @selected(($filters['severity'] ?? '') === $s->value)>{{ $s->translatedLabel() }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label fs-13">{{ __('front_desk.fields.status') }}</label>
            <select name="status" class="form-select"><option value="">{{ __('front_desk.filters.all_statuses') }}</option>@foreach($statuses as $st)<option value="{{ $st->value }}" @selected(($filters['status'] ?? '') === $st->value)>{{ $st->translatedLabel() }}</option>@endforeach</select></div>
        <div class="col-md-3 d-flex gap-2"><button type="submit" class="btn btn-primary"><i class="ti ti-filter me-1"></i>{{ __('front_desk.actions.filter') }}</button>
            <a href="{{ route('admin.front-desk.incidents.index') }}" class="btn btn-outline-secondary">{{ __('front_desk.actions.reset') }}</a></div>
    </form>
</div></div>

<div class="card"><div class="card-body p-0"><div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr>
            <th>{{ __('front_desk.fields.incident_number') }}</th><th>{{ __('front_desk.fields.incident_type') }}</th>
            <th>{{ __('front_desk.fields.severity') }}</th><th>{{ __('front_desk.fields.location') }}</th>
            <th>{{ __('front_desk.fields.assigned_to') }}</th><th>{{ __('front_desk.fields.status') }}</th>
            <th class="text-end">{{ __('front_desk.actions.view') }}</th>
        </tr></thead>
        <tbody>
            @forelse($logs as $log)
            <tr>
                <td class="fw-medium">{{ $log->incident_number ?? __('front_desk.none') }}</td>
                <td>{{ $log->incident_type?->translatedLabel() }}</td>
                <td><x-status-badge :status="$log->severity" size="sm" /></td>
                <td>{{ $log->location ?? __('front_desk.none') }}</td>
                <td>{{ $log->assignedToUser?->full_name ?? __('front_desk.none') }}</td>
                <td><x-status-badge :status="$log->status" size="sm" soft /></td>
                <td class="text-end"><a href="{{ route('admin.front-desk.incidents.show', $log) }}" class="btn btn-sm btn-icon btn-outline-primary" title="{{ __('front_desk.actions.view') }}"><i class="ti ti-eye"></i></a></td>
            </tr>
            @empty
            <tr><td colspan="7"><x-empty-state icon="ti-alert-octagon" :message="__('front_desk.incidents.none')" /></td></tr>
            @endforelse
        </tbody>
    </table>
</div></div></div>
<div class="mt-3">{{ $logs->withQueryString()->links() }}</div>
@endsection
