@extends('layouts.app')
@section('title', __('front_desk.calls.details'))

@php($followEnum = \App\Enums\FrontDesk\CallFollowUpStatus::tryFrom($log->follow_up_status ?? ''))

@section('content')
<div class="d-flex align-items-center mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ $log->category?->translatedLabel() }}</h4>
        <div class="mt-1">
            <x-status-badge :status="$log->direction" size="sm" soft />
            <x-status-badge :status="$log->outcome" size="sm" />
            @if($log->isOverdueCallback())<span class="badge bg-danger">{{ __('front_desk.filters.overdue') }}</span>@endif
        </div>
    </div>
    <div class="d-flex gap-2">
        @can('front_desk.calls.update')
        <a href="{{ $workspaceRoutes->route('admin.front-desk.calls.edit', $log) }}" class="btn btn-outline-primary"><i class="ti ti-edit me-1"></i>{{ __('front_desk.actions.edit') }}</a>
        @endcan
        <a href="{{ $workspaceRoutes->route('admin.front-desk.calls.index') }}" class="btn btn-outline-secondary"><i class="ti ti-arrow-left me-1"></i>{{ __('front_desk.actions.back') }}</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h5 class="mb-0 fs-15">{{ __('front_desk.calls.section_call') }}</h5></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.caller_name') }}</dt><dd class="col-sm-8">{{ $log->caller_name ?? __('front_desk.none') }}</dd>
                    <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.recipient_name') }}</dt><dd class="col-sm-8">{{ $log->recipient_name ?? __('front_desk.none') }}</dd>
                    <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.phone_number') }}</dt><dd class="col-sm-8">{{ $log->phone_number ?? __('front_desk.none') }}</dd>
                    <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.department') }}</dt><dd class="col-sm-8">{{ $log->department?->name ?? __('front_desk.none') }}</dd>
                    <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.started_at') }}</dt><dd class="col-sm-8">{{ $log->started_at?->format('d M Y H:i') }}</dd>
                    <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.handled_by') }}</dt><dd class="col-sm-8">{{ $log->handledBy?->full_name ?? __('front_desk.none') }}</dd>
                </dl>
                @if($log->notes)
                <hr>
                <div class="text-muted fs-13 mb-1">{{ __('front_desk.fields.notes') }}</div>
                <p class="mb-0">{{ $log->notes }}</p>
                @endif
            </div>
        </div>

        {{-- Transfer --}}
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0 fs-15">{{ __('front_desk.calls.section_transfer') }}</h5>
                @can('front_desk.calls.transfer')
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#transferModal">{{ __('front_desk.actions.transfer') }}</button>
                @endcan
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.transfer_department') }}</dt><dd class="col-sm-8">{{ $log->transferDepartment?->name ?? __('front_desk.none') }}</dd>
                    <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.transferred_to') }}</dt><dd class="col-sm-8">{{ $log->transferredToUser?->full_name ?? __('front_desk.none') }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0 fs-15">{{ __('front_desk.calls.section_followup') }}</h5>
                @can('front_desk.calls.followups.assign')
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#assignModal">{{ __('front_desk.actions.assign_follow_up') }}</button>
                @endcan
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5 text-muted fw-normal">{{ __('front_desk.fields.follow_up_required') }}</dt>
                    <dd class="col-sm-7">{{ $log->follow_up_required ? __('front_desk.yes') : __('front_desk.no') }}</dd>
                    <dt class="col-sm-5 text-muted fw-normal">{{ __('front_desk.fields.follow_up_status') }}</dt>
                    <dd class="col-sm-7">@if($followEnum)<x-status-badge :status="$followEnum" size="sm" soft />@else {{ __('front_desk.none') }} @endif</dd>
                    <dt class="col-sm-5 text-muted fw-normal">{{ __('front_desk.fields.assigned_to') }}</dt>
                    <dd class="col-sm-7">{{ $log->assignedFollowUpUser?->full_name ?? __('front_desk.none') }}</dd>
                    <dt class="col-sm-5 text-muted fw-normal">{{ __('front_desk.fields.follow_up_due_at') }}</dt>
                    <dd class="col-sm-7">{{ $log->follow_up_due_at?->format('d M Y H:i') ?? __('front_desk.none') }}</dd>
                </dl>
                @if($log->completionNote())
                <hr><div class="text-muted fs-13 mb-1">{{ __('front_desk.fields.completion_note') }}</div><p class="mb-0">{{ $log->completionNote() }}</p>
                @endif
                @if($log->cancellationReason())
                <hr><div class="text-muted fs-13 mb-1">{{ __('front_desk.fields.cancellation_reason') }}</div><p class="mb-0">{{ $log->cancellationReason() }}</p>
                @endif

                @if($log->hasPendingFollowUp())
                <hr>
                <div class="d-flex gap-2">
                    @can('front_desk.calls.followups.complete')
                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#completeModal">{{ __('front_desk.actions.complete_follow_up') }}</button>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">{{ __('front_desk.actions.cancel_follow_up') }}</button>
                    @endcan
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Assign follow-up modal --}}
@can('front_desk.calls.followups.assign')
<div class="modal fade" id="assignModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ $workspaceRoutes->route('admin.front-desk.calls.assign-follow-up', $log) }}">
        @csrf
        <div class="modal-header"><h5 class="modal-title">{{ __('front_desk.actions.assign_follow_up') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-3"><label class="form-label">{{ __('front_desk.fields.assigned_to') }}</label>
                <select name="assigned_follow_up_user_id" class="form-select select2">
                    <option value="">{{ __('front_desk.none') }}</option>
                    @foreach($users as $u)<option value="{{ $u->id }}" @selected($log->assigned_follow_up_user_id === $u->id)>{{ $u->full_name }}</option>@endforeach
                </select>
            </div>
            <div class="mb-3"><label class="form-label">{{ __('front_desk.fields.follow_up_due_at') }}</label>
                <input type="datetime-local" name="follow_up_due_at" class="form-control" value="{{ optional($log->follow_up_due_at)->format('Y-m-d\TH:i') }}"></div>
            <div class="mb-1"><label class="form-label">{{ __('front_desk.fields.note') }}</label><textarea name="follow_up_note" class="form-control" rows="2"></textarea></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('front_desk.actions.cancel') }}</button><button type="submit" class="btn btn-primary">{{ __('front_desk.actions.assign_follow_up') }}</button></div>
    </form>
</div></div></div>
@endcan

{{-- Complete / Cancel modals --}}
@can('front_desk.calls.followups.complete')
@if($log->hasPendingFollowUp())
<div class="modal fade" id="completeModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ $workspaceRoutes->route('admin.front-desk.calls.complete-follow-up', $log) }}">
        @csrf
        <div class="modal-header"><h5 class="modal-title">{{ __('front_desk.actions.complete_follow_up') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><label class="form-label">{{ __('front_desk.fields.completion_note') }}</label><textarea name="completion_note" class="form-control" rows="2"></textarea></div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('front_desk.actions.cancel') }}</button><button type="submit" class="btn btn-success">{{ __('front_desk.actions.complete_follow_up') }}</button></div>
    </form>
</div></div></div>
<div class="modal fade" id="cancelModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ $workspaceRoutes->route('admin.front-desk.calls.cancel-follow-up', $log) }}">
        @csrf
        <div class="modal-header"><h5 class="modal-title">{{ __('front_desk.actions.cancel_follow_up') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><label class="form-label">{{ __('front_desk.fields.cancellation_reason') }}</label><textarea name="cancellation_reason" class="form-control" rows="2"></textarea></div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('front_desk.actions.back') }}</button><button type="submit" class="btn btn-danger">{{ __('front_desk.actions.cancel_follow_up') }}</button></div>
    </form>
</div></div></div>
@endif
@endcan

{{-- Transfer modal --}}
@can('front_desk.calls.transfer')
<div class="modal fade" id="transferModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ $workspaceRoutes->route('admin.front-desk.calls.transfer', $log) }}">
        @csrf
        <div class="modal-header"><h5 class="modal-title">{{ __('front_desk.actions.transfer') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-3"><label class="form-label">{{ __('front_desk.fields.transfer_department') }}</label>
                <select name="transfer_department_id" class="form-select">
                    <option value="">{{ __('front_desk.none') }}</option>
                    @foreach($departments as $d)<option value="{{ $d->id }}" @selected($log->transfer_department_id === $d->id)>{{ $d->name }}</option>@endforeach
                </select></div>
            <div class="mb-1"><label class="form-label">{{ __('front_desk.fields.transferred_to') }}</label>
                <select name="transferred_to_user_id" class="form-select select2">
                    <option value="">{{ __('front_desk.none') }}</option>
                    @foreach($users as $u)<option value="{{ $u->id }}" @selected($log->transferred_to_user_id === $u->id)>{{ $u->full_name }}</option>@endforeach
                </select></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('front_desk.actions.cancel') }}</button><button type="submit" class="btn btn-primary">{{ __('front_desk.actions.transfer') }}</button></div>
    </form>
</div></div></div>
@endcan
@endsection
