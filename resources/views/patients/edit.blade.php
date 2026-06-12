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
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-xxl rounded-circle bg-light text-muted me-3 d-flex align-items-center justify-content-center">
                            @if($patient->avatar)
                                <img src="{{ Storage::url($patient->avatar) }}" alt="{{ $patient->full_name }}" class="rounded-circle">
                            @else
                                <span class="fs-16 fw-bold">{{ strtoupper(substr($patient->first_name, 0, 1) . substr($patient->last_name, 0, 1)) }}</span>
                            @endif
                        </div>
                        <input type="file" name="avatar" class="form-control @error('avatar') is-invalid @enderror" accept="image/*" style="max-width: 300px;">
                        @error('avatar')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                    <input type="date" name="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror" value="{{ old('date_of_birth', $patient->date_of_birth->format('Y-m-d')) }}" required>
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
                    <select name="occupation" class="form-select @error('occupation') is-invalid @enderror">
                        <option value="">{{ __('patients.select_occupation') }}</option>
                        @foreach(['Accountant','Architect','Artist','Baker','Banker','Barber','Business Owner','Carpenter','Cashier','Chef','Civil Servant','Clergy','Cleaner','Construction Worker','Consultant','Dentist','Doctor','Driver','Electrician','Engineer','Farmer','Fisherman','Graphic Designer','Hairdresser','Journalist','Lawyer','Lecturer','Mechanic','Miner','Musician','Nurse','Pharmacist','Photographer','Pilot','Plumber','Police Officer','Politician','Programmer','Retired','Salesperson','Secretary','Security Guard','Social Worker','Student','Surveyor','Tailor','Teacher','Technician','Trader','Unemployed','Veterinarian','Welder','Other'] as $occ)
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
                    <input type="tel" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $patient->phone) }}" required>
                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">{{ __('patients.secondary_phone') }}</label>
                    <input type="tel" name="phone_secondary" class="form-control @error('phone_secondary') is-invalid @enderror" value="{{ old('phone_secondary', $patient->phone_secondary) }}">
                    @error('phone_secondary')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">{{ __('patients.email_address') }}</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $patient->email) }}">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">{{ __('patients.ghana_card_number') }}</label>
                    <input type="text" name="ghana_card_number" class="form-control @error('ghana_card_number') is-invalid @enderror" value="{{ old('ghana_card_number', $patient->ghana_card_number) }}" placeholder="GHA-XXXXXXXXX-X">
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
                    <select name="region" class="form-select @error('region') is-invalid @enderror">
                        <option value="">{{ __('patients.select_region') }}</option>
                        @foreach(['Greater Accra', 'Ashanti', 'Western', 'Central', 'Eastern', 'Volta', 'Northern', 'Upper East', 'Upper West', 'Bono', 'Bono East', 'Ahafo', 'Western North', 'Oti', 'North East', 'Savannah'] as $region)
                            <option value="{{ $region }}" {{ old('region', $patient->region) == $region ? 'selected' : '' }}>{{ $region }}</option>
                        @endforeach
                    </select>
                    @error('region')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">{{ __('patients.city') }}</label>
                    <input type="text" name="city" class="form-control @error('city') is-invalid @enderror" value="{{ old('city', $patient->city) }}">
                    @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">{{ __('patients.town') }}</label>
                    <input type="text" name="town" class="form-control @error('town') is-invalid @enderror" value="{{ old('town', $patient->town) }}">
                    @error('town')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">{{ __('patients.digital_address_gps') }}</label>
                    <input type="text" name="digital_address" class="form-control @error('digital_address') is-invalid @enderror" value="{{ old('digital_address', $patient->digital_address) }}" placeholder="e.g. GA-123-4567">
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
@endsection
