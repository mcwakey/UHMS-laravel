@extends('layouts.app')
@section('title', __('front_desk.lost_found.title'))
@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ __('front_desk.lost_found.title') }}
            <span class="badge badge-soft-primary border border-primary fs-13 fw-medium ms-2">{{ $logs->total() }}</span>
        </h4>
    </div>
    @can('front_desk.lost_found.create')
    <a href="{{ $workspaceRoutes->route('admin.front-desk.lost-found.create') }}" class="btn btn-primary btn-md fs-13"><i class="ti ti-plus me-1"></i>{{ __('front_desk.lost_found.new') }}</a>
    @endcan
</div>

<div class="card mb-3"><div class="card-body">
    <form method="GET" action="{{ $workspaceRoutes->route('admin.front-desk.lost-found.index') }}" class="row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label fs-13">{{ __('front_desk.actions.search') }}</label><input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}"></div>
        <div class="col-md-2"><label class="form-label fs-13">{{ __('front_desk.fields.item_status') }}</label>
            <select name="item_status" class="form-select"><option value="">{{ __('front_desk.filters.all_statuses') }}</option>
                @foreach($statuses as $s)<option value="{{ $s->value }}" @selected(($filters['item_status'] ?? '') === $s->value)>{{ $s->translatedLabel() }}</option>@endforeach
            </select></div>
        <div class="col-md-2"><label class="form-label fs-13">{{ __('front_desk.fields.item_category') }}</label>
            <select name="item_category" class="form-select"><option value="">{{ __('front_desk.filters.all_types') }}</option>
                @foreach($categories as $c)<option value="{{ $c->value }}" @selected(($filters['item_category'] ?? '') === $c->value)>{{ $c->translatedLabel() }}</option>@endforeach
            </select></div>
        <div class="col-md-2"><label class="form-label fs-13">{{ __('front_desk.lost_found.unclaimed_only') }}</label>
            <select name="unclaimed" class="form-select"><option value="">{{ __('front_desk.quick.all') }}</option>
                <option value="1" @selected(($filters['unclaimed'] ?? '') === '1')>{{ __('front_desk.dashboard.unclaimed_lost_found') }}</option>
            </select></div>
        <div class="col-md-3 d-flex gap-2"><button type="submit" class="btn btn-primary"><i class="ti ti-filter me-1"></i>{{ __('front_desk.actions.filter') }}</button>
            <a href="{{ $workspaceRoutes->route('admin.front-desk.lost-found.index') }}" class="btn btn-outline-secondary">{{ __('front_desk.actions.reset') }}</a></div>
    </form>
</div></div>

<div class="card"><div class="card-body p-0"><div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr>
            <th>{{ __('front_desk.fields.reference_number') }}</th><th>{{ __('front_desk.fields.item_category') }}</th>
            <th>{{ __('front_desk.fields.item_description') }}</th><th>{{ __('front_desk.fields.found_or_reported_at') }}</th>
            <th>{{ __('front_desk.fields.stored_location') }}</th><th>{{ __('front_desk.fields.item_status') }}</th>
            <th class="text-end">{{ __('front_desk.actions.view') }}</th>
        </tr></thead>
        <tbody>
            @forelse($logs as $log)
            <tr>
                <td class="fw-medium">{{ $log->reference_number ?? __('front_desk.none') }}</td>
                <td><x-status-badge :status="$log->item_category" size="sm" soft /></td>
                <td>{{ Str::limit($log->item_description, 40) }}</td>
                <td>{{ $log->found_or_reported_at?->format('d M Y H:i') }}</td>
                <td>{{ $log->stored_location ?? __('front_desk.none') }}</td>
                <td><x-status-badge :status="$log->item_status" size="sm" /></td>
                <td class="text-end"><a href="{{ $workspaceRoutes->route('admin.front-desk.lost-found.show', $log) }}" class="btn btn-sm btn-icon btn-outline-primary" title="{{ __('front_desk.actions.view') }}"><i class="ti ti-eye"></i></a></td>
            </tr>
            @empty
            <tr><td colspan="7"><x-empty-state icon="ti-briefcase" :message="__('front_desk.lost_found.none')" /></td></tr>
            @endforelse
        </tbody>
    </table>
</div></div></div>
<div class="mt-3">{{ $logs->withQueryString()->links() }}</div>
@endsection
