@props([
    'patient' => null,
    'formAction' => null,
    'actionTemplate' => null,
    'formId' => 'editInsuranceForm',
    'modalId' => 'editInsuranceModal',
    'title' => 'Edit Insurance Plan',
    'submitLabel' => 'Update Insurance',
])

@php
    $actionTemplate = $actionTemplate
        ?: ($patient ? route('admin.patients.insurances.update', [$patient, '__INSURANCE__']) : null);
    $formAction = $formAction ?: '#';
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ $formAction }}" id="{{ $formId }}" data-action-template="{{ $actionTemplate }}">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">{{ $title }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="editInsuranceFormFeedback" class="alert d-none" role="alert"></div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <input type="hidden" name="insurance_provider_id" id="editInsProviderId">
                            <label class="form-label">Insurance</label>
                            <div id="editInsProviderName" class="form-control-plaintext fw-medium text-muted">-</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tier</label>
                            <div id="editInsTierDisplay" class="form-control-plaintext fw-medium text-muted">-</div>
                            <input type="hidden" name="insurance_tier_id" id="editInsTierId">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Member Type</label>
                            <div class="d-flex gap-3 mt-1">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="member_type" id="editMemberHolder" value="holder">
                                    <label class="form-check-label" for="editMemberHolder">
                                        <span class="badge bg-info">Card Holder</span>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="member_type" id="editMemberBeneficiary" value="beneficiary">
                                    <label class="form-check-label" for="editMemberBeneficiary">
                                        <span class="badge bg-warning text-dark">Beneficiary</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Membership Number</label>
                            <input type="text" name="membership_number" class="form-control" id="editInsMembership">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Policy Number</label>
                            <input type="text" name="policy_number" class="form-control" id="editInsPolicy">
                        </div>
                        {{-- <div class="col-md-6 mb-3">
                            <label class="form-label">CCC Code <small class="text-muted">(optional)</small></label>
                            <input type="text" name="ccc_code" class="form-control" id="editInsCccCode" maxlength="64">
                        </div> --}}
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" name="expiry_date" class="form-control" id="editInsExpiry">
                        </div>
                        <div class="col-md-4 mb-3 d-flex align-items-end">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" value="1" class="form-check-input" id="editInsActive">
                                <label class="form-check-label" for="editInsActive">Active</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="editInsuranceSubmitBtn">{{ $submitLabel }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
