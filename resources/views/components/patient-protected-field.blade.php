@props([
    'field',
    'value' => null,
    'fallback' => '-',
    'mode' => 'display',
])

@php
    $privacy = app(\App\Services\PatientPrivacyService::class);
    $displayValue = $mode === 'export'
        ? $privacy->displayForExport($field, $value)
        : $privacy->display($field, $value);
@endphp

{{ filled($displayValue) ? $displayValue : $fallback }}
