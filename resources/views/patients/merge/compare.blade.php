@extends('layouts.app')
@section('title', __('patients.compare_folders'))

@section('content')
<!-- <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <h4 class="fw-bold mb-1">{{ __('patients.compare_folders') }}</h4>
        <p class="text-muted mb-0">{{ __('patients.compare_subtitle') }}</p>
    </div>
    <a href="{{ route('admin.patients.merge.index') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-chevron-left me-1"></i>{{ __('common.back') }}</a>
</div> -->
<x-page-header-back
    :title="__('patients.compare_folders')"
    :href="route('admin.patients.merge.index')"
/>

@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

@foreach($preview['warnings'] as $warning)
    <div class="alert alert-warning">{{ $warning }}</div>
@endforeach

<div class="row g-3 mb-2">
    <div class="col-md-6">
        <div class="small text-muted mb-1">{{ __('patients.main_patient_number') }}: <span class="fw-semibold">{{ $mainPatient->patient_number }}</span></div>
        <x-patient-selection-card
            :selected-patient="$mainPatient"
            :show-search="false"
            :show-clear-button="false"
            :show-active-admission-warning="false"
            title="{{ __('patients.main_folder') }}"
            icon="ti-check"
            patient-info-id="mainFolderInfo"
            patient-initial-id="mainFolderInitial"
            patient-name-id="mainFolderName"
            patient-number-id="mainFolderNumber"
            patient-phone-id="mainFolderPhone"
            patient-last-visit-id="mainFolderLastVisit"
            deceased-warning-id="mainFolderDeceasedWarning"
            class="border-success"
        />
    </div>
    <div class="col-md-6">
        <div class="small text-muted mb-1">{{ __('patients.duplicate_patient_number') }}: <span class="fw-semibold">{{ $duplicatePatient->patient_number }}</span></div>
        <x-patient-selection-card
            :selected-patient="$duplicatePatient"
            :show-search="false"
            :show-clear-button="false"
            :show-active-admission-warning="false"
            title="{{ __('patients.duplicate_folder') }}"
            icon="ti-copy"
            patient-info-id="duplicateFolderInfo"
            patient-initial-id="duplicateFolderInitial"
            patient-name-id="duplicateFolderName"
            patient-number-id="duplicateFolderNumber"
            patient-phone-id="duplicateFolderPhone"
            patient-last-visit-id="duplicateFolderLastVisit"
            deceased-warning-id="duplicateFolderDeceasedWarning"
            class="border-warning"
        >
            @if($duplicatePatient->is_temporary)
                <span class="badge bg-warning-subtle text-warning mt-2">{{ __('patients.temporary_emergency') }}</span>
            @endif
        </x-patient-selection-card>
    </div>
</div>
<div class="card mb-3">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0">{{ __('patients.records_to_reassign') }}</h5>
        <span class="badge bg-primary">{{ $preview['total_records'] }} {{ __('patients.col_records') }}</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="bg-light"><tr><th>{{ __('patients.col_area') }}</th><th>{{ __('patients.col_handler') }}</th><th class="text-end">{{ __('patients.col_records') }}</th></tr></thead>
            <tbody>
                @foreach($preview['record_counts'] as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $row['handler'] }}</span></td>
                        <td class="text-end">{{ $row['count'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<form method="POST" action="{{ route('admin.patients.merge.requests.store') }}">
    @csrf
    <input type="hidden" name="main_patient_number" value="{{ $mainPatient->patient_number }}">
    <input type="hidden" name="duplicate_patient_number" value="{{ $duplicatePatient->patient_number }}">

    <div class="card mb-3">
        <div class="card-header">
            <h5 class="card-title mb-0">{{ __('patients.demographic_resolution') }}</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>{{ __('patients.col_field') }}</th>
                        <th>{{ __('patients.col_main_value') }}</th>
                        <th>{{ __('patients.col_duplicate_value') }}</th>
                        <th>{{ __('patients.col_keep') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($preview['demographic_fields'] as $field)
                        <tr class="{{ $field['differs'] ? '' : 'table-light' }}">
                            <td class="{{ $field['differs'] ? '' : 'text-dark' }}">{{ $field['label'] }}</td>
                            <td class="{{ $field['differs'] ? '' : 'text-dark' }}">{{ $field['main'] ?: '-' }}</td>
                            <td class="{{ $field['differs'] ? '' : 'text-dark' }}">{{ $field['duplicate'] ?: '-' }}</td>
                            <td style="min-width: 180px;">
                                @if($field['differs'])
                                    <select name="field_resolution[{{ $field['field'] }}]" class="form-select form-select-sm">
                                        <option value="main" {{ $field['suggested_source'] === 'main' ? 'selected' : '' }}>{{ __('patients.main_folder_value') }}</option>
                                        <option value="duplicate" {{ $field['suggested_source'] === 'duplicate' ? 'selected' : '' }}>{{ __('patients.duplicate_folder_value') }}</option>
                                    </select>
                                @else
                                    <input type="hidden" name="field_resolution[{{ $field['field'] }}]" value="{{ $field['suggested_source'] }}">
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">{{ __('patients.merge_reason') }} <span class="text-danger">*</span></label>
                <textarea name="reason" class="form-control @error('reason') is-invalid @enderror" rows="3" required
                          placeholder="{{ __('patients.merge_reason_ph') }}">{{ old('reason') }}</textarea>
                @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="confirmed" value="1" id="confirmed" required>
                <label class="form-check-label" for="confirmed">{{ __('patients.confirm_merge_label') }}</label>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-outline-primary"><i class="ti ti-file-plus me-1"></i>{{ __('patients.create_request') }}</button>
                @can('patients.merge.execute')
                    <button type="submit" name="execute_now" value="1" class="btn btn-primary"><i class="ti ti-git-merge me-1"></i>{{ __('patients.create_and_execute') }}</button>
                @endcan
            </div>
        </div>
    </div>
</form>
@endsection
