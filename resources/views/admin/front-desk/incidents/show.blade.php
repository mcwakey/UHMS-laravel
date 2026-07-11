@extends('layouts.app')
@section('title', __('front_desk.incidents.details'))
@section('content')
<div class="d-flex align-items-center mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ $log->incident_number ?? __('front_desk.incidents.singular') }} — {{ $log->incident_type?->translatedLabel() }}</h4>
        <div class="mt-1"><x-status-badge :status="$log->severity" size="sm" /> <x-status-badge :status="$log->status" size="sm" soft /></div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @can('front_desk.incidents.assign')
        @unless($log->isResolved())<button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#assignModal">{{ __('front_desk.actions.assign') }}</button>@endunless
        @endcan
        @can('front_desk.incidents.escalate')
        @if($log->isOpen())<button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#escalateModal">{{ __('front_desk.actions.escalate') }}</button>@endif
        @endcan
        @can('front_desk.incidents.resolve')
        @if($log->isOpen())<button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#resolveModal">{{ __('front_desk.actions.resolve') }}</button>@endif
        @endcan
        @can('front_desk.incidents.update')
        <a href="{{ route('admin.front-desk.incidents.edit', $log) }}" class="btn btn-outline-secondary"><i class="ti ti-edit me-1"></i>{{ __('front_desk.actions.edit') }}</a>
        @endcan
        @can('front_desk.incidents.cancel')
        @if($log->isOpen())<button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal"><i class="ti ti-x me-1"></i>{{ __('front_desk.actions.cancel') }}</button>@endif
        @endcan
        <a href="{{ route('admin.front-desk.incidents.index') }}" class="btn btn-outline-secondary"><i class="ti ti-arrow-left me-1"></i>{{ __('front_desk.actions.back') }}</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7"><div class="card"><div class="card-header"><h5 class="mb-0 fs-15">{{ __('front_desk.incidents.section_incident') }}</h5></div>
        <div class="card-body"><dl class="row mb-0">
            <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.location') }}</dt><dd class="col-sm-8">{{ $log->location ?? __('front_desk.none') }}</dd>
            <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.department') }}</dt><dd class="col-sm-8">{{ $log->department?->name ?? __('front_desk.none') }}</dd>
            <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.reporter_name') }}</dt><dd class="col-sm-8">{{ $log->reported_by_name ?? __('front_desk.none') }}</dd>
            <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.reporter_phone') }}</dt><dd class="col-sm-8">{{ $log->maskedReporterPhone() ?? __('front_desk.none') }}</dd>
            <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.started_at') }}</dt><dd class="col-sm-8">{{ $log->reported_at?->format('d M Y H:i') }}</dd>
        </dl>
        <hr><div class="text-muted fs-13 mb-1">{{ __('front_desk.fields.description') }}</div><p class="mb-0">{{ $log->description }}</p>
        @if($log->action_taken)<hr><div class="text-muted fs-13 mb-1">{{ __('front_desk.fields.action_taken') }}</div><p class="mb-0">{{ $log->action_taken }}</p>@endif
        @if($log->resolution_note)<hr><div class="text-muted fs-13 mb-1">{{ __('front_desk.fields.resolution_note') }}</div><p class="mb-0">{{ $log->resolution_note }}</p>@endif
        </div></div></div>

    <div class="col-lg-5">
        <div class="card"><div class="card-header"><h5 class="mb-0 fs-15">{{ __('front_desk.incidents.section_handling') }}</h5></div>
            <div class="card-body"><dl class="row mb-0">
                <dt class="col-sm-6 text-muted fw-normal">{{ __('front_desk.fields.assigned_to') }}</dt><dd class="col-sm-6">{{ $log->assignedToUser?->full_name ?? __('front_desk.none') }}</dd>
                <dt class="col-sm-6 text-muted fw-normal">{{ __('front_desk.fields.escalated_to') }}</dt><dd class="col-sm-6">{{ $log->escalatedToUser?->full_name ?? __('front_desk.none') }}</dd>
                <dt class="col-sm-6 text-muted fw-normal">{{ __('front_desk.fields.resolved_by') }}</dt><dd class="col-sm-6">{{ $log->resolvedBy?->full_name ?? __('front_desk.none') }}</dd>
            </dl></div>
        </div>
        <div class="card"><div class="card-header"><h5 class="mb-0 fs-15">{{ __('front_desk.incidents.section_links') }}</h5></div>
            <div class="card-body"><dl class="row mb-0">
                <dt class="col-sm-5 text-muted fw-normal">{{ __('front_desk.fields.related_visitor') }}</dt><dd class="col-sm-7">{{ $log->relatedVisitorLog?->visitor_name ?? __('front_desk.none') }}</dd>
                <dt class="col-sm-5 text-muted fw-normal">{{ __('front_desk.fields.related_call') }}</dt><dd class="col-sm-7">{{ $log->relatedCallLog?->category?->translatedLabel() ?? __('front_desk.none') }}</dd>
                <dt class="col-sm-5 text-muted fw-normal">{{ __('front_desk.fields.related_courier') }}</dt><dd class="col-sm-7">{{ $log->relatedCourierLog?->tracking_number ?? __('front_desk.none') }}</dd>
            </dl></div>
        </div>
    </div>
</div>

@can('front_desk.incidents.assign')
@unless($log->isResolved())
<div class="modal fade" id="assignModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="POST" action="{{ route('admin.front-desk.incidents.assign', $log) }}">@csrf
    <div class="modal-header"><h5 class="modal-title">{{ __('front_desk.actions.assign') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><label class="form-label">{{ __('front_desk.fields.assigned_to') }}</label>
        <select name="assigned_to_user_id" class="form-select select2" required><option value="">{{ __('front_desk.none') }}</option>@foreach($users as $u)<option value="{{ $u->id }}" @selected($log->assigned_to_user_id === $u->id)>{{ $u->full_name }}</option>@endforeach</select></div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('front_desk.actions.cancel') }}</button><button type="submit" class="btn btn-primary">{{ __('front_desk.actions.assign') }}</button></div>
</form></div></div></div>
@endunless
@endcan

@can('front_desk.incidents.escalate')
@if($log->isOpen())
<div class="modal fade" id="escalateModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="POST" action="{{ route('admin.front-desk.incidents.escalate', $log) }}">@csrf
    <div class="modal-header"><h5 class="modal-title">{{ __('front_desk.actions.escalate') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-3"><label class="form-label">{{ __('front_desk.fields.escalated_to') }}</label><select name="escalated_to_user_id" class="form-select select2" required><option value="">{{ __('front_desk.none') }}</option>@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->full_name }}</option>@endforeach</select></div>
        <div class="mb-1"><label class="form-label">{{ __('front_desk.fields.note') }}</label><textarea name="note" class="form-control" rows="2"></textarea></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('front_desk.actions.cancel') }}</button><button type="submit" class="btn btn-danger">{{ __('front_desk.actions.escalate') }}</button></div>
</form></div></div></div>
@endif
@endcan

@can('front_desk.incidents.resolve')
@if($log->isOpen())
<div class="modal fade" id="resolveModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="POST" action="{{ route('admin.front-desk.incidents.resolve', $log) }}">@csrf
    <div class="modal-header"><h5 class="modal-title">{{ __('front_desk.actions.resolve') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><label class="form-label">{{ __('front_desk.fields.resolution_note') }}</label><textarea name="resolution_note" class="form-control" rows="2"></textarea></div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('front_desk.actions.cancel') }}</button><button type="submit" class="btn btn-success">{{ __('front_desk.actions.resolve') }}</button></div>
</form></div></div></div>
@endif
@endcan

@can('front_desk.incidents.cancel')
@if($log->isOpen())
<div class="modal fade" id="cancelModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="POST" action="{{ route('admin.front-desk.incidents.cancel', $log) }}">@csrf
    <div class="modal-header"><h5 class="modal-title">{{ __('front_desk.actions.cancel') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><label class="form-label">{{ __('front_desk.fields.reason') }}</label><textarea name="reason" class="form-control" rows="2"></textarea></div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('front_desk.actions.back') }}</button><button type="submit" class="btn btn-danger">{{ __('front_desk.actions.cancel') }}</button></div>
</form></div></div></div>
@endif
@endcan
@endsection
