@extends('layouts.app')
@section('title', __('front_desk.reports.title'))

@php
    $s = $payload['summary'];
    $v = $payload['visitors'];
    $c = $payload['calls'];
    $cb = $payload['callbacks'];
    $co = $payload['couriers'];
    $wf = $payload['workflow'];
    $fac = $payload['facility'];
    $exportBase = request()->only(['date_from', 'date_to', 'department_id', 'ward_id', 'visitor_context', 'visitor_status', 'call_direction', 'call_category', 'call_outcome', 'follow_up_status', 'courier_direction', 'courier_type', 'courier_status', 'handover_status']);
    $exportUrl = fn ($type) => $workspaceRoutes->route('admin.front-desk.reports.export', array_merge($exportBase, ['type' => $type]));
    $cards = [
        'total_visitors', 'patient_visitors', 'facility_visitors', 'currently_inside', 'overdue_visitors',
        'total_calls', 'incoming_calls', 'outgoing_calls', 'pending_callbacks', 'overdue_callbacks',
        'total_couriers', 'pending_couriers', 'in_transit_couriers', 'delivered_couriers', 'returned_couriers',
    ];
@endphp

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ __('front_desk.reports.title') }}</h4>
        <p class="text-muted mb-0 fs-13">{{ __('front_desk.reports.subtitle') }}</p>
    </div>
    @can('front_desk.reports.export')
    <a href="{{ $exportUrl('summary') }}" class="btn btn-outline-primary btn-md fs-13"><i class="ti ti-download me-1"></i>{{ __('front_desk.reports.export_csv') }}</a>
    @endcan
</div>

{{-- Filters --}}
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ $workspaceRoutes->route('admin.front-desk.reports.index') }}" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label fs-13">{{ __('front_desk.filters.date_from') }}</label>
                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] }}">
            </div>
            <div class="col-md-2">
                <label class="form-label fs-13">{{ __('front_desk.filters.date_to') }}</label>
                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] }}">
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
            <div class="col-md-3">
                <label class="form-label fs-13">{{ __('front_desk.fields.ward') }}</label>
                <select name="ward_id" class="form-select">
                    <option value="">{{ __('front_desk.filters.all_departments') }}</option>
                    @foreach($wards as $w)
                    <option value="{{ $w->id }}" @selected((string) ($filters['ward_id'] ?? '') === (string) $w->id)>{{ $w->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="ti ti-filter me-1"></i>{{ __('front_desk.reports.apply_filters') }}</button>
                <a href="{{ $workspaceRoutes->route('admin.front-desk.reports.index') }}" class="btn btn-outline-secondary">{{ __('front_desk.reports.reset') }}</a>
            </div>
        </form>
    </div>
</div>

{{-- Summary cards --}}
<div class="row g-2 mb-3">
    @foreach($cards as $key)
    <div class="col-xl-2 col-md-3 col-6">
        <div class="card h-100 mb-0"><div class="card-body py-3">
            <h4 class="mb-0 fw-bold">{{ $s[$key] }}</h4>
            <span class="text-muted fs-12">{{ __('front_desk.reports.cards.' . $key) }}</span>
        </div></div>
    </div>
    @endforeach
</div>

{{-- Tabs --}}
<ul class="nav nav-tabs mb-3" role="tablist">
    @foreach(['overview', 'visitors', 'calls', 'callbacks', 'couriers', 'courier_workflow', 'facility'] as $i => $tab)
    <li class="nav-item"><button class="nav-link {{ $i === 0 ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-{{ $tab }}" type="button">{{ __('front_desk.reports.tabs.' . $tab) }}</button></li>
    @endforeach
</ul>

<div class="tab-content">
    {{-- Overview --}}
    <div class="tab-pane fade show active" id="tab-overview">
        <div class="row g-3">
            <div class="col-lg-4">@include('admin.front-desk.reports._metric-table', ['title' => __('front_desk.reports.sections.visitors_by_context'), 'rows' => $v['visitors_by_context']])</div>
            <div class="col-lg-4">@include('admin.front-desk.reports._metric-table', ['title' => __('front_desk.reports.sections.calls_by_direction'), 'rows' => $c['calls_by_direction']])</div>
            <div class="col-lg-4">@include('admin.front-desk.reports._metric-table', ['title' => __('front_desk.reports.sections.couriers_by_status'), 'rows' => $co['couriers_by_status']])</div>
        </div>
    </div>

    {{-- Visitors --}}
    <div class="tab-pane fade" id="tab-visitors">
        @can('front_desk.reports.export')
        <div class="mb-2 d-flex gap-2 flex-wrap">
            <a href="{{ $exportUrl('visitors') }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-download me-1"></i>{{ __('front_desk.reports.export_types.visitors') }}</a>
            <a href="{{ $exportUrl('visitor_currently_inside') }}" class="btn btn-sm btn-outline-primary">{{ __('front_desk.reports.export_types.visitor_currently_inside') }}</a>
            <a href="{{ $exportUrl('visitor_overdue') }}" class="btn btn-sm btn-outline-primary">{{ __('front_desk.reports.export_types.visitor_overdue') }}</a>
        </div>
        @endcan
        <div class="row g-3">
            <div class="col-lg-4">@include('admin.front-desk.reports._metric-table', ['title' => __('front_desk.reports.sections.visitors_by_context'), 'rows' => $v['visitors_by_context']])</div>
            <div class="col-lg-4">@include('admin.front-desk.reports._metric-table', ['title' => __('front_desk.reports.sections.visitors_by_status'), 'rows' => $v['visitors_by_status']])</div>
            <div class="col-lg-4">@include('admin.front-desk.reports._metric-table', ['title' => __('front_desk.reports.sections.visitors_by_ward'), 'rows' => $v['visitors_by_ward']])</div>
            <div class="col-lg-4">@include('admin.front-desk.reports._metric-table', ['title' => __('front_desk.reports.sections.visitors_by_department'), 'rows' => $v['visitors_by_department']])</div>
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-header"><h6 class="mb-0 fs-14">{{ __('front_desk.reports.top_patients') }}</h6></div>
                    <div class="card-body p-0"><div class="table-responsive"><table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th>{{ __('front_desk.fields.patient') }}</th><th class="text-end">{{ __('front_desk.reports.count') }}</th></tr></thead>
                        <tbody>
                            @forelse($v['top_patients_by_visitor_count'] as $p)
                            <tr><td>{{ $p['patient_number'] }} — {{ $p['patient_name'] }}</td><td class="text-end"><span class="badge badge-soft-primary">{{ $p['count'] }}</span></td></tr>
                            @empty
                            <tr><td colspan="2" class="text-center text-muted py-3">{{ __('front_desk.reports.no_report_data') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table></div></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Calls --}}
    <div class="tab-pane fade" id="tab-calls">
        @can('front_desk.reports.export')
        <div class="mb-2"><a href="{{ $exportUrl('calls') }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-download me-1"></i>{{ __('front_desk.reports.export_types.calls') }}</a></div>
        @endcan
        <div class="row g-3">
            <div class="col-lg-4">@include('admin.front-desk.reports._metric-table', ['title' => __('front_desk.reports.sections.calls_by_direction'), 'rows' => $c['calls_by_direction']])</div>
            <div class="col-lg-4">@include('admin.front-desk.reports._metric-table', ['title' => __('front_desk.reports.sections.calls_by_category'), 'rows' => $c['calls_by_category']])</div>
            <div class="col-lg-4">@include('admin.front-desk.reports._metric-table', ['title' => __('front_desk.reports.sections.calls_by_outcome'), 'rows' => $c['calls_by_outcome']])</div>
            <div class="col-lg-6">@include('admin.front-desk.reports._metric-table', ['title' => __('front_desk.reports.sections.calls_by_department'), 'rows' => $c['calls_by_department']])</div>
            <div class="col-lg-6">@include('admin.front-desk.reports._metric-table', ['title' => __('front_desk.reports.sections.calls_by_handler'), 'rows' => $c['calls_by_handler']])</div>
        </div>
    </div>

    {{-- Callbacks --}}
    <div class="tab-pane fade" id="tab-callbacks">
        @can('front_desk.reports.export')
        <div class="mb-2"><a href="{{ $exportUrl('callbacks') }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-download me-1"></i>{{ __('front_desk.reports.export_types.callbacks') }}</a></div>
        @endcan
        <div class="row g-3">
            <div class="col-lg-3"><div class="card h-100 mb-0"><div class="card-body"><h4 class="fw-bold mb-0">{{ $cb['pending_callbacks'] }}</h4><span class="text-muted fs-13">{{ __('front_desk.dashboard.pending_callbacks') }}</span></div></div></div>
            <div class="col-lg-3"><div class="card h-100 mb-0"><div class="card-body"><h4 class="fw-bold mb-0">{{ $cb['overdue_callbacks'] }}</h4><span class="text-muted fs-13">{{ __('front_desk.dashboard.overdue_callbacks') }}</span></div></div></div>
            <div class="col-lg-3"><div class="card h-100 mb-0"><div class="card-body"><h4 class="fw-bold mb-0">{{ $cb['callbacks_due_today'] }}</h4><span class="text-muted fs-13">{{ __('front_desk.dashboard.callbacks_due_today') }}</span></div></div></div>
            <div class="col-lg-3"><div class="card h-100 mb-0"><div class="card-body"><h4 class="fw-bold mb-0">{{ $cb['callback_completion_rate'] }}%</h4><span class="text-muted fs-13">{{ __('front_desk.reports.completion_rate') }}</span></div></div></div>
            <div class="col-lg-6">@include('admin.front-desk.reports._metric-table', ['title' => __('front_desk.reports.sections.callbacks_by_status'), 'rows' => $cb['callbacks_by_status']])</div>
            <div class="col-lg-6">@include('admin.front-desk.reports._metric-table', ['title' => __('front_desk.reports.sections.callbacks_by_assignee'), 'rows' => $cb['callbacks_by_assignee']])</div>
        </div>
    </div>

    {{-- Couriers --}}
    <div class="tab-pane fade" id="tab-couriers">
        @can('front_desk.reports.export')
        <div class="mb-2"><a href="{{ $exportUrl('couriers') }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-download me-1"></i>{{ __('front_desk.reports.export_types.couriers') }}</a></div>
        @endcan
        <div class="row g-3">
            <div class="col-lg-4">@include('admin.front-desk.reports._metric-table', ['title' => __('front_desk.reports.sections.couriers_by_direction'), 'rows' => $co['couriers_by_direction']])</div>
            <div class="col-lg-4">@include('admin.front-desk.reports._metric-table', ['title' => __('front_desk.reports.sections.couriers_by_type'), 'rows' => $co['couriers_by_type']])</div>
            <div class="col-lg-4">@include('admin.front-desk.reports._metric-table', ['title' => __('front_desk.reports.sections.couriers_by_status'), 'rows' => $co['couriers_by_status']])</div>
            <div class="col-lg-6">@include('admin.front-desk.reports._metric-table', ['title' => __('front_desk.reports.sections.couriers_by_handover_status'), 'rows' => $co['couriers_by_handover_status']])</div>
            <div class="col-lg-6">@include('admin.front-desk.reports._metric-table', ['title' => __('front_desk.reports.sections.couriers_by_department'), 'rows' => $co['couriers_by_department']])</div>
        </div>
    </div>

    {{-- Courier workflow --}}
    <div class="tab-pane fade" id="tab-courier_workflow">
        @can('front_desk.reports.export')
        <div class="mb-2"><a href="{{ $exportUrl('courier_workflow') }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-download me-1"></i>{{ __('front_desk.reports.export_types.courier_workflow') }}</a></div>
        @endcan
        <div class="row g-3">
            <div class="col-lg-4">@include('admin.front-desk.reports._metric-table', ['title' => __('front_desk.reports.sections.handoffs_by_action'), 'rows' => $wf['handoffs_by_action']])</div>
            <div class="col-lg-4">@include('admin.front-desk.reports._metric-table', ['title' => __('front_desk.reports.sections.handoffs_by_department'), 'rows' => $wf['handoffs_by_department']])</div>
            <div class="col-lg-4">@include('admin.front-desk.reports._metric-table', ['title' => __('front_desk.reports.sections.handoffs_by_user'), 'rows' => $wf['handoffs_by_user']])</div>
        </div>
    </div>

    {{-- Facility (Phase 18E) --}}
    <div class="tab-pane fade" id="tab-facility">
        @can('front_desk.reports.export')
        <div class="mb-2 d-flex gap-2 flex-wrap">
            <a href="{{ $exportUrl('handovers') }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-download me-1"></i>{{ __('front_desk.reports.export_types.handovers') }}</a>
            <a href="{{ $exportUrl('lost_found') }}" class="btn btn-sm btn-outline-primary">{{ __('front_desk.reports.export_types.lost_found') }}</a>
            <a href="{{ $exportUrl('incidents') }}" class="btn btn-sm btn-outline-primary">{{ __('front_desk.reports.export_types.incidents') }}</a>
        </div>
        @endcan
        <div class="row g-3">
            <div class="col-lg-4">@include('admin.front-desk.reports._metric-table', ['title' => __('front_desk.reports.sections.handovers_by_status'), 'rows' => $fac['handovers_by_status']])</div>
            <div class="col-lg-4">@include('admin.front-desk.reports._metric-table', ['title' => __('front_desk.reports.sections.lost_found_by_status'), 'rows' => $fac['lost_found_by_status']])</div>
            <div class="col-lg-4">@include('admin.front-desk.reports._metric-table', ['title' => __('front_desk.reports.sections.lost_found_by_category'), 'rows' => $fac['lost_found_by_category']])</div>
            <div class="col-lg-4">@include('admin.front-desk.reports._metric-table', ['title' => __('front_desk.reports.sections.incidents_by_type'), 'rows' => $fac['incidents_by_type']])</div>
            <div class="col-lg-4">@include('admin.front-desk.reports._metric-table', ['title' => __('front_desk.reports.sections.incidents_by_severity'), 'rows' => $fac['incidents_by_severity']])</div>
            <div class="col-lg-4">@include('admin.front-desk.reports._metric-table', ['title' => __('front_desk.reports.sections.incidents_by_status'), 'rows' => $fac['incidents_by_status']])</div>
        </div>
    </div>
</div>
@endsection
