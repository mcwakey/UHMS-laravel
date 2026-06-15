@extends('layouts.app')
@section('title', 'Edit Employee - ' . $employee->full_name)

@section('content')
<div class="d-flex align-items-center mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Edit Employee — {{ $employee->full_name }}</h4>
        <small class="text-muted">{{ $employee->employee_number }}</small>
    </div>
    <a href="{{ route('admin.hr.employees.show', $employee) }}" class="btn btn-outline-secondary"><i class="ti ti-arrow-left me-1"></i>Back</a>
</div>

@if($errors->any())
<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<form method="POST" action="{{ route('admin.hr.employees.update', $employee) }}">
    @csrf
    @method('PUT')
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Personal Information</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $employee->first_name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $employee->last_name) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="">{{ __('common.select') }}</option>
                                @foreach($genders as $g)
                                    <option value="{{ $g->value }}" {{ old('gender', $employee->gender?->value) == $g->value ? 'selected' : '' }}>{{ $g->translatedLabel() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', $employee->date_of_birth?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Phone <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $employee->phone) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $employee->email) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Link to System User</label>
                            <select name="user_id" class="form-select">
                                <option value="">{{ __('common.none') }}</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ old('user_id', $employee->user_id) == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2">{{ old('address', $employee->address) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Employment Details</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Department</label>
                            <select name="department_id" class="form-select">
                                <option value="">{{ __('common.select') }}</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id', $employee->department_id) == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Position <span class="text-danger">*</span></label>
                            <input type="text" name="position" class="form-control" value="{{ old('position', $employee->position) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Hire Date <span class="text-danger">*</span></label>
                            <input type="date" name="hire_date" class="form-control" value="{{ old('hire_date', $employee->hire_date->format('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Basic Salary (GH₵) <span class="text-danger">*</span></label>
                            <input type="number" name="basic_salary" class="form-control" step="0.01" value="{{ old('basic_salary', $employee->basic_salary) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                @foreach($statuses as $s)
                                    <option value="{{ $s->value }}" {{ old('status', $employee->status->value) == $s->value ? 'selected' : '' }}>{{ $s->translatedLabel() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Bank & Tax Details</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Bank Name</label>
                            <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name', $employee->bank_name) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Bank Account #</label>
                            <input type="text" name="bank_account" class="form-control" value="{{ old('bank_account', $employee->bank_account) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Bank Branch</label>
                            <input type="text" name="bank_branch" class="form-control" value="{{ old('bank_branch', $employee->bank_branch) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">SSNIT Number</label>
                            <input type="text" name="ssnit_number" class="form-control" value="{{ old('ssnit_number', $employee->ssnit_number) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">TIN Number</label>
                            <input type="text" name="tin_number" class="form-control" value="{{ old('tin_number', $employee->tin_number) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('payroll.resident_type') }}</label>
                            <select name="tax_residency_status" class="form-select">
                                @foreach(['resident', 'non_resident', 'exempt'] as $type)<option value="{{ $type }}" @selected(old('tax_residency_status', $employee->tax_residency_status) === $type)>{{ __('payroll.'.$type) }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-12"><label class="form-label">{{ __('payroll.tax_identification_number') }}</label><input name="tax_identification_number" class="form-control" value="{{ old('tax_identification_number', $employee->tax_identification_number) }}"></div>
                        <div class="col-6"><label class="form-label">{{ __('payroll.tax_reliefs') }}</label><input type="number" step="0.01" min="0" name="tax_relief_amount" class="form-control" value="{{ old('tax_relief_amount', $employee->tax_relief_amount) }}"></div>
                        <div class="col-6"><label class="form-label">SSNIT %</label><input type="number" step="0.0001" min="0" name="employee_ssnit_rate" class="form-control" value="{{ old('employee_ssnit_rate', $employee->employee_ssnit_rate) }}"></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Emergency Contact</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Contact Name</label>
                            <input type="text" name="emergency_contact_name" class="form-control" value="{{ old('emergency_contact_name', $employee->emergency_contact_name) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Contact Phone</label>
                            <input type="text" name="emergency_contact_phone" class="form-control" value="{{ old('emergency_contact_phone', $employee->emergency_contact_phone) }}">
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100"><i class="ti ti-device-floppy me-1"></i>Update Employee</button>
        </div>
    </div>
</form>
@endsection
