@extends('layouts.app')
@section('title', __('front_desk.visitors.details'))

@section('content')
<div class="d-flex align-items-center mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ $log->visitor_name }}</h4>
        <div class="mt-1">
            <x-status-badge :status="$log->status" size="sm" />
            <x-status-badge :status="$log->visitor_context" size="sm" soft />
            @if($log->isOverdue())<span class="badge bg-danger">{{ __('front_desk.visitors.overdue') }}</span>@endif
            @if($log->badge_number)<span class="badge badge-soft-secondary border">{{ $log->badge_number }}</span>@endif
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @can('front_desk.visitors.checkout')
        @if($log->isInside())
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#checkoutModal">
            <i class="ti ti-logout me-1"></i>{{ __('front_desk.actions.check_out') }}
        </button>
        @endif
        @endcan
        @can('front_desk.visitors.print_pass')
        <a href="{{ route('admin.front-desk.visitors.pass', $log) }}" target="_blank" rel="noopener" class="btn btn-outline-primary">
            <i class="ti ti-id-badge-2 me-1"></i>{{ __('front_desk.actions.print_pass') }}
        </a>
        @endcan
        @can('front_desk.visitors.update')
        <a href="{{ route('admin.front-desk.visitors.edit', $log) }}" class="btn btn-outline-primary"><i class="ti ti-edit me-1"></i>{{ __('front_desk.actions.edit') }}</a>
        @endcan
        <a href="{{ route('admin.front-desk.visitors.index') }}" class="btn btn-outline-secondary"><i class="ti ti-arrow-left me-1"></i>{{ __('front_desk.actions.back') }}</a>
    </div>
</div>

@include('admin.front-desk.partials.visitor-warnings', ['warnings' => $warnings ?? []])

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h5 class="mb-0 fs-15">{{ __('front_desk.visitors.section_visitor') }}</h5></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.visitor_phone') }}</dt><dd class="col-sm-8">{{ $log->visitor_phone ?? __('front_desk.none') }}</dd>
                    <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.organization') }}</dt><dd class="col-sm-8">{{ $log->organization ?? __('front_desk.none') }}</dd>
                    <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.badge_number') }}</dt><dd class="col-sm-8">{{ $log->badge_number ?? __('front_desk.none') }}</dd>
                    <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.id_type') }}</dt><dd class="col-sm-8">{{ $log->id_type ?? __('front_desk.none') }}</dd>
                    <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.id_number') }}</dt><dd class="col-sm-8">{{ $log->id_number ?? __('front_desk.none') }}</dd>
                    <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.vehicle_number') }}</dt><dd class="col-sm-8">{{ $log->vehicle_number ?? __('front_desk.none') }}</dd>
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0 fs-15">{{ __('front_desk.visitors.section_admission') }}</h5>
                <div class="d-flex gap-2">
                    @if($log->patient)
                    <a href="{{ route('admin.front-desk.visitors.patient-history', $log->patient) }}" class="fs-13"><i class="ti ti-history me-1"></i>{{ __('front_desk.actions.patient_history') }}</a>
                    @endif
                    @if($log->admission)
                    <a href="{{ route('admin.front-desk.visitors.admission-history', $log->admission) }}" class="fs-13">{{ __('front_desk.actions.admission_history') }}</a>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <p class="text-muted fs-13"><i class="ti ti-shield-lock me-1"></i>{{ __('front_desk.privacy.safe_notice') }}</p>
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.patient') }}</dt>
                    <dd class="col-sm-8">
                        @if($log->patient)
                            <a href="{{ route('admin.patients.show', $log->patient) }}">{{ $log->patient->patient_number }} — {{ $log->patient->full_name }}</a>
                        @else {{ __('front_desk.none') }} @endif
                    </dd>
                    <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.ward') }}</dt><dd class="col-sm-8">{{ $log->ward?->name ?? __('front_desk.none') }}</dd>
                    <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.bed') }}</dt><dd class="col-sm-8">{{ $log->bed?->bed_number ?? $log->bed?->name ?? __('front_desk.none') }}</dd>
                    <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.admission') }}</dt><dd class="col-sm-8">{{ $log->admission?->admission_number ?? __('front_desk.none') }}</dd>
                    <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.department') }}</dt><dd class="col-sm-8">{{ $log->department?->name ?? __('front_desk.none') }}</dd>
                    <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.relationship_to_patient') }}</dt><dd class="col-sm-8">{{ $log->relationship_to_patient ?? __('front_desk.none') }}</dd>
                    <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.person_to_see') }}</dt><dd class="col-sm-8">{{ $log->person_to_see ?? __('front_desk.none') }}</dd>
                    <dt class="col-sm-4 text-muted fw-normal">{{ __('front_desk.fields.purpose') }}</dt><dd class="col-sm-8">{{ $log->purpose ?? __('front_desk.none') }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h5 class="mb-0 fs-15">{{ __('front_desk.visitors.section_meta') }}</h5></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5 text-muted fw-normal">{{ __('front_desk.fields.time_in') }}</dt><dd class="col-sm-7">{{ $log->time_in?->format('d M Y H:i') }}</dd>
                    <dt class="col-sm-5 text-muted fw-normal">{{ __('front_desk.fields.expected_checkout') }}</dt><dd class="col-sm-7">{{ $log->expectedCheckoutAt()?->format('d M Y H:i') ?? __('front_desk.none') }}</dd>
                    <dt class="col-sm-5 text-muted fw-normal">{{ __('front_desk.fields.time_out') }}</dt><dd class="col-sm-7">{{ $log->time_out?->format('d M Y H:i') ?? __('front_desk.none') }}</dd>
                    <dt class="col-sm-5 text-muted fw-normal">{{ __('front_desk.fields.duration') }}</dt><dd class="col-sm-7">{{ $log->durationMinutes() !== null ? $log->durationMinutes() . ' min' : __('front_desk.none') }}</dd>
                    <dt class="col-sm-5 text-muted fw-normal">{{ __('front_desk.fields.checked_in_by') }}</dt><dd class="col-sm-7">{{ $log->checkedInBy?->full_name ?? __('front_desk.none') }}</dd>
                    <dt class="col-sm-5 text-muted fw-normal">{{ __('front_desk.fields.checked_out_by') }}</dt><dd class="col-sm-7">{{ $log->checkedOutBy?->full_name ?? __('front_desk.none') }}</dd>
                </dl>
                @if($log->checkoutNote())
                <hr>
                <div class="text-muted fs-13 mb-1">{{ __('front_desk.fields.checkout_note') }}</div>
                <p class="mb-0">{{ $log->checkoutNote() }}</p>
                @endif
                @if($log->notes)
                <hr>
                <div class="text-muted fs-13 mb-1">{{ __('front_desk.fields.notes') }}</div>
                <p class="mb-0">{{ $log->notes }}</p>
                @endif
            </div>
        </div>
    </div>
</div>

@can('front_desk.visitors.checkout')
@if($log->isInside())
<div class="modal fade" id="checkoutModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.front-desk.visitors.check-out', $log) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('front_desk.actions.check_out') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('front_desk.fields.time_out') }}</label>
                        <input type="datetime-local" name="time_out" class="form-control">
                    </div>
                    <div class="mb-1">
                        <label class="form-label">{{ __('front_desk.fields.checkout_note') }}</label>
                        <textarea name="checkout_note" class="form-control" rows="2" placeholder="{{ __('front_desk.actions.add_note_optional') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('front_desk.actions.cancel') }}</button>
                    <button type="submit" class="btn btn-success">{{ __('front_desk.actions.check_out') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endcan
@endsection
