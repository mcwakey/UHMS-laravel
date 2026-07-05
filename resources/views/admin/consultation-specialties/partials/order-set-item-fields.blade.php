@php($editing = isset($item) && $item)
@if($editing)
    <td>
        <select name="item_type" class="form-select form-select-sm" required>@foreach($itemTypes as $type)<option value="{{ $type }}" @selected($item->item_type === $type)>{{ __('consultation_specialties.admin.item_types.'.$type) }}</option>@endforeach</select>
    </td>
    <td><input name="label" class="form-control form-control-sm" value="{{ $item->label }}" required></td>
    <td>
        <select name="apply_mode" class="form-select form-select-sm">@foreach($applyModes as $mode)<option value="{{ $mode }}" @selected($item->apply_mode === $mode)>{{ __('consultation_specialties.admin.apply_modes.'.$mode) }}</option>@endforeach</select>
    </td>
    <td>
        <select name="target_section" class="form-select form-select-sm mb-1">
            <option value="">{{ __('consultation_specialties.admin.none') }}</option>
            @foreach($sectionKeys as $key)<option value="{{ $key }}" @selected($item->target_section === $key)>{{ $key }}</option>@endforeach
        </select>
        <input name="target_field" class="form-control form-control-sm" value="{{ $item->target_field }}" placeholder="{{ __('consultation_specialties.admin.target_field') }}">
        <textarea name="payload_json" class="form-control form-control-sm font-monospace mt-1" rows="2">{{ $item->payload ? json_encode($item->payload) : '' }}</textarea>
    </td>
    <td><input type="number" min="0" name="sort_order" class="form-control form-control-sm" value="{{ $item->sort_order }}"></td>
    <td>
        <div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($item->is_active)><label class="form-check-label">{{ __('consultation_specialties.admin.active') }}</label></div>
        <div class="form-check"><input class="form-check-input" type="checkbox" name="is_required" value="1" @checked($item->is_required)><label class="form-check-label">{{ __('consultation_specialties.admin.required') }}</label></div>
    </td>
@else
    <div class="col-md-2">
        <label class="form-label">{{ __('consultation_specialties.admin.type') }}</label>
        <select name="item_type" class="form-select" required>@foreach($itemTypes as $type)<option value="{{ $type }}">{{ __('consultation_specialties.admin.item_types.'.$type) }}</option>@endforeach</select>
    </div>
    <div class="col-md-2">
        <label class="form-label">{{ __('consultation_specialties.admin.label') }}</label>
        <input name="label" class="form-control" required>
    </div>
    <div class="col-md-2">
        <label class="form-label">{{ __('consultation_specialties.admin.apply_mode') }}</label>
        <select name="apply_mode" class="form-select">@foreach($applyModes as $mode)<option value="{{ $mode }}">{{ __('consultation_specialties.admin.apply_modes.'.$mode) }}</option>@endforeach</select>
    </div>
    <div class="col-md-2">
        <label class="form-label">{{ __('consultation_specialties.admin.target_section') }}</label>
        <select name="target_section" class="form-select">
            <option value="">{{ __('consultation_specialties.admin.none') }}</option>
            @foreach($sectionKeys as $key)<option value="{{ $key }}">{{ $key }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-1">
        <label class="form-label">{{ __('consultation_specialties.admin.target_field') }}</label>
        <input name="target_field" class="form-control">
    </div>
    <div class="col-md-2">
        <label class="form-label">{{ __('consultation_specialties.admin.payload') }}</label>
        <textarea name="payload_json" class="form-control font-monospace" rows="1"></textarea>
    </div>
    <input type="hidden" name="is_active" value="1">
@endif
