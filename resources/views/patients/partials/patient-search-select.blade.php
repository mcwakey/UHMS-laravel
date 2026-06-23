{{--
    Reusable AJAX patient search (select2) — the SINGLE source of truth for
    "find a patient" UI across Visit Create, Emergency Create, etc. Searches by
    name, folder number, phone, email, ID card and insurance membership number
    via the shared `admin.visits.patient-search` endpoint.

    Params (all optional):
      $id          DOM id (default 'patientSearch'); also the hidden field id + JS hook prefix
      $name        hidden input name (default 'patient_id')
      $label       field label (default translated 'Search Patient')
      $placeholder placeholder text
      $required    show the red asterisk (default false)
      $emptyOption text for the blank option (e.g. 'Create temporary emergency patient')
      $selected    a Patient model to pre-select (e.g. on validation error / existing visit)
      $searchRoute search endpoint (default admin.visits.patient-search)
--}}
@php
    $id = $id ?? 'patientSearch';
    $name = $name ?? 'patient_id';
    $label = $label ?? __('patients.search_patient');
    $placeholder = $placeholder ?? __('patients.search_patient_ph');
    $required = $required ?? false;
    $emptyOption = $emptyOption ?? null;
    $selected = $selected ?? null;
    $searchRoute = $searchRoute ?? route('admin.visits.patient-search');
@endphp
<div class="mb-2" data-patient-search="{{ $id }}" data-search-url="{{ $searchRoute }}">
    <label class="form-label">{{ $label }} @if($required)<span class="text-danger">*</span>@endif</label>
    <select id="{{ $id }}"
            class="form-select form-select-lg @error($name) is-invalid @enderror"
            data-placeholder="{{ $placeholder }}"
            data-empty-option="{{ $emptyOption }}"
            style="width:100%">
        <option value="">{{ $emptyOption }}</option>
        @if($selected)
            <option value="{{ $selected->id }}" selected>{{ $selected->patient_number }} - {{ $selected->full_name }}</option>
        @endif
    </select>
    <input type="hidden" name="{{ $name }}" id="{{ $id }}Value" value="{{ $selected?->id ?? old($name) }}">
    @error($name)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
</div>
