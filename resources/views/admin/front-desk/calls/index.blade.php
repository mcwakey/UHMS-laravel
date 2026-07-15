@extends('layouts.app')
@section('title', __('front_desk.calls.title'))

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ __('front_desk.calls.title') }}
            <span class="badge badge-soft-primary border border-primary fs-13 fw-medium ms-2">{{ $logs->total() }}</span>
        </h4>
    </div>
    <div class="d-flex gap-2">
        @can('front_desk.calls.followups.view')
        <a href="{{ $workspaceRoutes->route('admin.front-desk.calls.follow-ups') }}" class="btn btn-outline-primary btn-md fs-13">
            <i class="ti ti-phone-call me-1"></i>{{ __('front_desk.actions.view_queue') }}
        </a>
        @endcan
        @can('front_desk.calls.create')
        <a href="{{ $workspaceRoutes->route('admin.front-desk.calls.create') }}" class="btn btn-primary btn-md fs-13">
            <i class="ti ti-plus me-1"></i>{{ __('front_desk.calls.new') }}
        </a>
        @endcan
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ $workspaceRoutes->route('admin.front-desk.calls.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label fs-13">{{ __('front_desk.actions.search') }}</label>
                <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('front_desk.placeholders.search_calls') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label fs-13">{{ __('front_desk.filters.date_from') }}</label>
                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label fs-13">{{ __('front_desk.filters.date_to') }}</label>
                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label fs-13">{{ __('front_desk.fields.direction') }}</label>
                <select name="direction" class="form-select">
                    <option value="">{{ __('front_desk.filters.all_directions') }}</option>
                    @foreach($directions as $d)
                    <option value="{{ $d->value }}" @selected(($filters['direction'] ?? '') === $d->value)>{{ $d->translatedLabel() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fs-13">{{ __('front_desk.fields.category') }}</label>
                <select name="category" class="form-select">
                    <option value="">{{ __('front_desk.filters.all_categories') }}</option>
                    @foreach($categories as $c)
                    <option value="{{ $c->value }}" @selected(($filters['category'] ?? '') === $c->value)>{{ $c->translatedLabel() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-13">{{ __('front_desk.fields.outcome') }}</label>
                <select name="outcome" class="form-select">
                    <option value="">{{ __('front_desk.filters.all_outcomes') }}</option>
                    @foreach($outcomes as $o)
                    <option value="{{ $o->value }}" @selected(($filters['outcome'] ?? '') === $o->value)>{{ $o->translatedLabel() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-13">{{ __('front_desk.fields.department') }}</label>
                <select name="department_id" class="form-select">
                    <option value="">{{ __('front_desk.filters.all_departments') }}</option>
                    @foreach($departments as $d)
                    <option value="{{ $d->id }}" @selected((string) ($filters['department_id'] ?? '') === (string) $d->id)>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-13">{{ __('front_desk.fields.follow_up_required') }}</label>
                <select name="follow_up_required" class="form-select">
                    <option value="">{{ __('front_desk.filters.follow_up_any') }}</option>
                    <option value="1" @selected(($filters['follow_up_required'] ?? '') === '1')>{{ __('front_desk.filters.follow_up_required') }}</option>
                    <option value="0" @selected(($filters['follow_up_required'] ?? '') === '0')>{{ __('front_desk.filters.follow_up_not_required') }}</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-13">{{ __('front_desk.fields.follow_up_status') }}</label>
                <select name="follow_up_status" class="form-select">
                    <option value="">{{ __('front_desk.filters.all_follow_up_statuses') }}</option>
                    @foreach($followUpStatuses as $fs)
                    <option value="{{ $fs->value }}" @selected(($filters['follow_up_status'] ?? '') === $fs->value)>{{ $fs->translatedLabel() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-13">{{ __('front_desk.filters.overdue') }}</label>
                <select name="overdue" class="form-select">
                    <option value="">{{ __('front_desk.quick.all') }}</option>
                    <option value="1" @selected(($filters['overdue'] ?? '') === '1')>{{ __('front_desk.filters.overdue') }}</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="ti ti-filter me-1"></i>{{ __('front_desk.actions.filter') }}</button>
                <a href="{{ $workspaceRoutes->route('admin.front-desk.calls.index') }}" class="btn btn-outline-secondary">{{ __('front_desk.actions.reset') }}</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('front_desk.fields.direction') }}</th>
                        <th>{{ __('front_desk.fields.category') }}</th>
                        <th>{{ __('front_desk.fields.phone_number') }}</th>
                        <th>{{ __('front_desk.fields.started_at') }}</th>
                        <th>{{ __('front_desk.fields.outcome') }}</th>
                        <th>{{ __('front_desk.fields.follow_up_status') }}</th>
                        <th class="text-end">{{ __('front_desk.actions.view') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td><x-status-badge :status="$log->direction" size="sm" soft /></td>
                        <td>{{ $log->category?->translatedLabel() }}</td>
                        <td>{{ $log->phone_number ?? __('front_desk.none') }}</td>
                        <td>{{ $log->started_at?->format('d M Y H:i') }}</td>
                        <td><x-status-badge :status="$log->outcome" size="sm" /></td>
                        <td>
                            @if($log->follow_up_required)
                                <span class="badge badge-soft-warning">{{ __('front_desk.follow_up_status.' . ($log->follow_up_status ?? 'pending')) }}</span>
                            @else
                                <span class="text-muted">{{ __('front_desk.none') }}</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ $workspaceRoutes->route('admin.front-desk.calls.show', $log) }}" class="btn btn-sm btn-icon btn-outline-primary" title="{{ __('front_desk.actions.view') }}"><i class="ti ti-eye"></i></a>
                            @can('front_desk.calls.update')
                            <a href="{{ $workspaceRoutes->route('admin.front-desk.calls.edit', $log) }}" class="btn btn-sm btn-icon btn-outline-secondary" title="{{ __('front_desk.actions.edit') }}"><i class="ti ti-edit"></i></a>
                            @if($log->hasPendingFollowUp())
                            <form method="POST" action="{{ $workspaceRoutes->route('admin.front-desk.calls.follow-up-complete', $log) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-icon btn-outline-success" title="{{ __('front_desk.actions.complete_follow_up') }}"><i class="ti ti-check"></i></button>
                            </form>
                            @endif
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7"><x-empty-state icon="ti-phone" :message="__('front_desk.calls.none')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">{{ $logs->withQueryString()->links() }}</div>
@endsection
