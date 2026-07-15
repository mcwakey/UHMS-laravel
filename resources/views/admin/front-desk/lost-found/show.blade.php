@extends('layouts.app')
@section('title', __('front_desk.lost_found.details'))
@section('content')
<div class="d-flex align-items-center mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ $log->reference_number ?? __('front_desk.lost_found.singular') }}</h4>
        <div class="mt-1"><x-status-badge :status="$log->item_status" size="sm" /> <x-status-badge :status="$log->item_category" size="sm" soft /></div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @can('front_desk.lost_found.claim')
        @if($log->isUnclaimed())<button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#claimModal"><i class="ti ti-hand-finger me-1"></i>{{ __('front_desk.actions.claim') }}</button>@endif
        @endcan
        @can('front_desk.lost_found.release')
        @unless($log->isClosed())<button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#releaseModal"><i class="ti ti-package-export me-1"></i>{{ __('front_desk.actions.release') }}</button>@endunless
        @endcan
        @can('front_desk.lost_found.update')
        <a href="{{ $workspaceRoutes->route('admin.front-desk.lost-found.edit', $log) }}" class="btn btn-outline-secondary"><i class="ti ti-edit me-1"></i>{{ __('front_desk.actions.edit') }}</a>
        @endcan
        @can('front_desk.lost_found.cancel')
        @unless($log->isClosed())<button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal"><i class="ti ti-x me-1"></i>{{ __('front_desk.actions.cancel') }}</button>@endunless
        @endcan
        <a href="{{ $workspaceRoutes->route('admin.front-desk.lost-found.index') }}" class="btn btn-outline-secondary"><i class="ti ti-arrow-left me-1"></i>{{ __('front_desk.actions.back') }}</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7"><div class="card"><div class="card-header"><h5 class="mb-0 fs-15">{{ __('front_desk.lost_found.section_item') }}</h5></div>
        <div class="card-body"><dl class="row mb-0">
            <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.item_description') }}</dt><dd class="col-sm-8">{{ $log->item_description }}</dd>
            <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.found_location') }}</dt><dd class="col-sm-8">{{ $log->found_location ?? __('front_desk.none') }}</dd>
            <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.stored_location') }}</dt><dd class="col-sm-8">{{ $log->stored_location ?? __('front_desk.none') }}</dd>
            <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.found_by_name') }}</dt><dd class="col-sm-8">{{ $log->found_by_name ?? __('front_desk.none') }}</dd>
            <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.reported_by_name') }}</dt><dd class="col-sm-8">{{ $log->reported_by_name ?? __('front_desk.none') }}</dd>
            <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.reported_by_phone') }}</dt><dd class="col-sm-8">{{ $log->maskedReportedPhone() ?? __('front_desk.none') }}</dd>
            <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.found_or_reported_at') }}</dt><dd class="col-sm-8">{{ $log->found_or_reported_at?->format('d M Y H:i') }}</dd>
        </dl>
        @if($log->notes)<hr><div class="text-muted fs-13 mb-1">{{ __('front_desk.fields.notes') }}</div><p class="mb-0">{{ $log->notes }}</p>@endif
        </div></div></div>
    <div class="col-lg-5"><div class="card"><div class="card-header"><h5 class="mb-0 fs-15">{{ __('front_desk.lost_found.section_claim') }}</h5></div>
        <div class="card-body"><dl class="row mb-0">
            <dt class="col-sm-6 text-muted fw-normal">{{ __('front_desk.fields.claimed_by_name') }}</dt><dd class="col-sm-6">{{ $log->claimed_by_name ?? __('front_desk.none') }}</dd>
            <dt class="col-sm-6 text-muted fw-normal">{{ __('front_desk.fields.claimed_by_phone') }}</dt><dd class="col-sm-6">{{ $log->maskedClaimedPhone() ?? __('front_desk.none') }}</dd>
            <dt class="col-sm-6 text-muted fw-normal">{{ __('front_desk.fields.assigned_to') }}</dt><dd class="col-sm-6">{{ $log->claimVerifiedBy?->full_name ?? __('front_desk.none') }}</dd>
            <dt class="col-sm-6 text-muted fw-normal">{{ __('front_desk.fields.resolved_by') }}</dt><dd class="col-sm-6">{{ $log->releasedBy?->full_name ?? __('front_desk.none') }}</dd>
            <dt class="col-sm-6 text-muted fw-normal">{{ __('front_desk.lost_found_status.released') }}</dt><dd class="col-sm-6">{{ $log->released_at?->format('d M Y H:i') ?? __('front_desk.none') }}</dd>
        </dl></div></div></div>
</div>

@can('front_desk.lost_found.claim')
@if($log->isUnclaimed())
<div class="modal fade" id="claimModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="POST" action="{{ $workspaceRoutes->route('admin.front-desk.lost-found.claim', $log) }}">@csrf
    <div class="modal-header"><h5 class="modal-title">{{ __('front_desk.actions.claim') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-3"><label class="form-label">{{ __('front_desk.fields.claimed_by_name') }}</label><input type="text" name="claimed_by_name" class="form-control"></div>
        <div class="mb-1"><label class="form-label">{{ __('front_desk.fields.claimed_by_phone') }}</label><input type="text" name="claimed_by_phone" class="form-control"></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('front_desk.actions.cancel') }}</button><button type="submit" class="btn btn-primary">{{ __('front_desk.actions.claim') }}</button></div>
</form></div></div></div>
@endif
@endcan

@can('front_desk.lost_found.release')
@unless($log->isClosed())
<div class="modal fade" id="releaseModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="POST" action="{{ $workspaceRoutes->route('admin.front-desk.lost-found.release', $log) }}">@csrf
    <div class="modal-header"><h5 class="modal-title">{{ __('front_desk.actions.release') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-3"><label class="form-label">{{ __('front_desk.fields.claimed_by_name') }}</label><input type="text" name="claimed_by_name" class="form-control" value="{{ $log->claimed_by_name }}"></div>
        <div class="mb-1"><label class="form-label">{{ __('front_desk.fields.claimed_by_phone') }}</label><input type="text" name="claimed_by_phone" class="form-control" value="{{ $log->claimed_by_phone }}"></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('front_desk.actions.cancel') }}</button><button type="submit" class="btn btn-success">{{ __('front_desk.actions.release') }}</button></div>
</form></div></div></div>
@endunless
@endcan

@can('front_desk.lost_found.cancel')
@unless($log->isClosed())
<div class="modal fade" id="cancelModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="POST" action="{{ $workspaceRoutes->route('admin.front-desk.lost-found.cancel', $log) }}">@csrf
    <div class="modal-header"><h5 class="modal-title">{{ __('front_desk.actions.cancel') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><label class="form-label">{{ __('front_desk.fields.reason') }}</label><textarea name="reason" class="form-control" rows="2"></textarea></div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('front_desk.actions.back') }}</button><button type="submit" class="btn btn-danger">{{ __('front_desk.actions.cancel') }}</button></div>
</form></div></div></div>
@endunless
@endcan
@endsection
