@extends('layouts.app')
@section('title', __('admissions.new_request'))

@section('content')
<x-page-header :title="__('admissions.new_request')" icon="ti-git-branch">
    <x-slot:actions>
        <a href="{{ route('admin.admissions.requests') }}" class="btn btn-outline-secondary btn-sm">
            <i class="ti ti-arrow-left me-1"></i>{{ __('admissions.admission_requests') }}
        </a>
    </x-slot:actions>
</x-page-header>

@if($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('admin.admissions.requests.store') }}" class="card">
    @csrf
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">{{ __('admissions.visit_awaiting') }}</label>
                <select name="visit_id" class="form-select">
                    <option value="">{{ __('admissions.select_visit') }}</option>
                    @foreach($admittingVisits as $visit)
                        <option value="{{ $visit->id }}" @selected(old('visit_id') == $visit->id)>
                            {{ $visit->visit_number }} — {{ $visit->patient?->full_name }}
                        </option>
                    @endforeach
                </select>
                <small class="text-muted">{{ __('admissions.request_visit_hint') }}</small>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('admissions.patient_id_direct') }}</label>
                <input type="number" name="patient_id" class="form-control" value="{{ old('patient_id') }}" min="1">
                <small class="text-muted">{{ __('admissions.patient_id_direct_hint') }}</small>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('admissions.source') }}</label>
                <select name="source_type" class="form-select" required>
                    @foreach($sources as $source)
                        <option value="{{ $source->value }}" @selected(old('source_type', 'direct') === $source->value)>{{ $source->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('admissions.requested_ward') }}</label>
                <select name="requested_ward_id" class="form-select">
                    <option value="">{{ __('admissions.all_wards_option') }}</option>
                    @foreach($wards as $ward)
                        <option value="{{ $ward->id }}" @selected(old('requested_ward_id') == $ward->id)>{{ $ward->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('admissions.priority') }}</label>
                <select name="priority" class="form-select">
                    <option value="">{{ __('admissions.priority_routine') }}</option>
                    <option value="high" @selected(old('priority') === 'high')>{{ __('admissions.priorities.high') }}</option>
                    <option value="urgent" @selected(old('priority') === 'urgent')>{{ __('admissions.priorities.urgent') }}</option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">{{ __('admissions.provisional_diagnosis') }}</label>
                <textarea name="provisional_diagnosis" class="form-control" rows="3">{{ old('provisional_diagnosis') }}</textarea>
            </div>
            <div class="col-12">
                <label class="form-label">{{ __('admissions.clinical_summary') }}</label>
                <textarea name="clinical_summary" class="form-control" rows="4">{{ old('clinical_summary') }}</textarea>
            </div>
        </div>
    </div>
    <div class="card-footer text-end">
        <button class="btn btn-primary">
            <i class="ti ti-device-floppy me-1"></i>{{ __('admissions.create_request') }}
        </button>
    </div>
</form>
@endsection
