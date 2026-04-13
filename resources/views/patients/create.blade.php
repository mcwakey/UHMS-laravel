@extends('layouts.app')
@section('title', 'Register Patient')

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Register New Patient</h4>
    </div>
    <div>
        <a href="{{ route('admin.patients.index') }}" class="btn btn-outline-secondary btn-md">
            <i class="ti ti-arrow-left me-1"></i>Back to Patients
        </a>
    </div>
</div>

<form method="POST" action="{{ route('admin.patients.store') }}" enctype="multipart/form-data">
    @csrf

    <!-- Personal Information -->
    <div class="card">
        <div class="card-header">
            <h5 class="fw-bold mb-0"><i class="ti ti-user me-1"></i>Personal Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-lg-12 mb-3">
                    <label class="form-label mb-1 fw-medium">Profile Image</label>
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-xxl rounded-circle bg-light text-muted me-3 d-flex align-items-center justify-content-center" id="avatar-preview">
                            <i class="ti ti-user-plus fs-16"></i>
                        </div>
                        <input type="file" name="avatar" class="form-control @error('avatar') is-invalid @enderror" accept="image/*" style="max-width: 300px;">
                        @error('avatar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                    <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name') }}" required>
                    @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Other Names</label>
                    <input type="text" name="other_names" class="form-control @error('other_names') is-invalid @enderror" value="{{ old('other_names') }}">
                    @error('other_names')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                    <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name') }}" required>
                    @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                    <input type="date" name="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror" value="{{ old('date_of_birth') }}" required>
                    @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Gender <span class="text-danger">*</span></label>
                    <select name="gender" class="form-select @error('gender') is-invalid @enderror" required>
                        <option value="">Select Gender</option>
                        @foreach(\App\Enums\Gender::cases() as $g)
                            <option value="{{ $g->value }}" {{ old('gender') == $g->value ? 'selected' : '' }}>{{ $g->label() }}</option>
                        @endforeach
                    </select>
                    @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Marital Status</label>
                    <select name="marital_status" class="form-select @error('marital_status') is-invalid @enderror">
                        <option value="">Select</option>
                        @foreach(\App\Enums\MaritalStatus::cases() as $ms)
                            <option value="{{ $ms->value }}" {{ old('marital_status') == $ms->value ? 'selected' : '' }}>{{ $ms->label() }}</option>
                        @endforeach
                    </select>
                    @error('marital_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Religion</label>
                    <select name="religion" class="form-select @error('religion') is-invalid @enderror">
                        <option value="">Select</option>
                        @foreach(['Christianity', 'Islam', 'Traditional', 'Hindu', 'Buddhist', 'Other', 'None'] as $rel)
                            <option value="{{ $rel }}" {{ old('religion') == $rel ? 'selected' : '' }}>{{ $rel }}</option>
                        @endforeach
                    </select>
                    @error('religion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Blood Group</label>
                    <select name="blood_group" class="form-select @error('blood_group') is-invalid @enderror">
                        <option value="">Select</option>
                        @foreach(\App\Enums\BloodGroup::cases() as $bg)
                            <option value="{{ $bg->value }}" {{ old('blood_group') == $bg->value ? 'selected' : '' }}>{{ $bg->label() }}</option>
                        @endforeach
                    </select>
                    @error('blood_group')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Occupation</label>
                    <input type="text" name="occupation" class="form-control @error('occupation') is-invalid @enderror" value="{{ old('occupation') }}">
                    @error('occupation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <!-- Contact & Identification -->
    <div class="card">
        <div class="card-header">
            <h5 class="fw-bold mb-0"><i class="ti ti-phone me-1"></i>Contact & Identification</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                    <input type="tel" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" placeholder="e.g. 0241234567" required>
                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Secondary Phone</label>
                    <input type="tel" name="phone_secondary" class="form-control @error('phone_secondary') is-invalid @enderror" value="{{ old('phone_secondary') }}">
                    @error('phone_secondary')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Ghana Card Number</label>
                    <input type="text" name="ghana_card_number" class="form-control @error('ghana_card_number') is-invalid @enderror" value="{{ old('ghana_card_number') }}" placeholder="GHA-XXXXXXXXX-X">
                    @error('ghana_card_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <!-- Address Information -->
    <div class="card">
        <div class="card-header">
            <h5 class="fw-bold mb-0"><i class="ti ti-map-pin me-1"></i>Address Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address') }}</textarea>
                    @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">City / Town</label>
                    <input type="text" name="city" class="form-control @error('city') is-invalid @enderror" value="{{ old('city') }}">
                    @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Region</label>
                    <select name="region" class="form-select @error('region') is-invalid @enderror">
                        <option value="">Select Region</option>
                        @foreach(['Greater Accra', 'Ashanti', 'Western', 'Central', 'Eastern', 'Volta', 'Northern', 'Upper East', 'Upper West', 'Bono', 'Bono East', 'Ahafo', 'Western North', 'Oti', 'North East', 'Savannah'] as $region)
                            <option value="{{ $region }}" {{ old('region') == $region ? 'selected' : '' }}>{{ $region }}</option>
                        @endforeach
                    </select>
                    @error('region')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Digital Address (GPS)</label>
                    <input type="text" name="digital_address" class="form-control @error('digital_address') is-invalid @enderror" value="{{ old('digital_address') }}" placeholder="e.g. GA-123-4567">
                    @error('digital_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <!-- Emergency Contact -->
    <div class="card">
        <div class="card-header">
            <h5 class="fw-bold mb-0"><i class="ti ti-urgent me-1"></i>Emergency Contact</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Contact Name</label>
                    <input type="text" name="emergency_contact_name" class="form-control @error('emergency_contact_name') is-invalid @enderror" value="{{ old('emergency_contact_name') }}">
                    @error('emergency_contact_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Contact Phone</label>
                    <input type="tel" name="emergency_contact_phone" class="form-control @error('emergency_contact_phone') is-invalid @enderror" value="{{ old('emergency_contact_phone') }}">
                    @error('emergency_contact_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Relationship</label>
                    <select name="emergency_contact_relationship" class="form-select @error('emergency_contact_relationship') is-invalid @enderror">
                        <option value="">Select</option>
                        @foreach(['Spouse', 'Parent', 'Child', 'Sibling', 'Relative', 'Friend', 'Other'] as $rel)
                            <option value="{{ $rel }}" {{ old('emergency_contact_relationship') == $rel ? 'selected' : '' }}>{{ $rel }}</option>
                        @endforeach
                    </select>
                    @error('emergency_contact_relationship')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <!-- Medical Notes -->
    <div class="card">
        <div class="card-header">
            <h5 class="fw-bold mb-0"><i class="ti ti-report-medical me-1"></i>Medical Notes</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Known Allergies</label>
                    <textarea name="allergies" class="form-control @error('allergies') is-invalid @enderror" rows="3" placeholder="List any known allergies...">{{ old('allergies') }}</textarea>
                    @error('allergies')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Chronic Conditions</label>
                    <textarea name="chronic_conditions" class="form-control @error('chronic_conditions') is-invalid @enderror" rows="3" placeholder="List any chronic conditions...">{{ old('chronic_conditions') }}</textarea>
                    @error('chronic_conditions')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <!-- Submit -->
    <div class="d-flex justify-content-end gap-2 mb-4">
        <a href="{{ route('admin.patients.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>Register Patient</button>
    </div>
</form>
@endsection
