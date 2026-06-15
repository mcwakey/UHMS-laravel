@extends('layouts.app')
@section('title', __('hr.hr_configuration'))
@section('content')
<div class="d-flex align-items-center mb-3 pb-3 border-bottom">
    <div><h4 class="fw-bold mb-0">{{ __('hr.hr_configuration') }}</h4><div class="text-muted">{{ __('hr.hr_configuration_help') }}</div></div>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="row g-3">
    <div class="col-xl-5">
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">{{ __('hr.shift_settings') }}</h5></div>
            <div class="card-body">
                @can('hr.shifts.manage')
                <form method="POST" action="{{ route('admin.hr.configuration.shifts.store') }}" class="row g-2 mb-3">
                    @csrf
                    <div class="col-12"><input name="name" class="form-control" placeholder="{{ __('hr.shift') }}" required></div>
                    <div class="col-6"><label class="form-label">{{ __('hr.start_time') }}</label><input type="time" name="start_time" class="form-control" required></div>
                    <div class="col-6"><label class="form-label">{{ __('hr.end_time') }}</label><input type="time" name="end_time" class="form-control" required></div>
                    <div class="col-6"><label class="form-label">{{ __('hr.grace_minutes') }}</label><input type="number" name="grace_minutes" value="15" min="0" class="form-control"></div>
                    <div class="col-6"><label class="form-label">{{ __('hr.break_minutes') }}</label><input type="number" name="break_minutes" value="0" min="0" class="form-control"></div>
                    <div class="col-12 form-check ms-2"><input type="checkbox" name="is_night_shift" value="1" class="form-check-input" id="night"><label for="night" class="form-check-label">{{ __('hr.night_shift') }}</label></div>
                    <div class="col-12"><button class="btn btn-primary"><i class="ti ti-plus me-1"></i>{{ __('hr.add_shift') }}</button></div>
                </form>
                @endcan
                <div class="table-responsive"><table class="table table-sm"><thead><tr><th>{{ __('hr.shift') }}</th><th>{{ __('hr.start_time') }}</th><th>{{ __('hr.end_time') }}</th></tr></thead><tbody>
                    @forelse($shifts as $shift)<tr><td>{{ $shift->name }}</td><td>{{ $shift->start_time }}</td><td>{{ $shift->end_time }}</td></tr>@empty<tr><td colspan="3" class="text-muted">{{ __('hr.no_shifts') }}</td></tr>@endforelse
                </tbody></table></div>
            </div>
        </div>
    </div>
    <div class="col-xl-7">
        <div class="card mb-3"><div class="card-header"><h5 class="card-title mb-0">{{ __('hr.policy_settings') }}</h5></div><div class="card-body">
            @foreach($policies as $policy)
            <form method="POST" action="{{ route('admin.hr.configuration.policies.update', $policy) }}" class="row g-2 align-items-center mb-2">@csrf @method('PUT')
                <div class="col-md-6"><code>{{ $policy->key }}</code></div><div class="col-md-4"><input name="value" value="{{ $policy->value }}" class="form-control form-control-sm"></div>
                <div class="col-md-2"><button class="btn btn-sm btn-outline-primary w-100">{{ __('stock.save') }}</button></div>
            </form>
            @endforeach
        </div></div>
    </div>
</div>
<div class="card"><div class="card-header"><h5 class="card-title mb-0">{{ __('payroll.tax_tables') }}</h5></div><div class="card-body p-0"><div class="table-responsive">
<table class="table table-hover mb-0"><thead><tr><th>{{ __('payroll.tax_table') }}</th><th>{{ __('payroll.resident_type') }}</th><th>{{ __('payroll.period_basis') }}</th><th>{{ __('payroll.effective_from') }}</th><th>{{ __('payroll.tax_bands') }}</th></tr></thead><tbody>
@foreach($taxTables as $table)<tr><td>{{ $table->name }}</td><td>{{ __('payroll.'.$table->resident_type) }}</td><td>{{ $table->period_basis }}</td><td>{{ $table->effective_from->format('d M Y') }}</td><td>@foreach($table->bands as $band)<span class="badge bg-light text-dark me-1">{{ $band->band_label }}: {{ number_format($band->rate_percent, 2) }}%</span>@endforeach</td></tr>@endforeach
</tbody></table></div></div></div>
@endsection
