@props([
    'patient' => null,
    'insuranceProviders',
    'formAction' => null,
    'formId' => 'addInsuranceForm',
    'modalId' => 'addInsuranceModal',
    'title' => null,
    'submitLabel' => null,
])

@php
    $title = $title ?? __('patients.add_insurance');
    $submitLabel = $submitLabel ?? __('patients.add_insurance');
    $formAction = $formAction ?: ($patient ? route('admin.patients.insurances.store', $patient) : '#');
    $addableInsuranceTypes = $insuranceProviders
        ->where('is_default', false)
        ->pluck('type')
        ->filter()
        ->unique(fn ($type) => $type instanceof \BackedEnum ? $type->value : (string) $type)
        ->sortBy(fn ($type) => $type instanceof \BackedEnum && method_exists($type, 'label') ? $type->label() : ucfirst((string) ($type instanceof \BackedEnum ? $type->value : $type)));

    $insuranceI18n = [
        'select_type_first' => __('patients.select_type_first'),
        'loading_providers' => __('patients.loading_providers'),
        'no_providers'      => __('patients.no_providers'),
        'select_provider'   => __('patients.select_provider'),
        'loading_tiers'     => __('patients.loading_tiers'),
        'no_tiers'          => __('patients.no_tiers'),
        'select_tier'       => __('patients.select_tier'),
    ];
@endphp
<script>const insuranceI18n = @json($insuranceI18n);</script>

<div class="modal fade" id="{{ $modalId }}" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ $formAction }}" id="{{ $formId }}" autocomplete="off">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ $title }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="addInsuranceFormFeedback" class="alert d-none" role="alert"></div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('patients.insurance_type') }} <span class="text-danger">*</span></label>
                            <select id="addInsType" class="form-select" required>
                                <option value="">{{ __('patients.select') }}</option>
                                @foreach($addableInsuranceTypes as $type)
                                    @php
                                        $typeValue = $type instanceof \BackedEnum ? $type->value : (string) $type;
                                        $typeLabel = $type instanceof \BackedEnum && method_exists($type, 'label') ? $type->label() : ucfirst($typeValue);
                                    @endphp
                                    <option value="{{ $typeValue }}">{{ $typeLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('patients.insurance_provider_label') }} <span class="text-danger">*</span></label>
                            <select name="insurance_provider_id" id="addInsProvider" class="form-select" required disabled>
                                <option value="">{{ __('patients.select_type_first') }}</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('patients.insurance_tier') }} <span class="text-danger">*</span></label>
                            <select name="insurance_tier_id" id="addInsTier" class="form-select" disabled>
                                <option value="">{{ __('patients.select_type_first') }}</option>
                            </select>
                            <div id="addInsTierInfo" class="small text-muted mt-1">{{ __('patients.default_tier_info') }}</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('patients.member_type') }} <span class="text-danger">*</span></label>
                            <div class="d-flex gap-3 mt-1">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="member_type" id="addMemberHolder" value="holder" checked>
                                    <label class="form-check-label" for="addMemberHolder">
                                        <span class="badge bg-info">{{ __('patients.card_holder') }}</span>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="member_type" id="addMemberBeneficiary" value="beneficiary">
                                    <label class="form-check-label" for="addMemberBeneficiary">
                                        <span class="badge bg-warning text-dark">{{ __('patients.beneficiary') }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('patients.membership_number') }}</label>
                            <input type="text" name="membership_number" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('patients.policy_number') }}</label>
                            <input type="text" name="policy_number" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('patients.expiry_date') }}</label>
                            <input type="date" name="expiry_date" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3 d-flex align-items-end">
                            <div class="form-check">
                                <input type="checkbox" name="is_primary" value="1" class="form-check-input" id="addInsPrimary">
                                <label class="form-check-label" for="addInsPrimary">{{ __('patients.set_as_primary_insurance') }}</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary" id="addInsuranceSubmitBtn">{{ $submitLabel }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
