@props([
    'insuranceProviders' => collect(),
    'modalId' => 'insuranceModal',
    'formId' => 'insuranceForm',
    'patientInputId' => 'insuranceFormPatientId',
    'insuranceInputId' => 'insuranceFormInsuranceId',
    'titleId' => 'insuranceModalTitle',
    'feedbackId' => 'insuranceFormFeedback',
    'providerSelectId' => 'insuranceProviderSelect',
    'tierSelectId' => 'insuranceTierSelect',
    'primaryCheckboxId' => 'insIsPrimary',
    'saveButtonId' => 'insuranceFormSaveBtn',
    'title' => null,
    'dialogClass' => 'modal-lg',
])

@php
    $title ??= __('visits.add_insurance_title');
@endphp

<div {{ $attributes->merge(['class' => 'modal fade', 'id' => $modalId, 'tabindex' => '-1', 'aria-hidden' => 'true']) }}>
    <div class="modal-dialog {{ $dialogClass }}">
        <div class="modal-content">
            <form id="{{ $formId }}" autocomplete="off">
                @csrf
                <input type="hidden" id="{{ $patientInputId }}" name="_patient_id">
                <input type="hidden" id="{{ $insuranceInputId }}" name="_insurance_id">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="ti ti-shield-plus me-1"></i><span id="{{ $titleId }}">{{ $title }}</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
                </div>
                <div class="modal-body">
                    <div id="{{ $feedbackId }}" class="alert d-none" role="alert"></div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('visits.insurance_provider_label') }} <span class="text-danger">*</span></label>
                            <select name="insurance_provider_id" id="{{ $providerSelectId }}" class="form-select" required>
                                <option value="">{{ __('visits.select_provider_opt') }}</option>
                                @foreach($insuranceProviders as $prov)
                                    <option value="{{ $prov->id }}"
                                            data-type="{{ $prov->type?->value }}"
                                            data-tiers='@json($prov->tiers->map(fn($t) => ["id" => $t->id, "name" => $t->name]))'>
                                        {{ $prov->name }} ({{ $prov->type?->translatedLabel() }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ __('visits.tier_label') }}</label>
                            <select name="insurance_tier_id" id="{{ $tierSelectId }}" class="form-select">
                                <option value="">{{ __('visits.tier_default') }}</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ __('visits.membership_number_label') }}</label>
                            <input type="text" name="membership_number" class="form-control" maxlength="50">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ __('visits.policy_number_label') }}</label>
                            <input type="text" name="policy_number" class="form-control" maxlength="50">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ __('visits.ccc_code_label') }} <small class="text-muted">{{ __('visits.optional_label') }}</small></label>
                            <input type="text" name="ccc_code" class="form-control" maxlength="64">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ __('visits.member_type_label') }}</label>
                            <select name="member_type" class="form-select">
                                <option value="holder" selected>{{ __('visits.card_holder_opt') }}</option>
                                <option value="beneficiary">{{ __('visits.beneficiary_opt') }}</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ __('visits.expiry_date_label') }}</label>
                            <input type="date" name="expiry_date" class="form-control">
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_primary" value="1" id="{{ $primaryCheckboxId }}">
                                <label class="form-check-label" for="{{ $primaryCheckboxId }}">{{ __('visits.set_primary_label') }}</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('visits.cancel_btn') }}</button>
                    <button type="submit" class="btn btn-primary" id="{{ $saveButtonId }}">
                        <i class="ti ti-device-floppy me-1"></i>{{ __('visits.save_insurance_btn') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
