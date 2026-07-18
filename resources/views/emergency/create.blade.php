@extends('layouts.app')
@section('title', __('emergency.create_title'))

@section('content')
@php
    $erRoute = fn ($name, $parameters = []) => $workspaceRoutes->route($name, $parameters);
@endphp
<x-page-header :title="__('emergency.create_title')" :description="__('emergency.create_description')" icon="ti-ambulance">
    <x-slot:actions>
        <a href="{{ $erRoute('admin.emergency.board') }}" class="btn btn-outline-secondary btn-sm">{{ __('emergency.back_to_board') }}</a>
    </x-slot:actions>
</x-page-header>

@if($errors->any())
    <div class="alert alert-danger">
        <div class="fw-semibold mb-1">{{ __('emergency.error_intro') }}</div>
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $erRoute('admin.emergency.cases.store') }}">
    @csrf
    @if(!empty($existingVisit))
        <input type="hidden" name="visit_id" value="{{ $existingVisit->id }}">
        <div class="alert alert-info d-flex align-items-start gap-2">
            <div>
                <div class="fw-semibold">{{ __('emergency.existing_session') }}</div>
                <div class="small">{{ $existingVisit->visit_number }} - {{ $existingVisit->patient?->full_name }}. {{ __('emergency.existing_session_detail') }}</div>
            </div>
        </div>
    @endif
    <div class="row g-3">
        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ __('emergency.patient_identity') }}</h5>
                </div>
                <div class="card-body">
                    @if(!empty($existingVisit))
                        <input type="hidden" name="patient_id" value="{{ $existingVisit->patient_id }}">
                        <div class="mb-3">
                            <label class="form-label">{{ __('emergency.existing_patient') }}</label>
                            <div class="form-control-plaintext fw-medium">
                                {{ $existingVisit->patient?->patient_number }} - {{ $existingVisit->patient?->full_name }}
                            </div>
                        </div>
                    @else
                        @include('patients.partials.patient-search-select', [
                            'id' => 'emergencyPatientSearch',
                            'name' => 'patient_id',
                            'label' => __('emergency.find_existing'),
                            'emptyOption' => __('emergency.create_temporary'),
                            'selected' => $selectedPatient ?? null,
                        ])
                        <small class="text-muted d-block mb-3">{{ __('emergency.temp_unknown_hint') }}</small>
                    @endif

                    <div id="emergencyTemporaryDetails" class="border rounded p-3 bg-light">
                        <div class="fw-semibold mb-2">{{ __('emergency.temp_patient_details') }}</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('emergency.display_name') }}</label>
                                <input type="text" class="form-control" name="temporary_display_name" value="{{ old('temporary_display_name') }}" placeholder="{{ __('emergency.display_name_ph') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('emergency.gender') }}</label>
                                <select class="form-select" name="temporary_gender">
                                    <option value="">{{ __('emergency.gender_unknown') }}</option>
                                    <option value="male" @selected(old('temporary_gender') === 'male')>{{ __('emergency.gender_male') }}</option>
                                    <option value="female" @selected(old('temporary_gender') === 'female')>{{ __('emergency.gender_female') }}</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('emergency.estimated_age') }}</label>
                                <input type="number" min="0" max="120" class="form-control" name="estimated_age" value="{{ old('estimated_age') }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ __('emergency.temp_reason') }}</label>
                                <textarea class="form-control" name="temporary_reason" rows="2" placeholder="{{ __('emergency.temp_reason_ph') }}">{{ old('temporary_reason') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ __('emergency.arrival_assignment') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('emergency.arrival_mode') }} <span class="text-danger">*</span></label>
                            <select class="form-select" name="arrival_mode" required>
                                @foreach([
                                    'WALK_IN' => 'Walk-in',
                                    'AMBULANCE' => 'Ambulance',
                                    'POLICE' => 'Police',
                                    'FAMILY_BROUGHT' => 'Family brought',
                                    'REFERRAL' => 'Referral',
                                    'TRANSFER_FROM_OPD' => 'Transfer from OPD',
                                    'TRANSFER_FROM_WARD' => 'Transfer from ward',
                                    'UNKNOWN' => 'Unknown',
                                ] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('arrival_mode') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('emergency.arrival_time') }} <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" name="arrival_time" value="{{ old('arrival_time', now()->format('Y-m-d\TH:i')) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('emergency.brought_by') }}</label>
                            <input type="text" class="form-control" name="brought_by" value="{{ old('brought_by') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('emergency.source') }}</label>
                            <input type="text" class="form-control" name="source" value="{{ old('source') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('emergency.referral_facility') }}</label>
                            <input type="text" class="form-control" name="referral_facility" value="{{ old('referral_facility') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('emergency.chief_complaint') }}</label>
                            <textarea class="form-control" name="chief_complaint" rows="2">{{ old('chief_complaint') }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('emergency.initial_condition') }}</label>
                            <textarea class="form-control" name="initial_condition" rows="2">{{ old('initial_condition') }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('emergency.bay') }}</label>
                            <select class="form-select" name="emergency_bay_id">
                                <option value="">{{ __('emergency.assign_later') }}</option>
                                @foreach($bays as $bay)
                                    <option value="{{ $bay->id }}" @selected(old('emergency_bay_id') == $bay->id)>{{ $bay->name }} ({{ str_replace('_', ' ', $bay->bay_type) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('emergency.doctor') }}</label>
                            <select class="form-select" name="assigned_doctor_id">
                                <option value="">{{ __('emergency.unassigned') }}</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" @selected(old('assigned_doctor_id') == $user->id)>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('emergency.nurse') }}</label>
                            <select class="form-select" name="assigned_nurse_id">
                                <option value="">{{ __('emergency.unassigned') }}</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" @selected(old('assigned_nurse_id') == $user->id)>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end gap-2">
                    <a href="{{ $erRoute('admin.emergency.board') }}" class="btn btn-outline-secondary">{{ __('emergency.cancel') }}</a>
                    <button class="btn btn-primary" type="submit">{{ __('emergency.create_case') }}</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
    @include('patients.partials.patient-search-select-scripts', ['id' => 'emergencyPatientSearch'])
    <script>
        window.emergencyPatientSearchOnSelect = function () {
            var box = document.getElementById('emergencyTemporaryDetails');
            if (box) { box.classList.add('opacity-50'); }
        };
        window.emergencyPatientSearchOnClear = function () {
            var box = document.getElementById('emergencyTemporaryDetails');
            if (box) { box.classList.remove('opacity-50'); }
        };
    </script>
@endpush
