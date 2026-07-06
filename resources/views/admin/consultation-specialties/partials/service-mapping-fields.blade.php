@php($editing = isset($mapping) && $mapping)
@if($editing)
    <td>
        <select name="consultation_specialty_profile_id" class="form-select form-select-sm" required>
            @foreach($profiles as $profileOption)<option value="{{ $profileOption->id }}" @selected($mapping->consultation_specialty_profile_id === $profileOption->id)>{{ $profileOption->name }}</option>@endforeach
        </select>
    </td>
    <td>
        <select name="service_id" class="form-select form-select-sm" required>
            @foreach($services as $service)<option value="{{ $service->id }}" @selected($mapping->service_id === $service->id)>{{ $service->name }}</option>@endforeach
        </select>
    </td>
    <td>
        <select name="mapping_context" class="form-select form-select-sm mb-1">@foreach($contexts as $context)<option value="{{ $context }}" @selected($mapping->mapping_context === $context)>{{ __('consultation_specialties.billing.contexts.'.$context) }}</option>@endforeach</select>
        <select name="billing_trigger" class="form-select form-select-sm">@foreach($triggers as $trigger)<option value="{{ $trigger }}" @selected($mapping->billing_trigger === $trigger)>{{ __('consultation_specialties.billing.triggers.'.$trigger) }}</option>@endforeach</select>
    </td>
    <td>
        <select name="department_id" class="form-select form-select-sm mb-1">
            <option value="">{{ __('consultation_specialties.admin.none') }}</option>
            @foreach($departments as $department)<option value="{{ $department->id }}" @selected($mapping->department_id === $department->id)>{{ $department->name }}</option>@endforeach
        </select>
        <select name="department_type" class="form-select form-select-sm">
            <option value="">{{ __('consultation_specialties.admin.none') }}</option>
            @foreach($departmentTypes as $type)<option value="{{ $type->value }}" @selected($mapping->department_type === $type->value)>{{ method_exists($type, 'label') ? $type->label() : $type->value }}</option>@endforeach
        </select>
    </td>
    <td><input type="number" min="0" name="priority" class="form-control form-control-sm" value="{{ $mapping->priority }}"></td>
    <td>
        <div class="form-check"><input type="hidden" name="is_default" value="0"><input class="form-check-input" type="checkbox" name="is_default" value="1" @checked($mapping->is_default)><label class="form-check-label">{{ __('consultation_specialties.billing.admin.default') }}</label></div>
        <div class="form-check"><input class="form-check-input" type="checkbox" name="auto_bill" value="1" @checked($mapping->auto_bill)><label class="form-check-label">{{ __('consultation_specialties.billing.auto_bill') }}</label></div>
        <div class="form-check"><input class="form-check-input" type="checkbox" name="auto_bill_acknowledged" value="1"><label class="form-check-label">{{ __('consultation_specialties.billing.admin.auto_bill_acknowledged') }}</label></div>
    </td>
    <td>
        <div class="form-check"><input type="hidden" name="requires_confirmation" value="0"><input class="form-check-input" type="checkbox" name="requires_confirmation" value="1" @checked($mapping->requires_confirmation)><label class="form-check-label">{{ __('consultation_specialties.billing.requires_confirmation') }}</label></div>
        <div class="form-check"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($mapping->is_active)><label class="form-check-label">{{ __('consultation_specialties.admin.active') }}</label></div>
    </td>
@else
    <div class="col-md-2">
        <label class="form-label">{{ __('consultation_specialties.admin.profile') }}</label>
        <select name="consultation_specialty_profile_id" class="form-select" required>@foreach($profiles as $profileOption)<option value="{{ $profileOption->id }}">{{ $profileOption->name }}</option>@endforeach</select>
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('consultation_specialties.billing.default_service') }}</label>
        <select name="service_id" class="form-select" required>@foreach($services as $service)<option value="{{ $service->id }}">{{ $service->name }} ({{ $service->code }})</option>@endforeach</select>
    </div>
    <div class="col-md-2">
        <label class="form-label">{{ __('consultation_specialties.billing.admin.context') }}</label>
        <select name="mapping_context" class="form-select">@foreach($contexts as $context)<option value="{{ $context }}">{{ __('consultation_specialties.billing.contexts.'.$context) }}</option>@endforeach</select>
    </div>
    <div class="col-md-2">
        <label class="form-label">{{ __('consultation_specialties.billing.admin.trigger') }}</label>
        <select name="billing_trigger" class="form-select">@foreach($triggers as $trigger)<option value="{{ $trigger }}">{{ __('consultation_specialties.billing.triggers.'.$trigger) }}</option>@endforeach</select>
    </div>
    <div class="col-md-1">
        <label class="form-label">{{ __('consultation_specialties.billing.admin.priority') }}</label>
        <input type="number" min="0" name="priority" class="form-control" value="0">
    </div>
    <div class="col-md-1">
        <div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="is_default" value="1" checked><label class="form-check-label">{{ __('consultation_specialties.billing.admin.default') }}</label></div>
        <div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked><label class="form-check-label">{{ __('consultation_specialties.admin.active') }}</label></div>
    </div>
@endif
