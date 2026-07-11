@extends('layouts.app')
@section('title', __('front_desk.visitors.title'))

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ __('front_desk.visitors.title') }}
            <span class="badge badge-soft-primary border border-primary fs-13 fw-medium ms-2">{{ $logs->total() }}</span>
        </h4>
    </div>
    @can('front_desk.visitors.create')
    <a href="{{ route('admin.front-desk.visitors.create') }}" class="btn btn-primary btn-md fs-13">
        <i class="ti ti-plus me-1"></i>{{ __('front_desk.visitors.new') }}
    </a>
    @endcan
</div>

{{-- Quick filters --}}
<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('admin.front-desk.visitors.index') }}" class="btn btn-sm {{ empty($filters['quick']) ? 'btn-primary' : 'btn-outline-secondary' }}">{{ __('front_desk.quick.all') }}</a>
    @foreach($quickFilters as $qf)
    <a href="{{ route('admin.front-desk.visitors.index', ['quick' => $qf]) }}"
       class="btn btn-sm {{ ($filters['quick'] ?? '') === $qf ? 'btn-primary' : 'btn-outline-secondary' }}">{{ __('front_desk.quick.' . $qf) }}</a>
    @endforeach
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.front-desk.visitors.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label fs-13">{{ __('front_desk.actions.search') }}</label>
                <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('front_desk.placeholders.search_visitors') }}">
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
                <label class="form-label fs-13">{{ __('front_desk.fields.status') }}</label>
                <select name="status" class="form-select">
                    <option value="">{{ __('front_desk.filters.all_statuses') }}</option>
                    @foreach($statuses as $s)
                    <option value="{{ $s->value }}" @selected(($filters['status'] ?? '') === $s->value)>{{ $s->translatedLabel() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-13">{{ __('front_desk.fields.visitor_context') }}</label>
                <select name="visitor_context" class="form-select">
                    <option value="">{{ __('front_desk.filters.all_contexts') }}</option>
                    @foreach($contexts as $ctx)
                    <option value="{{ $ctx->value }}" @selected(($filters['visitor_context'] ?? '') === $ctx->value)>{{ $ctx->translatedLabel() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fs-13">{{ __('front_desk.fields.department') }}</label>
                <select name="department_id" class="form-select">
                    <option value="">{{ __('front_desk.filters.all_departments') }}</option>
                    @foreach($departments as $d)
                    <option value="{{ $d->id }}" @selected((string) ($filters['department_id'] ?? '') === (string) $d->id)>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="ti ti-filter me-1"></i>{{ __('front_desk.actions.filter') }}</button>
                <a href="{{ route('admin.front-desk.visitors.index') }}" class="btn btn-outline-secondary">{{ __('front_desk.actions.reset') }}</a>
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
                        <th>{{ __('front_desk.fields.visitor_name') }}</th>
                        <th>{{ __('front_desk.fields.visitor_context') }}</th>
                        <th>{{ __('front_desk.fields.person_to_see') }}</th>
                        <th>{{ __('front_desk.fields.time_in') }}</th>
                        <th>{{ __('front_desk.fields.time_out') }}</th>
                        <th>{{ __('front_desk.fields.status') }}</th>
                        <th class="text-end">{{ __('front_desk.actions.view') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td>
                            <div class="fw-medium">{{ $log->visitor_name }}</div>
                            <small class="text-muted">{{ $log->visitor_phone ?: $log->organization }}</small>
                        </td>
                        <td>
                            <x-status-badge :status="$log->visitor_context" size="sm" soft />
                            @if($log->patient_id)<i class="ti ti-user-heart text-primary ms-1" title="{{ __('front_desk.fields.patient') }}"></i>@endif
                        </td>
                        <td>{{ $log->person_to_see ?? ($log->ward?->name ?? $log->department?->name ?? __('front_desk.none')) }}</td>
                        <td>{{ $log->time_in?->format('d M Y H:i') }}</td>
                        <td>{{ $log->time_out?->format('d M Y H:i') ?? __('front_desk.none') }}</td>
                        <td>
                            <x-status-badge :status="$log->status" size="sm" />
                            @if($log->isOverdue())<span class="badge bg-danger ms-1">{{ __('front_desk.visitors.overdue') }}</span>@endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.front-desk.visitors.show', $log) }}" class="btn btn-sm btn-icon btn-outline-primary" title="{{ __('front_desk.actions.view') }}"><i class="ti ti-eye"></i></a>
                            @can('front_desk.visitors.update')
                            <a href="{{ route('admin.front-desk.visitors.edit', $log) }}" class="btn btn-sm btn-icon btn-outline-secondary" title="{{ __('front_desk.actions.edit') }}"><i class="ti ti-edit"></i></a>
                            @endcan
                            @can('front_desk.visitors.checkout')
                            @if($log->isInside())
                            <form method="POST" action="{{ route('admin.front-desk.visitors.check-out', $log) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-icon btn-outline-success" title="{{ __('front_desk.actions.check_out') }}"><i class="ti ti-logout"></i></button>
                            </form>
                            @endif
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7"><x-empty-state icon="ti-users" :message="__('front_desk.visitors.none')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">{{ $logs->withQueryString()->links() }}</div>
@endsection
