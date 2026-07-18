@extends('layouts.app')
@section('title', __('inpatient.readmission.title'))

@section('content')
<x-page-header :title="__('inpatient.readmission.title')" icon="ti-refresh">
    <x-slot:actions>
        <a href="{{ route('inpatient.admissions.show', $admission) }}" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i>{{ __('admissions.back_to_admission') }}
        </a>
    </x-slot:actions>
</x-page-header>

<div class="row justify-content-center">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">{{ __('inpatient.readmission.confirm') }}</h5></div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="ti ti-alert-triangle me-1"></i>{{ __('inpatient.readmission.history_notice') }}
                </div>
                <dl class="row mb-4">
                    <dt class="col-sm-4">{{ __('admissions.patient') }}</dt>
                    <dd class="col-sm-8">{{ $admission->patient->full_name }} ({{ $admission->patient->patient_number }})</dd>
                    <dt class="col-sm-4">{{ __('admissions.admission_no') }}</dt>
                    <dd class="col-sm-8">{{ $admission->admission_number }}</dd>
                    <dt class="col-sm-4">{{ __('admissions.ward_bed') }}</dt>
                    <dd class="col-sm-8">{{ $admission->bed?->ward?->name ?? '—' }} / {{ $admission->bed?->bed_number ?? '—' }}</dd>
                    <dt class="col-sm-4">{{ __('inpatient.readmission.previous_discharge') }}</dt>
                    <dd class="col-sm-8">{{ $admission->actual_discharge_date?->format('d M Y, H:i') ?? '—' }}</dd>
                </dl>
                <form method="POST" action="{{ route('inpatient.readmissions.store', $admission) }}">
                    @csrf
                    <div class="mb-3">
                        <label for="readmissionReason" class="form-label">{{ __('inpatient.readmission.reason') }} <span class="text-danger">*</span></label>
                        <textarea id="readmissionReason" name="reason" rows="4" required minlength="5" maxlength="1000" class="form-control @error('reason') is-invalid @enderror">{{ old('reason') }}</textarea>
                        @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary"><i class="ti ti-refresh me-1"></i>{{ __('inpatient.readmission.submit') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
