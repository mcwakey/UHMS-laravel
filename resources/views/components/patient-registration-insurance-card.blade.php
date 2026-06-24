@props([
    'registrationInsuranceTypes' => collect(),
])

@php
    $registrationInsuranceTypes = collect($registrationInsuranceTypes);
@endphp

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="fw-bold mb-0"><i class="ti ti-shield-check me-1"></i>{{ __('patients.tab_insurance') }}</h5>

        <button type="button" class="btn btn-sm btn-outline-primary mt-1" id="add-ins-btn">
            <i class="ti ti-plus me-1"></i>{{ __('patients.add_another_insurance') }}
        </button>
    </div>
    <div class="card-body">
        <div class="alert alert-info small py-2 mb-3">
            <i class="ti ti-info-circle me-1"></i>
            {!! __('patients.cash_carry_info', ['strong' => '<strong>Cash &amp; Carry</strong>']) !!}
        </div>
        <div id="ins-wrapper">
            <div class="ins-row border rounded p-3 mb-2" data-index="0">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-medium small text-muted ins-label">{{ __('patients.tab_insurance') }} #1 <span class="badge bg-primary ms-1">{{ __('patients.primary_contact_badge') }}</span></span>
                    <button aria-label="Delete" title="Delete" type="button" class="btn btn-sm btn-outline-danger remove-ins d-none"><i class="ti ti-trash"></i></button>
                </div>
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label form-label-sm">{{ __('patients.insurance_type') }}</label>
                        <select name="insurances[0][type]" class="form-select form-select-sm ins-type" data-idx="0" data-selected-provider="{{ old('insurances.0.provider_id') }}" data-selected-tier="{{ old('insurances.0.insurance_tier_id') }}">
                            <option value="">{{ __('patients.insurance_none') }}</option>
                            @foreach($registrationInsuranceTypes as $type)
                                <option value="{{ $type['value'] }}" {{ old('insurances.0.type') === $type['value'] ? 'selected' : '' }}>{{ $type['label'] }}</option>
                            @endforeach
                        </select>
                        @error('insurances.0.type')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label form-label-sm">{{ __('patients.insurance_provider_label') }}</label>
                        <select name="insurances[0][provider_id]" class="form-select form-select-sm ins-provider" data-idx="0" disabled>
                            <option value="">{{ __('patients.select_type_first') }}</option>
                        </select>
                        @error('insurances.0.provider_id')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label form-label-sm">{{ __('patients.insurance_tier') }}</label>
                        <select name="insurances[0][insurance_tier_id]" class="form-select form-select-sm ins-tier" data-idx="0" disabled>
                            <option value="">{{ __('patients.select_type_first') }}</option>
                        </select>
                        @error('insurances.0.insurance_tier_id')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 ins-row-extra" style="display:none">
                        <label class="form-label form-label-sm">{{ __('patients.membership_card_number') }}</label>
                        <input type="text" name="insurances[0][membership_number]" class="form-control form-control-sm" value="{{ old('insurances.0.membership_number') }}" placeholder="e.g. INS-123456789">
                    </div>
                    <div class="col-md-4 ins-row-extra" style="display:none">
                        <label class="form-label form-label-sm">{{ __('patients.policy_number') }}</label>
                        <input type="text" name="insurances[0][policy_number]" class="form-control form-control-sm" value="{{ old('insurances.0.policy_number') }}">
                    </div>
                    <div class="col-md-4 ins-row-extra" style="display:none">
                        <label class="form-label form-label-sm">{{ __('patients.expiry_date') }}</label>
                        <input type="date" name="insurances[0][expiry_date]" class="form-control form-control-sm" value="{{ old('insurances.0.expiry_date') }}">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
