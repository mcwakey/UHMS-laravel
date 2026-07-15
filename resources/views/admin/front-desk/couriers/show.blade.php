@extends('layouts.app')
@section('title', __('front_desk.couriers.details'))

@section('content')
<div class="d-flex align-items-center mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ $log->courier_type?->translatedLabel() }}</h4>
        <div class="mt-1">
            <x-status-badge :status="$log->direction" size="sm" soft />
            <x-status-badge :status="$log->status" size="sm" />
            @if($log->handover_status)<x-status-badge :status="$log->handover_status" size="sm" soft />@endif
            @if($log->isOverdueCourier())<span class="badge bg-danger">{{ __('front_desk.filters.overdue') }}</span>@endif
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @can('front_desk.couriers.dispatch')
        @unless(in_array($log->status->value, ['delivered','cancelled','lost'], true))
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#dispatchModal"><i class="ti ti-truck me-1"></i>{{ __('front_desk.actions.dispatch') }}</button>
        @endunless
        @endcan
        @can('front_desk.couriers.handover')
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#handoverModal"><i class="ti ti-arrows-exchange me-1"></i>{{ __('front_desk.actions.handover') }}</button>
        @endcan
        @can('front_desk.couriers.deliver')
        @unless($log->isDelivered())
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#deliverModal"><i class="ti ti-package-export me-1"></i>{{ __('front_desk.actions.mark_delivered') }}</button>
        @endunless
        @endcan
        @can('front_desk.couriers.return')
        @unless($log->isDelivered())
        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#returnModal"><i class="ti ti-arrow-back-up me-1"></i>{{ __('front_desk.actions.mark_returned') }}</button>
        @endunless
        @endcan
        @can('front_desk.couriers.update')
        <a href="{{ $workspaceRoutes->route('admin.front-desk.couriers.edit', $log) }}" class="btn btn-outline-secondary"><i class="ti ti-edit me-1"></i>{{ __('front_desk.actions.edit') }}</a>
        @endcan
        <a href="{{ $workspaceRoutes->route('admin.front-desk.couriers.index') }}" class="btn btn-outline-secondary"><i class="ti ti-arrow-left me-1"></i>{{ __('front_desk.actions.back') }}</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h5 class="mb-0 fs-15">{{ __('front_desk.couriers.section_item') }}</h5></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.sender_name') }}</dt><dd class="col-sm-8">{{ $log->sender_name ?? __('front_desk.none') }}</dd>
                    <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.recipient_name') }}</dt><dd class="col-sm-8">{{ $log->recipient_name ?? __('front_desk.none') }}</dd>
                    <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.recipient_department') }}</dt><dd class="col-sm-8">{{ $log->recipientDepartment?->name ?? __('front_desk.none') }}</dd>
                    <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.tracking_number') }}</dt><dd class="col-sm-8">{{ $log->tracking_number ?? __('front_desk.none') }}</dd>
                    <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.reference_number') }}</dt><dd class="col-sm-8">{{ $log->reference_number ?? __('front_desk.none') }}</dd>
                </dl>
                @if($log->notes)
                <hr><div class="text-muted fs-13 mb-1">{{ __('front_desk.fields.notes') }}</div><p class="mb-0">{{ $log->notes }}</p>
                @endif
            </div>
        </div>

        {{-- Handover trail --}}
        <div class="card">
            <div class="card-header"><h5 class="mb-0 fs-15">{{ __('front_desk.couriers.section_timeline') }}</h5></div>
            <div class="card-body">
                @forelse($log->handoffs as $event)
                <div class="d-flex gap-2 pb-2 mb-2 border-bottom">
                    <div><x-status-badge :status="$event->action" size="sm" soft /></div>
                    <div class="flex-grow-1">
                        <div class="fs-13">{{ $event->createdBy?->full_name }}
                            @if($event->toDepartment) → {{ $event->toDepartment->name }}@endif
                            @if($event->toUser) → {{ $event->toUser->full_name }}@endif
                        </div>
                        @if($event->note)<div class="text-muted fs-13">{{ $event->note }}</div>@endif
                        <small class="text-muted">{{ $event->action_at?->format('d M Y H:i') }}</small>
                    </div>
                </div>
                @empty
                <p class="text-muted mb-0">{{ __('front_desk.couriers.no_timeline') }}</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h5 class="mb-0 fs-15">{{ __('front_desk.couriers.section_workflow') }}</h5></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-6 text-muted fw-normal">{{ __('front_desk.fields.handover_status') }}</dt>
                    <dd class="col-sm-6">@if($log->handover_status)<x-status-badge :status="$log->handover_status" size="sm" soft />@else {{ __('front_desk.none') }} @endif</dd>
                    <dt class="col-sm-6 text-muted fw-normal">{{ __('front_desk.fields.received_or_sent_at') }}</dt><dd class="col-sm-6">{{ $log->received_or_sent_at?->format('d M Y H:i') }}</dd>
                    <dt class="col-sm-6 text-muted fw-normal">{{ __('front_desk.fields.dispatched_at') }}</dt><dd class="col-sm-6">{{ $log->dispatched_at?->format('d M Y H:i') ?? __('front_desk.none') }}</dd>
                    <dt class="col-sm-6 text-muted fw-normal">{{ __('front_desk.fields.dispatched_by') }}</dt><dd class="col-sm-6">{{ $log->dispatchedBy?->full_name ?? __('front_desk.none') }}</dd>
                    <dt class="col-sm-6 text-muted fw-normal">{{ __('front_desk.fields.dispatch_department') }}</dt><dd class="col-sm-6">{{ $log->dispatchDepartment?->name ?? __('front_desk.none') }}</dd>
                    <dt class="col-sm-6 text-muted fw-normal">{{ __('front_desk.fields.received_internally_by') }}</dt><dd class="col-sm-6">{{ $log->receivedInternallyBy?->full_name ?? __('front_desk.none') }}</dd>
                    <dt class="col-sm-6 text-muted fw-normal">{{ __('front_desk.fields.delivered_at') }}</dt><dd class="col-sm-6">{{ $log->delivered_at?->format('d M Y H:i') ?? __('front_desk.none') }}</dd>
                    <dt class="col-sm-6 text-muted fw-normal">{{ __('front_desk.fields.proof_reference') }}</dt><dd class="col-sm-6">{{ $log->proof_reference ?? __('front_desk.none') }}</dd>
                </dl>
                @if($log->deliveryNote())
                <hr><div class="text-muted fs-13 mb-1">{{ __('front_desk.fields.delivery_note') }}</div><p class="mb-0">{{ $log->deliveryNote() }}</p>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Dispatch modal --}}
@can('front_desk.couriers.dispatch')
<div class="modal fade" id="dispatchModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ $workspaceRoutes->route('admin.front-desk.couriers.dispatch', $log) }}">
        @csrf
        <div class="modal-header"><h5 class="modal-title">{{ __('front_desk.actions.dispatch') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-3"><label class="form-label">{{ __('front_desk.fields.dispatch_department') }}</label>
                <select name="dispatch_department_id" class="form-select"><option value="">{{ __('front_desk.none') }}</option>@foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div>
            <div class="mb-1"><label class="form-label">{{ __('front_desk.fields.note') }}</label><textarea name="note" class="form-control" rows="2"></textarea></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('front_desk.actions.cancel') }}</button><button type="submit" class="btn btn-primary">{{ __('front_desk.actions.dispatch') }}</button></div>
    </form>
</div></div></div>
@endcan

{{-- Handover modal --}}
@can('front_desk.couriers.handover')
<div class="modal fade" id="handoverModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ $workspaceRoutes->route('admin.front-desk.couriers.handover', $log) }}">
        @csrf
        <div class="modal-header"><h5 class="modal-title">{{ __('front_desk.actions.handover') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-3"><label class="form-label">{{ __('front_desk.fields.received_internally_by') }}</label>
                <select name="to_user_id" class="form-select select2"><option value="">{{ __('front_desk.none') }}</option>@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->full_name }}</option>@endforeach</select></div>
            <div class="mb-3"><label class="form-label">{{ __('front_desk.fields.recipient_department') }}</label>
                <select name="to_department_id" class="form-select"><option value="">{{ __('front_desk.none') }}</option>@foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div>
            <div class="mb-1"><label class="form-label">{{ __('front_desk.fields.note') }}</label><textarea name="note" class="form-control" rows="2"></textarea></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('front_desk.actions.cancel') }}</button><button type="submit" class="btn btn-primary">{{ __('front_desk.actions.handover') }}</button></div>
    </form>
</div></div></div>
@endcan

{{-- Mark delivered modal (proof reference + delivery note) --}}
@can('front_desk.couriers.deliver')
@unless($log->isDelivered())
<div class="modal fade" id="deliverModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ $workspaceRoutes->route('admin.front-desk.couriers.mark-delivered', $log) }}">
        @csrf
        <div class="modal-header"><h5 class="modal-title">{{ __('front_desk.actions.mark_delivered') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-3"><label class="form-label">{{ __('front_desk.fields.delivered_to') }}</label><input type="text" name="delivered_to" class="form-control"></div>
            <div class="mb-3"><label class="form-label">{{ __('front_desk.fields.proof_reference') }}</label><input type="text" name="proof_reference" class="form-control"></div>
            <div class="mb-1"><label class="form-label">{{ __('front_desk.fields.delivery_note') }}</label><textarea name="delivery_note" class="form-control" rows="2"></textarea></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('front_desk.actions.cancel') }}</button><button type="submit" class="btn btn-success">{{ __('front_desk.actions.mark_delivered') }}</button></div>
    </form>
</div></div></div>
@endunless
@endcan

{{-- Mark returned modal --}}
@can('front_desk.couriers.return')
@unless($log->isDelivered())
<div class="modal fade" id="returnModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ $workspaceRoutes->route('admin.front-desk.couriers.mark-returned', $log) }}">
        @csrf
        <div class="modal-header"><h5 class="modal-title">{{ __('front_desk.actions.mark_returned') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><label class="form-label">{{ __('front_desk.fields.return_reason') }}</label><textarea name="reason" class="form-control" rows="2"></textarea></div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('front_desk.actions.cancel') }}</button><button type="submit" class="btn btn-danger">{{ __('front_desk.actions.mark_returned') }}</button></div>
    </form>
</div></div></div>
@endunless
@endcan
@endsection
