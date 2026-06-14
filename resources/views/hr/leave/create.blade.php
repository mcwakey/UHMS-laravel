@extends('layouts.app')
@section('title', __('hr.submit_leave_request'))

@section('content')
<div class="d-flex align-items-center mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ __('hr.submit_leave_request') }}</h4>
    </div>
    <a href="{{ route('admin.hr.leave.index') }}" class="btn btn-outline-secondary"><i class="ti ti-arrow-left me-1"></i>{{ __('stock.back') }}</a>
</div>

@if($errors->any())
<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.hr.leave.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('hr.employee') }} <span class="text-danger">*</span></label>
                            <select name="employee_id" class="form-select" required>
                                <option value="">{{ __('hr.select_employee') }}</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>{{ $emp->full_name }} ({{ $emp->employee_number }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('hr.leave_type') }} <span class="text-danger">*</span></label>
                            <select name="leave_type" class="form-select" required>
                                <option value="">{{ __('hr.select_type') }}</option>
                                @foreach($leaveTypes as $type)
                                    <option value="{{ $type->value }}" {{ old('leave_type') == $type->value ? 'selected' : '' }}>{{ $type->translatedLabel() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('hr.start_date') }} <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control" value="{{ old('start_date') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('hr.end_date') }} <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('hr.reason') }}</label>
                            <textarea name="reason" class="form-control" rows="3">{{ old('reason') }}</textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary"><i class="ti ti-send me-1"></i>{{ __('hr.submit_leave_request') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-info">
            <div class="card-header bg-info bg-opacity-10">
                <h5 class="card-title mb-0"><i class="ti ti-info-circle me-1"></i>{{ __('hr.leave_policy') }}</h5>
            </div>
            <div class="card-body">
                <ul class="mb-0 ps-3">
                    <li class="mb-2"><strong>Annual Leave:</strong> 21 working days per year (Ghana Labour Act)</li>
                    <li class="mb-2"><strong>Sick Leave:</strong> Medical certificate required for 3+ days</li>
                    <li class="mb-2"><strong>Maternity:</strong> 12 weeks with full pay</li>
                    <li class="mb-2"><strong>Paternity:</strong> 2 weeks</li>
                    <li class="mb-2"><strong>Study Leave:</strong> Subject to approval</li>
                    <li><strong>{{ __('hr.unpaid_leave') }}</strong> Deducted from salary</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
