@extends('layouts.app')
@section('title', __('front_desk.dashboard.title'))

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ __('front_desk.dashboard.title') }}</h4>
        <p class="text-muted mb-0 fs-13">{{ __('front_desk.subtitle') }}</p>
    </div>
    <div class="d-flex gap-2">
        @can('front_desk.visitors.create')
        <a href="{{ $workspaceRoutes->route('admin.front-desk.visitors.create') }}" class="btn btn-primary btn-sm">
            <i class="ti ti-user-plus me-1"></i>{{ __('front_desk.visitors.new') }}
        </a>
        @endcan
        @can('front_desk.calls.create')
        <a href="{{ $workspaceRoutes->route('admin.front-desk.calls.create') }}" class="btn btn-outline-primary btn-sm">
            <i class="ti ti-phone-plus me-1"></i>{{ __('front_desk.calls.new') }}
        </a>
        @endcan
        @can('front_desk.couriers.create')
        <a href="{{ $workspaceRoutes->route('admin.front-desk.couriers.create') }}" class="btn btn-outline-primary btn-sm">
            <i class="ti ti-package me-1"></i>{{ __('front_desk.couriers.new') }}
        </a>
        @endcan
    </div>
</div>

@php
    $cards = [
        ['label' => __('front_desk.dashboard.visitors_inside'), 'value' => $metrics['visitors_inside_count'], 'icon' => 'ti-users', 'color' => 'success', 'route' => 'admin.front-desk.visitors.index'],
        ['label' => __('front_desk.dashboard.visitors_today'), 'value' => $metrics['visitors_today_count'], 'icon' => 'ti-user-check', 'color' => 'primary', 'route' => 'admin.front-desk.visitors.index'],
        ['label' => __('front_desk.dashboard.overdue_visitors'), 'value' => $metrics['overdue_visitors_count'], 'icon' => 'ti-clock-exclamation', 'color' => 'danger', 'route' => 'admin.front-desk.visitors.index'],
        ['label' => __('front_desk.dashboard.calls_today'), 'value' => $metrics['calls_today_count'], 'icon' => 'ti-phone', 'color' => 'info', 'route' => 'admin.front-desk.calls.index'],
        ['label' => __('front_desk.dashboard.pending_call_followups'), 'value' => $metrics['pending_call_followups_count'], 'icon' => 'ti-phone-call', 'color' => 'warning', 'route' => 'admin.front-desk.calls.index'],
        ['label' => __('front_desk.dashboard.couriers_today'), 'value' => $metrics['couriers_today_count'], 'icon' => 'ti-package', 'color' => 'primary', 'route' => 'admin.front-desk.couriers.index'],
        ['label' => __('front_desk.dashboard.pending_couriers'), 'value' => $metrics['pending_couriers_count'], 'icon' => 'ti-truck-delivery', 'color' => 'warning', 'route' => 'admin.front-desk.couriers.index'],
        ['label' => __('front_desk.dashboard.delivered_today'), 'value' => $metrics['delivered_couriers_today'], 'icon' => 'ti-checks', 'color' => 'success', 'route' => 'admin.front-desk.couriers.index'],
    ];
@endphp

<div class="row g-3 mb-3">
    @foreach($cards as $card)
    <div class="col-xl-3 col-md-6">
        <a href="{{ $workspaceRoutes->route($card['route']) }}" class="card h-100 text-decoration-none">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="avatar avatar-lg bg-{{ $card['color'] }}-subtle rounded d-flex align-items-center justify-content-center">
                    <i class="ti {{ $card['icon'] }} fs-3 text-{{ $card['color'] }}"></i>
                </span>
                <div>
                    <h3 class="mb-0 fw-bold">{{ $card['value'] }}</h3>
                    <span class="text-muted fs-13">{{ $card['label'] }}</span>
                </div>
            </div>
        </a>
    </div>
    @endforeach
</div>

{{-- Patient visitor management (Phase 18B) --}}
<div class="row g-3 mb-3">
    <div class="col-xl-3 col-md-6">
        <a href="{{ $workspaceRoutes->route('admin.front-desk.visitors.index', ['quick' => 'patient']) }}" class="card h-100 text-decoration-none">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="avatar avatar-lg bg-primary-subtle rounded d-flex align-items-center justify-content-center"><i class="ti ti-user-heart fs-3 text-primary"></i></span>
                <div><h3 class="mb-0 fw-bold">{{ $metrics['patient_visitors_inside_count'] }}</h3><span class="text-muted fs-13">{{ __('front_desk.dashboard.patient_visitors_inside') }}</span></div>
            </div>
        </a>
    </div>
    <div class="col-xl-3 col-md-6">
        <a href="{{ $workspaceRoutes->route('admin.front-desk.visitors.index', ['quick' => 'facility']) }}" class="card h-100 text-decoration-none">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="avatar avatar-lg bg-info-subtle rounded d-flex align-items-center justify-content-center"><i class="ti ti-building fs-3 text-info"></i></span>
                <div><h3 class="mb-0 fw-bold">{{ $metrics['facility_visitors_inside_count'] }}</h3><span class="text-muted fs-13">{{ __('front_desk.dashboard.facility_visitors_inside') }}</span></div>
            </div>
        </a>
    </div>
    <div class="col-xl-3 col-md-6">
        <a href="{{ $workspaceRoutes->route('admin.front-desk.visitors.index', ['quick' => 'overdue']) }}" class="card h-100 text-decoration-none">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="avatar avatar-lg bg-danger-subtle rounded d-flex align-items-center justify-content-center"><i class="ti ti-clock-exclamation fs-3 text-danger"></i></span>
                <div><h3 class="mb-0 fw-bold">{{ $metrics['overdue_patient_visitors_count'] }}</h3><span class="text-muted fs-13">{{ __('front_desk.dashboard.overdue_patient_visitors') }}</span></div>
            </div>
        </a>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="avatar avatar-lg bg-warning-subtle rounded d-flex align-items-center justify-content-center"><i class="ti ti-building-hospital fs-3 text-warning"></i></span>
                <div><h3 class="mb-0 fw-bold">{{ $metrics['wards_with_visitors'] }}</h3><span class="text-muted fs-13">{{ __('front_desk.dashboard.wards_with_visitors') }}</span></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    {{-- Ward visitor load --}}
    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0 fs-15">{{ __('front_desk.dashboard.ward_visitor_load') }}</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <tbody>
                            @forelse($metrics['top_wards_by_active_visitors'] as $ward)
                            <tr>
                                <td>{{ $ward['name'] }}</td>
                                <td class="text-end"><span class="badge badge-soft-primary">{{ $ward['active_count'] }}</span></td>
                            </tr>
                            @empty
                            <tr><td class="text-center text-muted py-4">{{ __('front_desk.dashboard.no_ward_visitors') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent patient visitors --}}
    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0 fs-15">{{ __('front_desk.dashboard.recent_patient_visitors') }}</h5>
                <a href="{{ $workspaceRoutes->route('admin.front-desk.visitors.index', ['quick' => 'patient']) }}" class="fs-13">{{ __('front_desk.actions.view') }}</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <tbody>
                            @forelse($metrics['recent_patient_visitors'] as $v)
                            <tr>
                                <td>
                                    <div class="fw-medium">{{ $v->visitor_name }}</div>
                                    <small class="text-muted">{{ $v->patient?->patient_number }} · {{ $v->ward?->name }}</small>
                                </td>
                                <td class="text-end">
                                    <x-status-badge :status="$v->status" size="sm" soft />
                                    <div><small class="text-muted">{{ $v->time_in?->format('d M H:i') }}</small></div>
                                </td>
                            </tr>
                            @empty
                            <tr><td class="text-center text-muted py-4">{{ __('front_desk.dashboard.no_recent_patient_visitors') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Callback queue & courier workflow (Phase 18C) --}}
@php
    $workflowCards = [];
    if (auth()->user()?->can('front_desk.calls.followups.view')) {
        $workflowCards = array_merge($workflowCards, [
            ['label' => __('front_desk.dashboard.pending_callbacks'), 'value' => $metrics['pending_callbacks_count'], 'icon' => 'ti-phone-call', 'color' => 'primary', 'url' => $workspaceRoutes->route('admin.front-desk.calls.follow-ups')],
            ['label' => __('front_desk.dashboard.overdue_callbacks'), 'value' => $metrics['overdue_callbacks_count'], 'icon' => 'ti-alarm', 'color' => 'danger', 'url' => $workspaceRoutes->route('admin.front-desk.calls.follow-ups', ['overdue' => 1])],
            ['label' => __('front_desk.dashboard.callbacks_due_today'), 'value' => $metrics['callbacks_due_today_count'], 'icon' => 'ti-calendar-due', 'color' => 'warning', 'url' => $workspaceRoutes->route('admin.front-desk.calls.follow-ups', ['due_today' => 1])],
            ['label' => __('front_desk.dashboard.assigned_to_me'), 'value' => $metrics['assigned_to_me_callbacks_count'], 'icon' => 'ti-user-check', 'color' => 'info', 'url' => $workspaceRoutes->route('admin.front-desk.calls.follow-ups', ['mine' => 1])],
        ]);
    }
    if (auth()->user()?->can('front_desk.couriers.workflow.view')) {
        $workflowCards = array_merge($workflowCards, [
            ['label' => __('front_desk.dashboard.pending_dispatch'), 'value' => $metrics['pending_dispatch_count'], 'icon' => 'ti-package', 'color' => 'primary', 'url' => $workspaceRoutes->route('admin.front-desk.couriers.workflow', ['quick' => 'pending_dispatch'])],
            ['label' => __('front_desk.dashboard.in_transit'), 'value' => $metrics['in_transit_couriers_count'], 'icon' => 'ti-truck', 'color' => 'info', 'url' => $workspaceRoutes->route('admin.front-desk.couriers.workflow', ['quick' => 'in_transit'])],
            ['label' => __('front_desk.dashboard.awaiting_handover'), 'value' => $metrics['awaiting_handover_count'], 'icon' => 'ti-arrows-exchange', 'color' => 'warning', 'url' => $workspaceRoutes->route('admin.front-desk.couriers.workflow', ['quick' => 'awaiting_handover'])],
            ['label' => __('front_desk.dashboard.overdue_couriers'), 'value' => $metrics['overdue_couriers_count'], 'icon' => 'ti-clock-exclamation', 'color' => 'danger', 'url' => $workspaceRoutes->route('admin.front-desk.couriers.workflow', ['quick' => 'overdue'])],
        ]);
    }
@endphp
@if($workflowCards !== [])
<div class="row g-3 mb-3">
    @foreach($workflowCards as $card)
    <div class="col-xl-3 col-md-6">
        <a href="{{ $card['url'] }}" class="card h-100 text-decoration-none">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="avatar avatar-md bg-{{ $card['color'] }}-subtle rounded d-flex align-items-center justify-content-center"><i class="ti {{ $card['icon'] }} fs-4 text-{{ $card['color'] }}"></i></span>
                <div><h4 class="mb-0 fw-bold">{{ $card['value'] }}</h4><span class="text-muted fs-13">{{ $card['label'] }}</span></div>
            </div>
        </a>
    </div>
    @endforeach
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0 fs-15">{{ __('front_desk.dashboard.callback_queue') }}</h5>
                @can('front_desk.calls.followups.view')<a href="{{ $workspaceRoutes->route('admin.front-desk.calls.follow-ups') }}" class="fs-13">{{ __('front_desk.actions.view') }}</a>@endcan
            </div>
            <div class="card-body p-0"><div class="table-responsive"><table class="table table-sm table-hover mb-0"><tbody>
                @forelse($metrics['recent_pending_callbacks'] as $c)
                <tr>
                    <td><div class="fw-medium">{{ $c->category?->translatedLabel() }}</div><small class="text-muted">{{ $c->assignedFollowUpUser?->full_name }}</small></td>
                    <td class="text-end"><small class="text-muted">{{ $c->follow_up_due_at?->format('d M H:i') }}</small>@if($c->isOverdueCallback())<span class="badge bg-danger ms-1">{{ __('front_desk.filters.overdue') }}</span>@endif</td>
                </tr>
                @empty
                <tr><td class="text-center text-muted py-4">{{ __('front_desk.dashboard.no_pending_callbacks') }}</td></tr>
                @endforelse
            </tbody></table></div></div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0 fs-15">{{ __('front_desk.dashboard.recent_handoffs') }}</h5>
                @can('front_desk.couriers.workflow.view')<a href="{{ $workspaceRoutes->route('admin.front-desk.couriers.workflow') }}" class="fs-13">{{ __('front_desk.actions.view') }}</a>@endcan
            </div>
            <div class="card-body p-0"><div class="table-responsive"><table class="table table-sm table-hover mb-0"><tbody>
                @forelse($metrics['recent_courier_handoffs'] as $h)
                <tr>
                    <td><x-status-badge :status="$h->action" size="sm" soft /> <small class="text-muted">{{ $h->createdBy?->full_name }}</small></td>
                    <td class="text-end"><small class="text-muted">{{ $h->action_at?->format('d M H:i') }}</small></td>
                </tr>
                @empty
                <tr><td class="text-center text-muted py-4">{{ __('front_desk.dashboard.no_recent_handoffs') }}</td></tr>
                @endforelse
            </tbody></table></div></div>
        </div>
    </div>
</div>
@endif

{{-- Handover / lost & found / incident desk (Phase 18E) --}}
@php
    $facilityCards = [];
    if (auth()->user()?->can('front_desk.handovers.view')) {
        $facilityCards[] = ['label' => __('front_desk.dashboard.pending_handovers'), 'value' => $metrics['pending_handovers_count'], 'icon' => 'ti-clipboard-list', 'color' => 'primary', 'url' => $workspaceRoutes->route('admin.front-desk.handovers.index')];
    }
    if (auth()->user()?->can('front_desk.lost_found.view')) {
        $facilityCards[] = ['label' => __('front_desk.dashboard.unclaimed_lost_found'), 'value' => $metrics['unclaimed_lost_found_count'], 'icon' => 'ti-briefcase', 'color' => 'warning', 'url' => $workspaceRoutes->route('admin.front-desk.lost-found.index', ['unclaimed' => 1])];
    }
    if (auth()->user()?->can('front_desk.incidents.view')) {
        $facilityCards[] = ['label' => __('front_desk.dashboard.open_incidents'), 'value' => $metrics['open_incidents_count'], 'icon' => 'ti-alert-octagon', 'color' => 'info', 'url' => $workspaceRoutes->route('admin.front-desk.incidents.index', ['open' => 1])];
        $facilityCards[] = ['label' => __('front_desk.dashboard.critical_incidents'), 'value' => $metrics['critical_open_incidents_count'], 'icon' => 'ti-urgent', 'color' => 'danger', 'url' => $workspaceRoutes->route('admin.front-desk.incidents.index', ['critical' => 1])];
    }
@endphp
@if($facilityCards !== [])
<div class="row g-3 mb-3">
    @foreach($facilityCards as $card)
    <div class="col-xl-3 col-md-6">
        <a href="{{ $card['url'] }}" class="card h-100 text-decoration-none">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="avatar avatar-md bg-{{ $card['color'] }}-subtle rounded d-flex align-items-center justify-content-center"><i class="ti {{ $card['icon'] }} fs-4 text-{{ $card['color'] }}"></i></span>
                <div><h4 class="mb-0 fw-bold">{{ $card['value'] }}</h4><span class="text-muted fs-13">{{ $card['label'] }}</span></div>
            </div>
        </a>
    </div>
    @endforeach
</div>
@endif

<div class="row g-3">
    {{-- Recent visitors --}}
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0 fs-15">{{ __('front_desk.dashboard.recent_visitors') }}</h5>
                <a href="{{ $workspaceRoutes->route('admin.front-desk.visitors.index') }}" class="fs-13">{{ __('front_desk.actions.view') }}</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <tbody>
                            @forelse($metrics['recent_visitors'] as $v)
                            <tr>
                                <td>
                                    <div class="fw-medium">{{ $v->visitor_name }}</div>
                                    <small class="text-muted">{{ $v->time_in?->format('d M H:i') }}</small>
                                </td>
                                <td class="text-end"><x-status-badge :status="$v->status" size="sm" soft /></td>
                            </tr>
                            @empty
                            <tr><td class="text-center text-muted py-4">{{ __('front_desk.dashboard.no_recent_visitors') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent calls --}}
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0 fs-15">{{ __('front_desk.dashboard.recent_calls') }}</h5>
                <a href="{{ $workspaceRoutes->route('admin.front-desk.calls.index') }}" class="fs-13">{{ __('front_desk.actions.view') }}</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <tbody>
                            @forelse($metrics['recent_calls'] as $c)
                            <tr>
                                <td>
                                    <div class="fw-medium">{{ $c->category?->translatedLabel() }}</div>
                                    <small class="text-muted">{{ $c->started_at?->format('d M H:i') }}</small>
                                </td>
                                <td class="text-end"><x-status-badge :status="$c->direction" size="sm" soft /></td>
                            </tr>
                            @empty
                            <tr><td class="text-center text-muted py-4">{{ __('front_desk.dashboard.no_recent_calls') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent couriers --}}
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0 fs-15">{{ __('front_desk.dashboard.recent_couriers') }}</h5>
                <a href="{{ $workspaceRoutes->route('admin.front-desk.couriers.index') }}" class="fs-13">{{ __('front_desk.actions.view') }}</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <tbody>
                            @forelse($metrics['recent_couriers'] as $co)
                            <tr>
                                <td>
                                    <div class="fw-medium">{{ $co->courier_type?->translatedLabel() }}</div>
                                    <small class="text-muted">{{ $co->received_or_sent_at?->format('d M H:i') }}</small>
                                </td>
                                <td class="text-end"><x-status-badge :status="$co->status" size="sm" soft /></td>
                            </tr>
                            @empty
                            <tr><td class="text-center text-muted py-4">{{ __('front_desk.dashboard.no_recent_couriers') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
