@extends('layouts.app')
@section('title', __('front_desk.couriers.title'))

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ __('front_desk.couriers.title') }}
            <span class="badge badge-soft-primary border border-primary fs-13 fw-medium ms-2">{{ $logs->total() }}</span>
        </h4>
    </div>
    <div class="d-flex gap-2">
        @can('front_desk.couriers.workflow.view')
        <a href="{{ $workspaceRoutes->route('admin.front-desk.couriers.workflow') }}" class="btn btn-outline-primary btn-md fs-13">
            <i class="ti ti-truck-delivery me-1"></i>{{ __('front_desk.actions.view_workflow') }}
        </a>
        @endcan
        @can('front_desk.couriers.create')
        <a href="{{ $workspaceRoutes->route('admin.front-desk.couriers.create') }}" class="btn btn-primary btn-md fs-13">
            <i class="ti ti-plus me-1"></i>{{ __('front_desk.couriers.new') }}
        </a>
        @endcan
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ $workspaceRoutes->route('admin.front-desk.couriers.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label fs-13">{{ __('front_desk.actions.search') }}</label>
                <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('front_desk.placeholders.search_couriers') }}">
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
            <div class="col-md-2">
                <label class="form-label fs-13">{{ __('front_desk.fields.courier_type') }}</label>
                <select name="courier_type" class="form-select">
                    <option value="">{{ __('front_desk.filters.all_types') }}</option>
                    @foreach($types as $t)
                    <option value="{{ $t->value }}" @selected(($filters['courier_type'] ?? '') === $t->value)>{{ $t->translatedLabel() }}</option>
                    @endforeach
                </select>
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
                <label class="form-label fs-13">{{ __('front_desk.fields.handover_status') }}</label>
                <select name="handover_status" class="form-select">
                    <option value="">{{ __('front_desk.filters.all_handover_statuses') }}</option>
                    @foreach($handoverStatuses as $hs)
                    <option value="{{ $hs->value }}" @selected(($filters['handover_status'] ?? '') === $hs->value)>{{ $hs->translatedLabel() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fs-13">{{ __('front_desk.fields.recipient_department') }}</label>
                <select name="department_id" class="form-select">
                    <option value="">{{ __('front_desk.filters.all_departments') }}</option>
                    @foreach($departments as $d)
                    <option value="{{ $d->id }}" @selected((string) ($filters['department_id'] ?? '') === (string) $d->id)>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="ti ti-filter me-1"></i>{{ __('front_desk.actions.filter') }}</button>
                <a href="{{ $workspaceRoutes->route('admin.front-desk.couriers.index') }}" class="btn btn-outline-secondary">{{ __('front_desk.actions.reset') }}</a>
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
                        <th>{{ __('front_desk.fields.courier_type') }}</th>
                        <th>{{ __('front_desk.fields.sender_name') }}</th>
                        <th>{{ __('front_desk.fields.recipient_name') }}</th>
                        <th>{{ __('front_desk.fields.received_or_sent_at') }}</th>
                        <th>{{ __('front_desk.fields.status') }}</th>
                        <th class="text-end">{{ __('front_desk.actions.view') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td><x-status-badge :status="$log->direction" size="sm" soft /></td>
                        <td>{{ $log->courier_type?->translatedLabel() }}</td>
                        <td>{{ $log->sender_name ?? __('front_desk.none') }}</td>
                        <td>{{ $log->recipient_name ?? ($log->recipientDepartment?->name ?? __('front_desk.none')) }}</td>
                        <td>{{ $log->received_or_sent_at?->format('d M Y H:i') }}</td>
                        <td><x-status-badge :status="$log->status" size="sm" /></td>
                        <td class="text-end">
                            <a href="{{ $workspaceRoutes->route('admin.front-desk.couriers.show', $log) }}" class="btn btn-sm btn-icon btn-outline-primary" title="{{ __('front_desk.actions.view') }}"><i class="ti ti-eye"></i></a>
                            @can('front_desk.couriers.update')
                            <a href="{{ $workspaceRoutes->route('admin.front-desk.couriers.edit', $log) }}" class="btn btn-sm btn-icon btn-outline-secondary" title="{{ __('front_desk.actions.edit') }}"><i class="ti ti-edit"></i></a>
                            @endcan
                            @can('front_desk.couriers.deliver')
                            @unless($log->isDelivered())
                            <form method="POST" action="{{ $workspaceRoutes->route('admin.front-desk.couriers.mark-delivered', $log) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-icon btn-outline-success" title="{{ __('front_desk.actions.mark_delivered') }}"><i class="ti ti-package-export"></i></button>
                            </form>
                            @endunless
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7"><x-empty-state icon="ti-package" :message="__('front_desk.couriers.none')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">{{ $logs->withQueryString()->links() }}</div>
@endsection
