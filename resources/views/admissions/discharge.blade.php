@extends('layouts.app')
@section('title', __('admissions.discharge_title') . ' — ' . $admission->patient->full_name)

@section('content')
<x-page-header :title="__('admissions.discharge_title')" icon="ti-logout">
    <x-slot:actions>
        <a href="{{ $workspaceRoutes->route('admin.admissions.show', $admission) }}" class="btn btn-outline-secondary btn-md fs-13">
            <i class="ti ti-arrow-left me-1"></i>{{ __('admissions.back_to_admission') }}
        </a>
    </x-slot:actions>
</x-page-header>

@if($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

{{-- Previous-visit + current-admission + total patient balance at discharge clearance. --}}
@if(config('billing.previous_balance_policy.admission_show_previous_balance_on_discharge', true) && $admission->visit)
    <x-billing.previous-balance-alert :visit="$admission->visit" />
@endif

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-warning bg-opacity-10">
                <h5 class="card-title mb-0"><i class="ti ti-logout me-1"></i>{{ __('admissions.discharge_form') }}</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info d-flex align-items-start mb-4">
                    <i class="ti ti-info-circle fs-4 me-2 mt-1"></i>
                    <div>
                        <strong>{{ $admission->patient->full_name }}</strong> ({{ $admission->patient->patient_number }})<br>
                        <small>{{ __('admissions.admission_no') }} {{ $admission->admission_number }} | {{ __('admissions.ward_bed') }}: {{ $admission->bed->ward->name }} | {{ __('admissions.bed') }}: {{ $admission->bed->bed_number }}</small><br>
                        <small>{{ __('admissions.admitted_on') }}: {{ $admission->admission_date->format('d M Y, H:i') }} | {{ __('admissions.length_of_stay') }}: {{ $admission->length_of_stay }} {{ __('admissions.days') }}</small>
                    </div>
                </div>

                <form method="POST" action="{{ $workspaceRoutes->route('admin.admissions.process-discharge', $admission) }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">{{ __('admissions.discharge_summary') }} <span class="text-danger">*</span></label>
                        <textarea name="discharge_summary" class="form-control" rows="5" required
                                  placeholder="{{ __('admissions.discharge_summary_ph') }}">{{ old('discharge_summary') }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('admissions.discharge_instructions') }}</label>
                        <textarea name="discharge_instructions" class="form-control" rows="4"
                                  placeholder="{{ __('admissions.discharge_instructions_ph') }}">{{ old('discharge_instructions') }}</textarea>
                    </div>

                    <div class="text-end">
                        <a href="{{ $workspaceRoutes->route('admin.admissions.show', $admission) }}" class="btn btn-secondary me-2">{{ __('admissions.cancel') }}</a>
                        <button type="submit" class="btn btn-warning"
                                onclick="return confirm('{{ __('admissions.discharge_confirm') }}')">
                            <i class="ti ti-logout me-1"></i>{{ __('admissions.discharge_patient_btn') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        @can('admission.discharge.readiness.view')
        <div class="mb-3">
            @include('admissions.partials.discharge-readiness-tab')
        </div>
        @endcan

        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('admissions.admission_summary') }}</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive"><table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted">{{ __('admissions.admission_no') }}</td>
                        <td class="fw-medium">{{ $admission->admission_number }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('admissions.admitted_on') }}</td>
                        <td>{{ $admission->admission_date->format('d M Y, H:i') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('admissions.admitted_by') }}</td>
                        <td>{{ $admission->admittedBy->name ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('admissions.days') }}</td>
                        <td>{{ $admission->length_of_stay }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('admissions.ward_rounds_count') }}</td>
                        <td>{{ $admission->wardRounds->count() }}</td>
                    </tr>
                </table></div>

                @if($admission->admitting_diagnosis)
                <hr>
                <h6 class="text-muted mb-1">{{ __('admissions.admitting_diagnosis_lbl') }}</h6>
                <p class="mb-0">{{ $admission->admitting_diagnosis }}</p>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ti ti-cash me-1"></i>{{ __('admissions.estimated_bed_charges') }}</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive"><table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted">{{ __('admissions.daily_rate') }}</td>
                        <td class="fw-medium">GH₵ {{ number_format($admission->bed->daily_rate, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('admissions.days') }}</td>
                        <td>{{ max(1, $admission->length_of_stay) }}</td>
                    </tr>
                    <tr class="border-top">
                        <td class="fw-bold">{{ __('admissions.total_estimate') }}</td>
                        <td class="fw-bold text-primary">GH₵ {{ number_format($admission->bed->daily_rate * max(1, $admission->length_of_stay), 2) }}</td>
                    </tr>
                </table></div>
            </div>
        </div>
    </div>
</div>
@endsection
