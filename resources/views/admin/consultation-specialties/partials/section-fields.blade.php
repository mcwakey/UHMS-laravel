@php($editing = isset($section) && $section)
@php($aliasTarget = $editing ? (($sectionAliasMap ?? [])[$section->section_key] ?? null) : null)
@php($isCanonicalComplaints = $editing && $section->section_key === 'complaints' && ! empty($complaintDisplayLabel) && $complaintDisplayLabel !== __('consultation_specialties.sections.complaints'))
@if($editing)
    <td>
        <input name="section_key" class="form-control form-control-sm" value="{{ old('section_key', $section->section_key) }}" required>
        @if($aliasTarget)
            <div class="mt-1 d-flex flex-wrap gap-1" title="{{ __('consultation_specialties.admin.hidden_from_doctor_workspace') }}">
                <span class="badge bg-secondary-subtle text-secondary">{{ __('consultation_specialties.admin.deprecated_duplicate') }}</span>
                <span class="badge bg-info-subtle text-info">{{ __('consultation_specialties.admin.maps_to', ['section' => __('consultation_specialties.sections.'.$aliasTarget)]) }}</span>
            </div>
        @endif
        @if($isCanonicalComplaints)
            <div class="mt-1">
                <span class="badge bg-primary-subtle text-primary">{{ __('consultation_specialties.admin.displayed_as', ['label' => $complaintDisplayLabel]) }}</span>
            </div>
        @endif
    </td>
    <td><input name="label" class="form-control form-control-sm" value="{{ old('label', $section->label) }}" required></td>
    <td>
        <select name="component" class="form-select form-select-sm">
            <option value="">{{ __('consultation_specialties.admin.generic_shell') }}</option>
            @foreach($components as $component)
                <option value="{{ $component }}" @selected(old('component', $section->component) === $component)>{{ $component }}</option>
            @endforeach
        </select>
    </td>
    <td><input type="number" min="0" name="display_order" class="form-control form-control-sm" value="{{ old('display_order', $section->display_order) }}"></td>
    <td>
        <div class="form-check"><input class="form-check-input" type="checkbox" name="is_visible" value="1" @checked(old('is_visible', $section->is_visible))><label class="form-check-label">{{ __('consultation_specialties.admin.visible') }}</label></div>
        <div class="form-check"><input class="form-check-input" type="checkbox" name="is_required" value="1" @checked(old('is_required', $section->is_required))><label class="form-check-label">{{ __('consultation_specialties.admin.required') }}</label></div>
    </td>
@else
    <div class="col-md-2">
        <label class="form-label">{{ __('consultation_specialties.admin.section_key') }}</label>
        <input list="section-key-options" name="section_key" class="form-control" value="{{ old('section_key') }}" required>
        <datalist id="section-key-options">@foreach($sectionKeys as $key)<option value="{{ $key }}"></option>@endforeach</datalist>
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('consultation_specialties.admin.label') }}</label>
        <input name="label" class="form-control" value="{{ old('label') }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('consultation_specialties.admin.component') }}</label>
        <select name="component" class="form-select">
            <option value="">{{ __('consultation_specialties.admin.generic_shell') }}</option>
            @foreach($components as $component)<option value="{{ $component }}">{{ $component }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-1">
        <label class="form-label">{{ __('consultation_specialties.admin.sort_order') }}</label>
        <input type="number" min="0" name="display_order" class="form-control" value="{{ old('display_order', 0) }}">
    </div>
    <div class="col-md-1">
        <div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="is_visible" value="1" checked><label class="form-check-label">{{ __('consultation_specialties.admin.visible') }}</label></div>
        <div class="form-check"><input class="form-check-input" type="checkbox" name="is_required" value="1"><label class="form-check-label">{{ __('consultation_specialties.admin.required') }}</label></div>
    </div>
@endif
