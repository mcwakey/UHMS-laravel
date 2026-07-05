@php($editing = isset($mapping) && $mapping)
@if($editing)
    <td>
        <select name="consultation_specialty_profile_id" class="form-select form-select-sm" required>
            @foreach($profiles as $profileOption)<option value="{{ $profileOption->id }}" @selected($mapping->consultation_specialty_profile_id === $profileOption->id)>{{ $profileOption->name }}</option>@endforeach
        </select>
    </td>
    <td>
        <select name="department_id" class="form-select form-select-sm">
            <option value="">{{ __('consultation_specialties.admin.none') }}</option>
            @foreach($departments as $department)<option value="{{ $department->id }}" @selected($mapping->department_id === $department->id)>{{ $department->name }}</option>@endforeach
        </select>
    </td>
    <td>
        <select name="department_type" class="form-select form-select-sm">
            <option value="">{{ __('consultation_specialties.admin.none') }}</option>
            @foreach($departmentTypes as $type)<option value="{{ $type->value }}" @selected($mapping->department_type === $type->value)>{{ method_exists($type, 'label') ? $type->label() : $type->value }}</option>@endforeach
        </select>
    </td>
    <td><input type="number" min="0" name="priority" class="form-control form-control-sm" value="{{ $mapping->priority }}"></td>
    <td><input name="source" class="form-control form-control-sm" value="{{ $mapping->source }}"></td>
    <td><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($mapping->is_active)><label class="form-check-label">{{ __('consultation_specialties.admin.active') }}</label></div></td>
@else
    <div class="col-md-3">
        <label class="form-label">{{ __('consultation_specialties.admin.profile') }}</label>
        <select name="consultation_specialty_profile_id" class="form-select" required>
            @foreach($profiles as $profileOption)<option value="{{ $profileOption->id }}">{{ $profileOption->name }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label">{{ __('consultation_specialties.admin.department') }}</label>
        <select name="department_id" class="form-select">
            <option value="">{{ __('consultation_specialties.admin.none') }}</option>
            @foreach($departments as $department)<option value="{{ $department->id }}">{{ $department->name }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label">{{ __('consultation_specialties.admin.department_type') }}</label>
        <select name="department_type" class="form-select">
            <option value="">{{ __('consultation_specialties.admin.none') }}</option>
            @foreach($departmentTypes as $type)<option value="{{ $type->value }}">{{ method_exists($type, 'label') ? $type->label() : $type->value }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-1">
        <label class="form-label">{{ __('consultation_specialties.admin.priority') }}</label>
        <input type="number" min="0" name="priority" class="form-control" value="0">
    </div>
    <div class="col-md-2">
        <label class="form-label">{{ __('consultation_specialties.admin.source') }}</label>
        <input name="source" class="form-control" value="admin">
    </div>
    <div class="col-md-1">
        <div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked><label class="form-check-label">{{ __('consultation_specialties.admin.active') }}</label></div>
    </div>
@endif
