@props([
    'hidden' => true,
    'canAddInsurance' => false,
    'showVerification' => true,
    'title' => null,
    'fallbackLabel' => null,
    'addButtonLabel' => null,
    'loadingLabel' => null,
    'insuranceFieldName' => 'visit_insurance_id',
    'verificationFieldName' => 'insurance_verification_id',
    'cardId' => 'insuranceCard',
    'fallbackBadgeId' => 'insuranceFallbackBadge',
    'addButtonId' => 'addInsuranceBtn',
    'insuranceListId' => 'insuranceList',
    'insuranceInputId' => 'visitInsuranceId',
    'verificationInputId' => 'insuranceVerificationId',
    'verificationPanelId' => 'verificationPanel',
    'verificationStatusBadgeId' => 'verificationStatusBadge',
    'verificationProviderMetaId' => 'verificationProviderMeta',
    'verificationCodeRowId' => 'verificationCodeRow',
    'verificationReferenceInputId' => 'verificationReferenceInput',
    'runVerificationButtonId' => 'runVerificationBtn',
    'verificationManualRowId' => 'verificationManualRow',
    'runManualVerificationButtonId' => 'runVerificationBtn2',
    'verificationFeedbackId' => 'verificationFeedback',
    'selectedInsuranceId' => null,
    'selectedVerificationId' => null,
])

@php
    $title ??= __('visits.insurance_heading');
    $fallbackLabel ??= __('visits.insurance_fallback_badge');
    $addButtonLabel ??= __('visits.add_insurance_btn');
    $loadingLabel ??= __('visits.loading_insurances');
@endphp

<div {{ $attributes->merge(['class' => 'card'.($hidden ? ' d-none' : ''), 'id' => $cardId]) }}>
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="fw-bold mb-0"><i class="ti ti-shield-check me-1"></i>{{ $title }}</h5>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-warning text-dark" id="{{ $fallbackBadgeId }}" style="display:none;">{{ $fallbackLabel }}</span>
            @if($canAddInsurance)
                <button type="button" class="btn btn-sm btn-outline-primary" id="{{ $addButtonId }}">
                    <i class="ti ti-plus me-1"></i>{{ $addButtonLabel }}
                </button>
            @endif
        </div>
    </div>
    <div class="card-body">
        <div id="{{ $insuranceListId }}" class="mb-3">
            <div class="text-muted text-center py-3">
                <i class="ti ti-loader me-1"></i>{{ $loadingLabel }}
            </div>
        </div>

        {{ $slot }}

        <input type="hidden" name="{{ $insuranceFieldName }}" id="{{ $insuranceInputId }}" value="{{ old($insuranceFieldName, $selectedInsuranceId) }}">

        @if($showVerification)
            <input type="hidden" name="{{ $verificationFieldName }}" id="{{ $verificationInputId }}" value="{{ old($verificationFieldName, $selectedVerificationId) }}">

            <div id="{{ $verificationPanelId }}" class="border rounded p-3 mt-3 d-none">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="mb-0"><i class="ti ti-shield-lock me-1"></i>{{ __('visits.verification') }}</h6>
                    <span class="badge bg-secondary" id="{{ $verificationStatusBadgeId }}">{{ __('visits.not_started') }}</span>
                </div>
                <div class="text-muted small mb-2" id="{{ $verificationProviderMetaId }}">&mdash;</div>

                <div class="row g-2 align-items-end" id="{{ $verificationCodeRowId }}" style="display:none;">
                    <div class="col-sm-8">
                        <label class="form-label mb-1">{{ __('visits.ccc_code_label') }}</label>
                        <input type="text" id="{{ $verificationReferenceInputId }}" name="verification_reference_code"
                               class="form-control" placeholder="{{ __('visits.ccc_code_label') }}"
                               autocomplete="off">
                    </div>
                    <div class="col-sm-4 d-grid">
                        <button type="button" class="btn btn-primary" id="{{ $runVerificationButtonId }}">
                            <i class="ti ti-shield-check me-1"></i>{{ __('common.confirm') }}
                        </button>
                    </div>
                </div>

                <div class="row g-2 align-items-end mt-1" id="{{ $verificationManualRowId }}" style="display:none;">
                    <div class="col-12 d-grid">
                        <button type="button" class="btn btn-outline-primary btn-sm" id="{{ $runManualVerificationButtonId }}">
                            <i class="ti ti-shield-check me-1"></i>{{ __('common.confirm') }}
                        </button>
                    </div>
                </div>

                <div id="{{ $verificationFeedbackId }}" class="small mt-2"></div>
            </div>
        @endif
    </div>
</div>
