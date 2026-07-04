@extends('layouts.app')
@section('title', __('admissions.request_ref', ['id' => $admissionRequest->id]))

@section('content')
<x-page-header :title="__('admissions.request_ref', ['id' => $admissionRequest->id])" icon="ti-git-branch">
    <span class="badge bg-{{ $admissionRequest->status->color() }}">{{ $admissionRequest->status->label() }}</span>
    <x-slot:actions>
        <a href="{{ route('admin.admissions.requests') }}" class="btn btn-outline-secondary btn-sm">
            <i class="ti ti-arrow-left me-1"></i>{{ __('admissions.admission_requests') }}
        </a>
    </x-slot:actions>
</x-page-header>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="ti ti-circle-check me-1"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-light">
                <h6 class="mb-0">{{ __('admissions.request_details') }}</h6>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">{{ __('admissions.patient') }}</dt>
                    <dd class="col-sm-8">{{ $admissionRequest->patient?->full_name }} · {{ $admissionRequest->patient?->patient_number }}</dd>
                    <dt class="col-sm-4">{{ __('admissions.visit_no') }}</dt>
                    <dd class="col-sm-8">{{ $admissionRequest->visit?->visit_number ?? '—' }}</dd>
                    <dt class="col-sm-4">{{ __('admissions.source') }}</dt>
                    <dd class="col-sm-8">{{ $admissionRequest->source_type->label() }}</dd>
                    <dt class="col-sm-4">{{ __('admissions.requested_ward') }}</dt>
                    <dd class="col-sm-8">{{ $admissionRequest->requestedWard?->name ?? '—' }}</dd>
                    <dt class="col-sm-4">{{ __('admissions.reserved_bed') }}</dt>
                    <dd class="col-sm-8">
                        {{ $admissionRequest->reservedBed ? $admissionRequest->reservedBed->ward->name . ' / ' . $admissionRequest->reservedBed->bed_number : '—' }}
                        @if($admissionRequest->activeBedReservation)
                            <div class="small text-muted">
                                {{ __('admissions.bed_reservation_statuses.active') }}
                                @if($admissionRequest->activeBedReservation->expires_at)
                                    · {{ $admissionRequest->activeBedReservation->expires_at->diffForHumans() }}
                                @endif
                                @if($admissionRequest->activeBedReservation->reservedBy)
                                    · {{ $admissionRequest->activeBedReservation->reservedBy->name }}
                                @endif
                            </div>
                        @endif
                    </dd>
                    <dt class="col-sm-4">{{ __('admissions.provisional_diagnosis') }}</dt>
                    <dd class="col-sm-8">{{ $admissionRequest->provisional_diagnosis ?: '—' }}</dd>
                    <dt class="col-sm-4">{{ __('admissions.clinical_summary') }}</dt>
                    <dd class="col-sm-8">{{ $admissionRequest->clinical_summary ?: '—' }}</dd>
                    <dt class="col-sm-4">{{ __('admissions.reason') }}</dt>
                    <dd class="col-sm-8">{{ $admissionRequest->reason ?: '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header bg-light">
                <h6 class="mb-0">{{ __('admissions.request_actions') }}</h6>
            </div>
            <div class="card-body d-grid gap-2">
                @if($admissionRequest->status === \App\Enums\AdmissionRequestStatus::REQUESTED)
                    @can('admission.requests.accept')
                    <form method="POST" action="{{ route('admin.admissions.requests.accept', $admissionRequest) }}">
                        @csrf @method('PATCH')
                        <button class="btn btn-success w-100"><i class="ti ti-check me-1"></i>{{ __('admissions.accept_request') }}</button>
                    </form>
                    @endcan
                @endif

                @if(! $admissionRequest->status->isClosed())
                    @can('admission.requests.bed_pending')
                    <form method="POST" action="{{ route('admin.admissions.requests.bed-pending', $admissionRequest) }}">
                        @csrf @method('PATCH')
                        <button class="btn btn-outline-warning w-100"><i class="ti ti-clock me-1"></i>{{ __('admissions.mark_bed_pending') }}</button>
                    </form>
                    @endcan

                    @can('admission.requests.reserve_bed')
                    <form method="POST" action="{{ route('admin.admissions.requests.reserve-bed', $admissionRequest) }}" class="border rounded p-2">
                        @csrf @method('PATCH')
                        <label class="form-label small">{{ __('admissions.reserve_bed') }}</label>
                        <select name="bed_id" class="form-select mb-2" required>
                            <option value="">{{ __('admissions.select_bed') }}</option>
                            @foreach($availableBeds as $bed)
                                <option value="{{ $bed->id }}">{{ $bed->ward->name }} — {{ $bed->bed_number }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-outline-primary w-100"><i class="ti ti-bed me-1"></i>{{ __('admissions.reserve_bed') }}</button>
                    </form>
                    @endcan

                    @if($admissionRequest->status->canConvert())
                        @can('admission.requests.convert')
                        <form method="POST" action="{{ route('admin.admissions.requests.convert', $admissionRequest) }}">
                            @csrf
                            <button class="btn btn-warning w-100"><i class="ti ti-bed me-1"></i>{{ __('admissions.convert_to_admission') }}</button>
                        </form>
                        @endcan
                    @endif

                    @can('admission.requests.reject')
                    <form method="POST" action="{{ route('admin.admissions.requests.reject', $admissionRequest) }}" class="border rounded p-2">
                        @csrf @method('PATCH')
                        <label class="form-label small">{{ __('admissions.reject_request') }}</label>
                        <textarea name="reason" class="form-control mb-2" rows="2" required></textarea>
                        <button class="btn btn-outline-danger w-100">{{ __('admissions.reject_request') }}</button>
                    </form>
                    @endcan

                    @can('admission.requests.cancel')
                    <form method="POST" action="{{ route('admin.admissions.requests.cancel', $admissionRequest) }}" class="border rounded p-2">
                        @csrf @method('PATCH')
                        <label class="form-label small">{{ __('admissions.cancel_request') }}</label>
                        <textarea name="reason" class="form-control mb-2" rows="2" required></textarea>
                        <button class="btn btn-outline-secondary w-100">{{ __('admissions.cancel_request') }}</button>
                    </form>
                    @endcan
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
