@extends('layouts.app')
@section('title', __('front_desk.handovers.details'))
@section('content')
<div class="d-flex align-items-center mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ $log->shift_name ?: __('front_desk.handovers.singular') }} — {{ $log->shift_date?->format('d M Y') }}</h4>
        <div class="mt-1"><x-status-badge :status="$log->status" size="sm" /></div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @can('front_desk.handovers.submit')
        @if($log->isDraft())
        <form method="POST" action="{{ $workspaceRoutes->route('admin.front-desk.handovers.submit', $log) }}">@csrf<button type="submit" class="btn btn-primary"><i class="ti ti-send me-1"></i>{{ __('front_desk.actions.submit') }}</button></form>
        @endif
        @endcan
        @can('front_desk.handovers.accept')
        @if($log->status->value === 'submitted')
        <form method="POST" action="{{ $workspaceRoutes->route('admin.front-desk.handovers.accept', $log) }}">@csrf<button type="submit" class="btn btn-success"><i class="ti ti-check me-1"></i>{{ __('front_desk.actions.accept') }}</button></form>
        @endif
        @endcan
        @can('front_desk.handovers.update')
        @if($log->isDraft())<a href="{{ $workspaceRoutes->route('admin.front-desk.handovers.edit', $log) }}" class="btn btn-outline-primary"><i class="ti ti-edit me-1"></i>{{ __('front_desk.actions.edit') }}</a>@endif
        @endcan
        @can('front_desk.handovers.cancel')
        @unless($log->isCompleted())
        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal"><i class="ti ti-x me-1"></i>{{ __('front_desk.actions.cancel') }}</button>
        @endunless
        @endcan
        <a href="{{ $workspaceRoutes->route('admin.front-desk.handovers.index') }}" class="btn btn-outline-secondary"><i class="ti ti-arrow-left me-1"></i>{{ __('front_desk.actions.back') }}</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h5 class="mb-0 fs-15">{{ __('front_desk.handovers.section_staff') }}</h5></div>
            <div class="card-body"><dl class="row mb-0">
                <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.outgoing_user') }}</dt><dd class="col-sm-8">{{ $log->outgoingUser?->full_name ?? __('front_desk.none') }}</dd>
                <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.incoming_user') }}</dt><dd class="col-sm-8">{{ $log->incomingUser?->full_name ?? __('front_desk.none') }}</dd>
                <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.department') }}</dt><dd class="col-sm-8">{{ $log->department?->name ?? __('front_desk.none') }}</dd>
                <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.started_at') }}</dt><dd class="col-sm-8">{{ $log->handover_started_at?->format('d M Y H:i') }}</dd>
                <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.assigned_to') }}</dt><dd class="col-sm-8">{{ $log->acceptedBy?->full_name ?? __('front_desk.none') }}</dd>
            </dl>
            @if($log->summary_notes)<hr><div class="text-muted fs-13 mb-1">{{ __('front_desk.handovers.section_notes') }}</div><p class="mb-0">{{ $log->summary_notes }}</p>@endif
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h5 class="mb-0 fs-15">{{ __('front_desk.handovers.section_snapshot') }}</h5></div>
            <div class="card-body"><dl class="row mb-0">
                <dt class="col-7 fw-normal text-muted">{{ __('front_desk.dashboard.visitors_inside') }}</dt><dd class="col-5 text-end">{{ $log->visitors_inside_count }}</dd>
                <dt class="col-7 fw-normal text-muted">{{ __('front_desk.dashboard.pending_callbacks') }}</dt><dd class="col-5 text-end">{{ $log->pending_callbacks_count }}</dd>
                <dt class="col-7 fw-normal text-muted">{{ __('front_desk.dashboard.pending_couriers') }}</dt><dd class="col-5 text-end">{{ $log->pending_couriers_count }}</dd>
                <dt class="col-7 fw-normal text-muted">{{ __('front_desk.dashboard.open_incidents') }}</dt><dd class="col-5 text-end">{{ $log->open_incidents_count }}</dd>
                <dt class="col-7 fw-normal text-muted">{{ __('front_desk.dashboard.unclaimed_lost_found') }}</dt><dd class="col-5 text-end">{{ $log->lost_found_unclaimed_count }}</dd>
            </dl></div>
        </div>
    </div>
</div>

@can('front_desk.handovers.cancel')
@unless($log->isCompleted())
<div class="modal fade" id="cancelModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ $workspaceRoutes->route('admin.front-desk.handovers.cancel', $log) }}">@csrf
        <div class="modal-header"><h5 class="modal-title">{{ __('front_desk.actions.cancel') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><label class="form-label">{{ __('front_desk.fields.reason') }}</label><textarea name="reason" class="form-control" rows="2"></textarea></div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('front_desk.actions.back') }}</button><button type="submit" class="btn btn-danger">{{ __('front_desk.actions.cancel') }}</button></div>
    </form>
</div></div></div>
@endunless
@endcan
@endsection
