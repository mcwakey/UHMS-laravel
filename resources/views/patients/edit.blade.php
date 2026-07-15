@extends('layouts.app')
@section('title', __('patients.edit_patient') . ' - ' . $patient->full_name)

@section('content')
<x-page-header-back
    :title="__('patients.edit_patient')"
    :href="$workspaceRoutes->route('admin.patients.show', $patient)"
/>
@php
    $phonePattern = $countrySettings['phone_pattern'] ?? null;
    $phonePlaceholder = $countrySettings['phone_placeholder'] ?? '';
    $digitalAddressPattern = $countrySettings['digital_address_pattern'] ?? null;
    $digitalAddressPlaceholder = $countrySettings['digital_address_placeholder'] ?? '';
@endphp

<form method="POST" action="{{ $workspaceRoutes->route('admin.patients.update', $patient) }}" enctype="multipart/form-data">
    @csrf @method('PUT')

    <x-patient-personal-information-card
        :patient="$patient"
        :occupations="$occupations"
    />

    <x-patient-contact-identification-card
        :patient="$patient"
        :phone-pattern="$phonePattern"
        :phone-placeholder="$phonePlaceholder"
    />

    <x-patient-address-information-card
        :patient="$patient"
        :regions="$regions"
        :digital-address-pattern="$digitalAddressPattern"
        :digital-address-placeholder="$digitalAddressPlaceholder"
    />
    <x-patient-medical-notes-card
        :patient="$patient"
        :show-placeholders="false"
    />
    <!-- Submit -->
    <div class="d-flex justify-content-end gap-2 mb-4">
        <a href="{{ $workspaceRoutes->route('admin.patients.show', $patient) }}" class="btn btn-outline-secondary">{{ __('common.cancel') }}</a>
        <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>{{ __('patients.update_patient') }}</button>
    </div>
</form>

@push('scripts')
@include('patients.partials.avatar-camera-scripts')
@include('patients.partials.registration-input-scripts')
@endpush
@endsection
