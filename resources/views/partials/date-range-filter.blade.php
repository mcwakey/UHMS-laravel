@php
    $id = $id ?? 'dateRangePicker';
    $name = $name ?? 'date_range';
    $value = $value ?? '';
    $label = $label ?? 'Date Range';
    $labelClass = $labelClass ?? '';
    $submitOnApply = ! empty($submitOnApply);
@endphp

<label class="form-label {{ $labelClass }}">{{ $label }}</label>
<input type="hidden" name="{{ $name }}" id="{{ $id }}Value" value="{{ $value }}">
<div
    id="{{ $id }}"
    class="reportrange-picker uhms-date-range-filter d-flex align-items-center justify-content-between w-100"
    data-input="#{{ $id }}Value"
    data-submit-on-apply="{{ $submitOnApply ? 'true' : 'false' }}"
    role="button"
    tabindex="0"
>
    <span class="d-flex align-items-center text-nowrap overflow-hidden">
        <i class="ti ti-calendar text-gray-5 fs-14 me-1"></i>
        <span class="reportrange-picker-field text-truncate">Select date range</span>
    </span>
    <i class="ti ti-chevron-down text-gray-5 ms-2"></i>
</div>
