@props([
    'field',
    'value' => null,
    'fallback' => '-',
    'mode' => 'display',
    'patient' => null,
])

@php
    $privacy = app(\App\Services\PatientPrivacyService::class);
    $displayValue = $mode === 'export'
        ? $privacy->displayForExport($field, $value)
        : ($patient ? $privacy->displayForPatient($field, $value, $patient) : $privacy->display($field, $value));
@endphp

{{ filled($displayValue) ? $displayValue : $fallback }}
