@php($editing = isset($favorite) && $favorite)
@if($editing)
    <td>
        <select name="favorite_type" class="form-select form-select-sm" required>
            @foreach($types as $type)<option value="{{ $type }}" @selected($favorite->favorite_type === $type)>{{ __('consultation_specialties.admin.favorite_types.'.$type) }}</option>@endforeach
        </select>
    </td>
    <td><input name="label" class="form-control form-control-sm" value="{{ $favorite->label }}" required></td>
    <td><input name="code" class="form-control form-control-sm" value="{{ $favorite->code }}"></td>
    <td>
        <select name="favoritable_type" class="form-select form-select-sm mb-1">
            <option value="">{{ __('consultation_specialties.admin.label_only_suggestion') }}</option>
            @foreach($favoritableTypes as $type)<option value="{{ $type }}" @selected($favorite->favoritable_type === $type)>{{ class_basename($type) }}</option>@endforeach
        </select>
        <input type="number" min="1" name="favoritable_id" class="form-control form-control-sm" value="{{ $favorite->favoritable_id }}">
    </td>
    <td><input type="number" min="0" name="sort_order" class="form-control form-control-sm" value="{{ $favorite->sort_order }}"></td>
    <td><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($favorite->is_active)><label class="form-check-label">{{ __('consultation_specialties.admin.active') }}</label></div></td>
@else
    <div class="col-md-2">
        <label class="form-label">{{ __('consultation_specialties.admin.type') }}</label>
        <select name="favorite_type" class="form-select" required>@foreach($types as $type)<option value="{{ $type }}">{{ __('consultation_specialties.admin.favorite_types.'.$type) }}</option>@endforeach</select>
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('consultation_specialties.admin.label') }}</label>
        <input name="label" class="form-control" required>
    </div>
    <div class="col-md-2">
        <label class="form-label">{{ __('consultation_specialties.admin.code') }}</label>
        <input name="code" class="form-control">
    </div>
    <div class="col-md-2">
        <label class="form-label">{{ __('consultation_specialties.admin.linked_catalogue_item') }}</label>
        <select name="favoritable_type" class="form-select">
            <option value="">{{ __('consultation_specialties.admin.label_only_suggestion') }}</option>
            @foreach($favoritableTypes as $type)<option value="{{ $type }}">{{ class_basename($type) }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-1">
        <label class="form-label">{{ __('consultation_specialties.admin.id') }}</label>
        <input type="number" min="1" name="favoritable_id" class="form-control">
    </div>
    <div class="col-md-1">
        <label class="form-label">{{ __('consultation_specialties.admin.sort_order') }}</label>
        <input type="number" min="0" name="sort_order" class="form-control" value="0">
    </div>
    <input type="hidden" name="is_active" value="1">
@endif
