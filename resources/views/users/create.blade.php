@extends('layouts.app')
@section('title', __('users.add_user'))

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ __('users.add_new_user') }}</h4>
    </div>
    <div>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-md">
            <i class="ti ti-arrow-left me-1"></i>{{ __('users.back_to_users') }}
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.users.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="row">
                <!-- Personal Info -->
                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('users.first_name') }} <span class="text-danger">*</span></label>
                    <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name') }}" required>
                    @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('users.last_name') }} <span class="text-danger">*</span></label>
                    <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name') }}" required>
                    @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('users.email_address') }} <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('users.phone') }}</label>
                    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}">
                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('users.password') }} <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('users.gender') }}</label>
                    <select name="gender" class="form-select @error('gender') is-invalid @enderror">
                        <option value="">{{ __('users.select_gender') }}</option>
                        @foreach(\App\Enums\Gender::cases() as $gender)
                            <option value="{{ $gender->value }}" {{ old('gender') == $gender->value ? 'selected' : '' }}>{{ $gender->translatedLabel() }}</option>
                        @endforeach
                    </select>
                    @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('users.date_of_birth') }}</label>
                    <input type="date" name="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror" value="{{ old('date_of_birth') }}">
                    @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('users.employee_id') }}</label>
                    <input type="text" name="employee_id" class="form-control @error('employee_id') is-invalid @enderror" value="{{ old('employee_id') }}" placeholder="{{ __('users.employee_id_auto_placeholder') }}">
                    @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <!-- Work Info -->
                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('users.role') }} <span class="text-danger">*</span></label>
                    <select name="role" class="form-select @error('role') is-invalid @enderror" required>
                        <option value="">{{ __('users.select_role') }}</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}" {{ old('role') == $role->name ? 'selected' : '' }}>{{ $role->name }}</option>
                        @endforeach
                    </select>
                    @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('users.department') }}</label>
                    <select name="department_id" class="form-select @error('department_id') is-invalid @enderror" id="department_id">
                        <option value="">{{ __('users.select_department') }}</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                    @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6 mb-3" id="specialties-group" style="display:none;">
                    <label class="form-label">{{ __('users.specialties') }}</label>
                    <div class="border rounded p-2" style="max-height:150px;overflow-y:auto">
                        @foreach($specialties as $spec)
                        <div class="form-check">
                            <input type="checkbox" name="specialties[]" value="{{ $spec->id }}" class="form-check-input" id="spec{{ $spec->id }}" {{ in_array($spec->id, old('specialties', [])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="spec{{ $spec->id }}">{{ $spec->name }}</label>
                        </div>
                        @endforeach
                    </div>
                    @error('specialties')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('users.status') }}</label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror">
                        <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>{{ __('users.active') }}</option>
                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>{{ __('users.inactive') }}</option>
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('users.avatar') }}</label>
                    <input type="file" name="avatar" class="form-control @error('avatar') is-invalid @enderror" accept="image/*">
                    @error('avatar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-3">
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">{{ __('common.cancel') }}</a>
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-check me-1"></i>{{ __('users.create_user') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const roleSelect = document.querySelector('select[name="role"]');
        const specGroup = document.getElementById('specialties-group');
        function toggleSpecialties() {
            const val = roleSelect.value.toLowerCase();
            specGroup.style.display = (val === 'doctor' || val === 'specialist') ? '' : 'none';
        }
        roleSelect.addEventListener('change', toggleSpecialties);
        toggleSpecialties();
    });
</script>
@endpush
