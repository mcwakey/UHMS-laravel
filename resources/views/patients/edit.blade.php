@extends('layouts.app')
@section('title', __('patients.edit_patient') . ' - ' . $patient->full_name)

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3">
    <h6 class="fw-bold mb-0 d-flex align-items-center">
        <a href="{{ route('admin.patients.show', $patient) }}" class="text-dark"><i class="ti ti-chevron-left me-1"></i>{{ __('patients.edit_patient') }}</a>
    </h6>
    {{-- <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Edit Patient <span class="text-muted fs-14 ms-1">{{ $patient->patient_number }}</span></h4>
    </div>
    <div>
        <a href="{{ route('admin.patients.show', $patient) }}" class="btn btn-outline-secondary btn-md">
            <i class="ti ti-arrow-left me-1"></i>Back to Profile
        </a>
    </div> --}}
</div>

@php
    $phonePattern = $countrySettings['phone_pattern'] ?? null;
    $phonePlaceholder = $countrySettings['phone_placeholder'] ?? '';
    $digitalAddressPattern = $countrySettings['digital_address_pattern'] ?? null;
    $digitalAddressPlaceholder = $countrySettings['digital_address_placeholder'] ?? '';
@endphp

<form method="POST" action="{{ route('admin.patients.update', $patient) }}" enctype="multipart/form-data">
    @csrf @method('PUT')

    <!-- Personal Information -->
    <div class="card">
        <div class="card-header">
            <h5 class="fw-bold mb-0"><i class="ti ti-user me-1"></i>{{ __('patients.personal_information') }}</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-lg-12 mb-3">
                    <label class="form-label mb-1 fw-medium">{{ __('patients.profile_image') }}</label>
                    <div class="d-flex align-items-start flex-wrap gap-3">
                        <div class="avatar avatar-xxl rounded-circle bg-light text-muted d-flex align-items-center justify-content-center patient-avatar-preview" id="avatar-preview">
                            @if($patient->avatar)
                                <img src="{{ Storage::url($patient->avatar) }}" alt="{{ $patient->full_name }}" class="rounded-circle">
                            @else
                                <span class="fs-16 fw-bold">{{ strtoupper(substr($patient->first_name, 0, 1) . substr($patient->last_name, 0, 1)) }}</span>
                            @endif
                        </div>
                        <div class="flex-grow-1" style="max-width: 520px;">
                            <div class="d-flex gap-2 flex-wrap">
                                <input type="file" name="avatar" id="patientAvatarInput" class="form-control @error('avatar') is-invalid @enderror" accept="image/*" style="max-width: 300px;">
                                <button type="button" class="btn btn-outline-secondary" id="startPatientCameraBtn">
                                    <i class="ti ti-camera me-1"></i>{{ __('patients.use_webcam') }}
                                </button>
                            </div>
                            @error('avatar')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            @include('patients.partials.avatar-camera')
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('patients.first_name') }} <span class="text-danger">*</span></label>
                    <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name', $patient->first_name) }}" required>
                    @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('patients.other_names') }}</label>
                    <input type="text" name="other_names" class="form-control @error('other_names') is-invalid @enderror" value="{{ old('other_names', $patient->other_names) }}">
                    @error('other_names')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('patients.last_name') }} <span class="text-danger">*</span></label>
                    <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name', $patient->last_name) }}" required>
                    @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('patients.date_of_birth') }} <span class="text-danger">*</span></label>
                    <input type="date" name="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror" value="{{ old('date_of_birth', $patient->date_of_birth->format('Y-m-d')) }}" max="{{ date('Y-m-d') }}" required>
                    @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('patients.gender') }} <span class="text-danger">*</span></label>
                    <select name="gender" class="form-select @error('gender') is-invalid @enderror" required>
                        <option value="">{{ __('patients.select_gender') }}</option>
                        @foreach(\App\Enums\Gender::cases() as $g)
                            <option value="{{ $g->value }}" {{ old('gender', $patient->gender?->value) == $g->value ? 'selected' : '' }}>{{ $g->label() }}</option>
                        @endforeach
                    </select>
                    @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('patients.marital_status') }}</label>
                    <select name="marital_status" class="form-select @error('marital_status') is-invalid @enderror">
                        <option value="">{{ __('patients.select') }}</option>
                        @foreach(\App\Enums\MaritalStatus::cases() as $ms)
                            <option value="{{ $ms->value }}" {{ old('marital_status', $patient->marital_status?->value) == $ms->value ? 'selected' : '' }}>{{ $ms->label() }}</option>
                        @endforeach
                    </select>
                    @error('marital_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('patients.religion') }}</label>
                    <select name="religion" class="form-select @error('religion') is-invalid @enderror">
                        <option value="">{{ __('patients.select') }}</option>
                        @foreach(['Christianity', 'Islam', 'Traditional', 'Hindu', 'Buddhist', 'Other', 'None'] as $rel)
                            <option value="{{ $rel }}" {{ old('religion', $patient->religion) == $rel ? 'selected' : '' }}>{{ $rel }}</option>
                        @endforeach
                    </select>
                    @error('religion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('patients.blood_group') }}</label>
                    <select name="blood_group" class="form-select @error('blood_group') is-invalid @enderror">
                        <option value="">{{ __('patients.select') }}</option>
                        @foreach(\App\Enums\BloodGroup::cases() as $bg)
                            <option value="{{ $bg->value }}" {{ old('blood_group', $patient->blood_group?->value) == $bg->value ? 'selected' : '' }}>{{ $bg->label() }}</option>
                        @endforeach
                    </select>
                    @error('blood_group')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('patients.occupation') }}</label>
                    <select name="occupation" class="form-select patient-searchable-select @error('occupation') is-invalid @enderror" data-placeholder="{{ __('patients.select_occupation') }}">
                        <option value="">{{ __('patients.select_occupation') }}</option>
                        @foreach($occupations as $occ)
                            <option value="{{ $occ }}" {{ old('occupation', $patient->occupation) == $occ ? 'selected' : '' }}>{{ $occ }}</option>
                        @endforeach
                    </select>
                    @error('occupation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <!-- Contact & Identification -->
    <div class="card">
        <div class="card-header">
            <h5 class="fw-bold mb-0"><i class="ti ti-phone me-1"></i>{{ __('patients.contact_identification') }}</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">{{ __('patients.phone_number') }} <span class="text-danger">*</span></label>
                    <input type="tel" name="phone" class="form-control js-phone-mask @error('phone') is-invalid @enderror" value="{{ old('phone', $patient->phone) }}" placeholder="{{ $phonePlaceholder }}" inputmode="tel" @if($phonePattern) pattern="{{ $phonePattern }}" @endif required>
                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">{{ __('patients.secondary_phone') }}</label>
                    <input type="tel" name="phone_secondary" class="form-control @error('phone_secondary') is-invalid @enderror" value="{{ old('phone_secondary', $patient->phone_secondary) }}" placeholder="+000000000000" inputmode="tel">
                    @error('phone_secondary')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">{{ __('patients.email_address') }}</label>
                    <input type="email" name="email" class="form-control js-email-input @error('email') is-invalid @enderror" value="{{ old('email', $patient->email) }}" autocomplete="email" inputmode="email" pattern="^[^@\s]+@[^@\s]+\.[^@\s]+$">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">{{ __('patients.id_card_number') }}</label>
                    <input type="text" name="ghana_card_number" class="form-control js-id-card-input @error('ghana_card_number') is-invalid @enderror" value="{{ old('ghana_card_number', $patient->ghana_card_number) }}" placeholder="{{ __('patients.id_card_number') }}" inputmode="text" maxlength="30">
                    @error('ghana_card_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <!-- Address Information -->
    <div class="card">
        <div class="card-header">
            <h5 class="fw-bold mb-0"><i class="ti ti-map-pin me-1"></i>{{ __('patients.address_information') }}</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label">{{ __('patients.address') }}</label>
                    <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address', $patient->address) }}</textarea>
                    @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">{{ __('patients.region') }}</label>
                    <select name="region" id="patientRegionSelect" class="form-select patient-searchable-select @error('region') is-invalid @enderror" data-placeholder="{{ __('patients.select_region') }}">
                        <option value="">{{ __('patients.select_region') }}</option>
                        @foreach($regions as $region)
                            <option value="{{ $region->name }}" {{ old('region', $patient->region) == $region->name ? 'selected' : '' }}>{{ $region->name }}</option>
                        @endforeach
                    </select>
                    @error('region')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">{{ __('patients.city') }}</label>
                    <select name="city" id="patientCitySelect" class="form-select patient-searchable-select @error('city') is-invalid @enderror" data-selected="{{ old('city', $patient->city) }}" data-placeholder="{{ __('patients.city') }}" disabled>
                        <option value="">{{ __('patients.city') }}</option>
                    </select>
                    @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">{{ __('patients.town') }}</label>
                    <select name="town" id="patientTownSelect" class="form-select patient-searchable-select @error('town') is-invalid @enderror" data-selected="{{ old('town', $patient->town) }}" data-placeholder="{{ __('patients.town') }}" disabled>
                        <option value="">{{ __('patients.town') }}</option>
                    </select>
                    @error('town')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">{{ __('patients.digital_address_gps') }}</label>
                    <div class="input-group">
                        <input type="text" name="digital_address" id="digitalAddressInput" class="form-control js-digital-address-mask @error('digital_address') is-invalid @enderror" value="{{ old('digital_address', $patient->digital_address) }}" placeholder="{{ $digitalAddressPlaceholder }}" inputmode="text" @if($digitalAddressPattern) pattern="{{ $digitalAddressPattern }}" @endif>
                        <button class="btn btn-outline-secondary" type="button" id="detectDigitalAddressBtn" title="Use device location">
                            <i class="ti ti-current-location"></i>
                        </button>
                    </div>
                    @error('digital_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <!-- Medical Notes -->
    <div class="card">
        <div class="card-header">
            <h5 class="fw-bold mb-0"><i class="ti ti-report-medical me-1"></i>{{ __('patients.medical_notes') }}</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('patients.known_allergies') }}</label>
                    <textarea name="allergies" class="form-control @error('allergies') is-invalid @enderror" rows="3">{{ old('allergies', $patient->allergies) }}</textarea>
                    @error('allergies')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('patients.chronic_conditions') }}</label>
                    <textarea name="chronic_conditions" class="form-control @error('chronic_conditions') is-invalid @enderror" rows="3">{{ old('chronic_conditions', $patient->chronic_conditions) }}</textarea>
                    @error('chronic_conditions')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <!-- Submit -->
    <div class="d-flex justify-content-end gap-2 mb-4">
        <a href="{{ route('admin.patients.show', $patient) }}" class="btn btn-outline-secondary">{{ __('common.cancel') }}</a>
        <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>{{ __('patients.update_patient') }}</button>
    </div>
</form>

@push('scripts')
@include('patients.partials.avatar-camera-scripts')
@include('patients.partials.registration-input-scripts')
@endpush
@endsection
