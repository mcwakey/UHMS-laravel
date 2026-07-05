@php($editing = isset($section) && $section)
@if($editing)
    <td><input name="section_key" class="form-control form-control-sm" value="{{ old('section_key', $section->section_key) }}" required></td>
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
