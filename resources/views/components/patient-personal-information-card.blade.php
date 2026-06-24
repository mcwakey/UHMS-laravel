@props([
    'patient' => null,
    'occupations' => [],
    'showAvatar' => true,
])

@php
    $occupations = collect($occupations);
    $genderValue = old('gender', data_get($patient?->gender, 'value', $patient?->gender));
    $maritalStatusValue = old('marital_status', data_get($patient?->marital_status, 'value', $patient?->marital_status));
    $bloodGroupValue = old('blood_group', data_get($patient?->blood_group, 'value', $patient?->blood_group));
@endphp

<div class="card">
    <div class="card-header">
        <h5 class="fw-bold mb-0"><i class="ti ti-user me-1"></i>{{ __('patients.personal_information') }}</h5>
    </div>
    <div class="card-body">
        <div class="row">
            @if($showAvatar)
                <div class="col-lg-12 mb-3">
                    <label class="form-label mb-1 fw-medium">{{ __('patients.profile_image') }}</label>
                    <div class="d-flex align-items-start flex-wrap gap-3">
                        <div class="avatar avatar-xxl rounded-circle bg-light text-muted d-flex align-items-center justify-content-center patient-avatar-preview" id="avatar-preview">
                            @if($patient?->avatar)
                                <img src="{{ Storage::url($patient->avatar) }}" alt="{{ $patient->full_name }}" class="rounded-circle">
                            @elseif($patient)
                                <span class="fs-16 fw-bold">{{ strtoupper(substr((string) $patient->first_name, 0, 1) . substr((string) $patient->last_name, 0, 1)) }}</span>
                            @else
                                <i class="ti ti-user-plus fs-16"></i>
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
            @endif

            <div class="col-md-4 mb-3">
                <label class="form-label">{{ __('patients.first_name') }} <span class="text-danger">*</span></label>
                <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name', $patient?->first_name) }}" required>
                @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">{{ __('patients.other_names') }}</label>
                <input type="text" name="other_names" class="form-control @error('other_names') is-invalid @enderror" value="{{ old('other_names', $patient?->other_names) }}">
                @error('other_names')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">{{ __('patients.last_name') }} <span class="text-danger">*</span></label>
                <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name', $patient?->last_name) }}" required>
                @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">{{ __('patients.date_of_birth') }} <span class="text-danger">*</span></label>
                <input type="date" name="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror" value="{{ old('date_of_birth', optional($patient?->date_of_birth)->format('Y-m-d')) }}" max="{{ date('Y-m-d') }}" required>
                @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">{{ __('patients.gender') }} <span class="text-danger">*</span></label>
                <select name="gender" class="form-select @error('gender') is-invalid @enderror" required>
                    <option value="">{{ __('patients.select_gender') }}</option>
                    @foreach(\App\Enums\Gender::cases() as $g)
                        <option value="{{ $g->value }}" {{ $genderValue == $g->value ? 'selected' : '' }}>{{ $g->label() }}</option>
                    @endforeach
                </select>
                @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">{{ __('patients.marital_status') }}</label>
                <select name="marital_status" class="form-select @error('marital_status') is-invalid @enderror">
                    <option value="">{{ __('patients.select') }}</option>
                    @foreach(\App\Enums\MaritalStatus::cases() as $ms)
                        <option value="{{ $ms->value }}" {{ $maritalStatusValue == $ms->value ? 'selected' : '' }}>{{ $ms->label() }}</option>
                    @endforeach
                </select>
                @error('marital_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">{{ __('patients.religion') }}</label>
                <select name="religion" class="form-select @error('religion') is-invalid @enderror">
                    <option value="">{{ __('patients.select') }}</option>
                    @foreach(['Christianity', 'Islam', 'Traditional', 'Hindu', 'Buddhist', 'Other', 'None'] as $rel)
                        <option value="{{ $rel }}" {{ old('religion', $patient?->religion) == $rel ? 'selected' : '' }}>{{ $rel }}</option>
                    @endforeach
                </select>
                @error('religion')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">{{ __('patients.blood_group') }}</label>
                <select name="blood_group" class="form-select @error('blood_group') is-invalid @enderror">
                    <option value="">{{ __('patients.select') }}</option>
                    @foreach(\App\Enums\BloodGroup::cases() as $bg)
                        <option value="{{ $bg->value }}" {{ $bloodGroupValue == $bg->value ? 'selected' : '' }}>{{ $bg->label() }}</option>
                    @endforeach
                </select>
                @error('blood_group')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">{{ __('patients.occupation') }}</label>
                <select name="occupation" class="form-select patient-searchable-select @error('occupation') is-invalid @enderror" data-placeholder="{{ __('patients.select_occupation') }}">
                    <option value="">{{ __('patients.select_occupation') }}</option>
                    @foreach($occupations as $occ)
                        <option value="{{ $occ }}" {{ old('occupation', $patient?->occupation) == $occ ? 'selected' : '' }}>{{ $occ }}</option>
                    @endforeach
                </select>
                @error('occupation')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>
