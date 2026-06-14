@extends('layouts.app')
@section('title', 'Donation · '.$donation->donation_number)

@php($unit = $donation->unit)
@php($resultOptions = ['NOT_DONE','NEGATIVE','NON_REACTIVE','POSITIVE','REACTIVE','INCONCLUSIVE'])

@section('content')
<x-page-header :title="'Donation '.$donation->donation_number" description="Collection record, unit status, and infectious-disease screening." icon="ti-microscope"
    :breadcrumbs="[['label' => __('blood_bank.blood_donations'), 'url' => route('admin.blood-bank.donations.index')], ['label' => $donation->donation_number]]">
    <x-slot:actions>
        <a href="{{ route('admin.blood-bank.donations.index') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-arrow-left me-1"></i>{{ __('blood_bank.all_donations') }}</a>
        @if($donation->donor)<a href="{{ route('admin.blood-bank.donors.show', $donation->donor) }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-user me-1"></i>{{ __('blood_bank.donor_profile') }}</a>@endif
    </x-slot:actions>
</x-page-header>

<div class="row g-3">
    {{-- Donation + unit summary --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-white"><h6 class="card-title mb-0"><i class="ti ti-droplet me-1"></i>{{ __('blood_bank.collection') }}</h6></div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">{{ __('blood_bank.donor') }}</dt><dd class="col-7">{{ $donation->donor->full_name ?? '—' }}</dd>
                    <dt class="col-5 text-muted">{{ __('blood_bank.blood_group') }}</dt><dd class="col-7"><span class="badge bg-red-lt">{{ $donation->blood_group }}</span></dd>
                    <dt class="col-5 text-muted">{{ __('blood_bank.volume') }}</dt><dd class="col-7">{{ $donation->volume_ml }} ml</dd>
                    <dt class="col-5 text-muted">{{ __('blood_bank.collected') }}</dt><dd class="col-7">{{ $donation->donation_date?->format('d M Y H:i') }}</dd>
                    <dt class="col-5 text-muted">{{ __('blood_bank.collected_by') }}</dt><dd class="col-7">{{ $donation->collectedBy->full_name ?? '—' }}</dd>
                    <dt class="col-5 text-muted">{{ __('medication_administration.status') }}</dt><dd class="col-7"><x-status-badge :status="$donation->status" domain="screening" size="sm" /></dd>
                </dl>
                @if($donation->notes)<div class="alert alert-light border mt-2 mb-0 py-2 small">{{ $donation->notes }}</div>@endif
            </div>
        </div>

        @if($unit)
        <div class="card mt-3">
            <div class="card-header bg-white"><h6 class="card-title mb-0"><i class="ti ti-package me-1"></i>Unit {{ $unit->unit_number }}</h6></div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">{{ __('blood_bank.component') }}</dt><dd class="col-7">{{ str_replace('_',' ',$unit->component_type) }}</dd>
                    <dt class="col-5 text-muted">{{ __('medication_administration.status') }}</dt><dd class="col-7"><x-status-badge :status="$unit->status" domain="blood_unit" size="sm" /></dd>
                    <dt class="col-5 text-muted">{{ __('blood_bank.screening') }}</dt><dd class="col-7"><x-status-badge :status="$unit->screening_status" domain="screening" size="sm" /></dd>
                    <dt class="col-5 text-muted">{{ __('blood_bank.storage') }}</dt><dd class="col-7">{{ $unit->storageLocation->name ?? __('blood_bank.unassigned') }}</dd>
                    <dt class="col-5 text-muted">{{ __('blood_bank.expiry') }}</dt><dd class="col-7">{{ $unit->expiry_date?->format('d M Y') }} <span class="text-muted">({{ $unit->daysToExpiry() }}d)</span></dd>
                </dl>
            </div>
        </div>
        @endif
    </div>

    {{-- Infectious-disease screening panel --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0"><i class="ti ti-virus-search me-1"></i>{{ __('blood_bank.infectious_screening_panel') }}</h6>
                <x-status-badge :status="$donation->screening_status" domain="screening" />
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="bg-light"><tr><th>{{ __('blood_bank.test') }}</th><th>{{ __('blood_bank.result') }}</th><th>{{ __('blood_bank.performed_by') }}</th><th>{{ __('blood_bank.verified') }}</th>@can('blood_bank.screening.perform')<th class="text-end">{{ __('blood_bank.record') }}</th>@endcan</tr></thead>
                        <tbody>
                        @forelse($donation->tests as $test)
                            <tr>
                                <td>{{ $test->test_name }}@if($test->mandatory) <span class="text-danger" title="mandatory">*</span>@endif</td>
                                <td><x-status-badge :status="$test->result" domain="screening" size="sm" /></td>
                                <td class="small">{{ $test->performedBy->full_name ?? '—' }}<div class="text-muted">{{ $test->performed_at?->format('d M H:i') }}</div></td>
                                <td class="small">
                                    @if($test->verified_at)
                                        <span class="text-success"><i class="ti ti-check"></i> {{ $test->verifiedBy->full_name ?? '' }}</span>
                                    @else
                                        @can('blood_bank.screening.verify')
                                        <form method="POST" action="{{ route('admin.blood-bank.donations.tests.verify', $test) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-link btn-sm p-0" @disabled($test->result === 'NOT_DONE')>{{ __('blood_bank.verify') }}</button></form>
                                        @else <span class="text-muted">—</span> @endcan
                                    @endif
                                </td>
                                @can('blood_bank.screening.perform')
                                <td class="text-end">
                                    <form method="POST" action="{{ route('admin.blood-bank.donations.tests.store', $donation) }}" class="d-inline-flex gap-1 justify-content-end">
                                        @csrf
                                        <input type="hidden" name="test_code" value="{{ $test->test_code }}">
                                        <select name="result" class="form-select form-select-sm" style="width:auto">
                                            @foreach($resultOptions as $r)<option value="{{ $r }}" @selected($test->result === $r)>{{ str_replace('_',' ',$r) }}</option>@endforeach
                                        </select>
                                        <button class="btn btn-sm btn-outline-secondary">{{ __('blood_bank.save') }}</button>
                                    </form>
                                </td>
                                @endcan
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">{{ __('blood_bank.no_screening_tests') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer small text-muted">
                <i class="ti ti-info-circle me-1"></i>A reactive/positive mandatory result rejects the unit; all mandatory tests must be non-reactive
                @if(config('blood_bank.require_test_verification', true)) and verified @endif before the unit can be released.
            </div>
        </div>

        @can('blood_bank.screening.manage')
        <div class="card mt-3">
            <div class="card-header bg-white"><h6 class="card-title mb-0"><i class="ti ti-clipboard-check me-1"></i>{{ __('blood_bank.overall_screening_outcome') }}</h6></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.blood-bank.donations.screening', $donation) }}" class="row g-2 align-items-end">
                    @csrf @method('PATCH')
                    <div class="col-md-4"><label class="form-label small">{{ __('medication_administration.status') }}</label>
                        <select name="screening_status" class="form-select" required>
                            @foreach(['PENDING','PASSED','FAILED','INCONCLUSIVE'] as $status)<option value="{{ $status }}" @selected($donation->screening_status === $status)>{{ $status }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label small">{{ __('blood_bank.notes') }}</label><input name="screening_notes" class="form-control" value="{{ $donation->screening_notes }}" placeholder="{{ __('blood_bank.optional_notes') }}"></div>
                    <div class="col-md-2"><button class="btn btn-primary w-100">{{ __('theatre.apply') }}</button></div>
                </form>
                <p class="small text-muted mb-0 mt-2">{{ __('blood_bank.screening_outcome_help') }}</p>
            </div>
        </div>
        @endcan
    </div>
</div>
@endsection
