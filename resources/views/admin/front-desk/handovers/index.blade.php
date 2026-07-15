@extends('layouts.app')
@section('title', __('front_desk.handovers.title'))
@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ __('front_desk.handovers.title') }}
            <span class="badge badge-soft-primary border border-primary fs-13 fw-medium ms-2">{{ $logs->total() }}</span>
        </h4>
    </div>
    @can('front_desk.handovers.create')
    <a href="{{ $workspaceRoutes->route('admin.front-desk.handovers.create') }}" class="btn btn-primary btn-md fs-13"><i class="ti ti-plus me-1"></i>{{ __('front_desk.handovers.new') }}</a>
    @endcan
</div>

<div class="card mb-3"><div class="card-body">
    <form method="GET" action="{{ $workspaceRoutes->route('admin.front-desk.handovers.index') }}" class="row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label fs-13">{{ __('front_desk.actions.search') }}</label><input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}"></div>
        <div class="col-md-2"><label class="form-label fs-13">{{ __('front_desk.filters.date_from') }}</label><input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}"></div>
        <div class="col-md-2"><label class="form-label fs-13">{{ __('front_desk.filters.date_to') }}</label><input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}"></div>
        <div class="col-md-2"><label class="form-label fs-13">{{ __('front_desk.fields.status') }}</label>
            <select name="status" class="form-select"><option value="">{{ __('front_desk.filters.all_statuses') }}</option>
                @foreach($statuses as $s)<option value="{{ $s->value }}" @selected(($filters['status'] ?? '') === $s->value)>{{ $s->translatedLabel() }}</option>@endforeach
            </select></div>
        <div class="col-md-3 d-flex gap-2"><button type="submit" class="btn btn-primary"><i class="ti ti-filter me-1"></i>{{ __('front_desk.actions.filter') }}</button>
            <a href="{{ $workspaceRoutes->route('admin.front-desk.handovers.index') }}" class="btn btn-outline-secondary">{{ __('front_desk.actions.reset') }}</a></div>
    </form>
</div></div>

<div class="card"><div class="card-body p-0"><div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr>
            <th>{{ __('front_desk.fields.shift_date') }}</th><th>{{ __('front_desk.fields.shift_name') }}</th>
            <th>{{ __('front_desk.fields.outgoing_user') }}</th><th>{{ __('front_desk.fields.incoming_user') }}</th>
            <th>{{ __('front_desk.fields.status') }}</th><th class="text-end">{{ __('front_desk.actions.view') }}</th>
        </tr></thead>
        <tbody>
            @forelse($logs as $log)
            <tr>
                <td>{{ $log->shift_date?->format('d M Y') }}</td>
                <td>{{ $log->shift_name ?? __('front_desk.none') }}</td>
                <td>{{ $log->outgoingUser?->full_name ?? __('front_desk.none') }}</td>
                <td>{{ $log->incomingUser?->full_name ?? __('front_desk.none') }}</td>
                <td><x-status-badge :status="$log->status" size="sm" /></td>
                <td class="text-end"><a href="{{ $workspaceRoutes->route('admin.front-desk.handovers.show', $log) }}" class="btn btn-sm btn-icon btn-outline-primary" title="{{ __('front_desk.actions.view') }}"><i class="ti ti-eye"></i></a></td>
            </tr>
            @empty
            <tr><td colspan="6"><x-empty-state icon="ti-clipboard-list" :message="__('front_desk.handovers.none')" /></td></tr>
            @endforelse
        </tbody>
    </table>
</div></div></div>
<div class="mt-3">{{ $logs->withQueryString()->links() }}</div>
@endsection
