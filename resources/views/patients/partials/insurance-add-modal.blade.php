@props([
    'patient' => null,
    'insuranceProviders',
    'formAction' => null,
    'formId' => 'addInsuranceForm',
    'modalId' => 'addInsuranceModal',
    'title' => 'Add Insurance Plan',
    'submitLabel' => 'Add Insurance',
])

@php
    $formAction = $formAction ?: ($patient ? route('admin.patients.insurances.store', $patient) : '#');
    $addableInsuranceTypes = $insuranceProviders
        ->where('is_default', false)
        ->pluck('type')
        ->filter()
        ->unique(fn ($type) => $type instanceof \BackedEnum ? $type->value : (string) $type)
        ->sortBy(fn ($type) => $type instanceof \BackedEnum && method_exists($type, 'label') ? $type->label() : ucfirst((string) ($type instanceof \BackedEnum ? $type->value : $type)));
@endphp

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
                            <label class="form-label">Insurance Type <span class="text-danger">*</span></label>
                            <select id="addInsType" class="form-select" required>
                                <option value="">Select Type</option>
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
                            <label class="form-label">Insurance Provider <span class="text-danger">*</span></label>
                            <select name="insurance_provider_id" id="addInsProvider" class="form-select" required disabled>
                                <option value="">Select type first</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Insurance Tier <span class="text-danger">*</span></label>
                            <select name="insurance_tier_id" id="addInsTier" class="form-select" disabled>
                                <option value="">Select provider first</option>
                            </select>
                            <div id="addInsTierInfo" class="small text-muted mt-1">If no tier is chosen, the provider's default tier will be used.</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Member Type <span class="text-danger">*</span></label>
                            <div class="d-flex gap-3 mt-1">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="member_type" id="addMemberHolder" value="holder" checked>
                                    <label class="form-check-label" for="addMemberHolder">
                                        <span class="badge bg-info">Card Holder</span>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="member_type" id="addMemberBeneficiary" value="beneficiary">
                                    <label class="form-check-label" for="addMemberBeneficiary">
                                        <span class="badge bg-warning text-dark">Beneficiary</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Membership Number</label>
                            <input type="text" name="membership_number" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Policy Number</label>
                            <input type="text" name="policy_number" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" name="expiry_date" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3 d-flex align-items-end">
                            <div class="form-check">
                                <input type="checkbox" name="is_primary" value="1" class="form-check-input" id="addInsPrimary">
                                <label class="form-check-label" for="addInsPrimary">Set as primary insurance</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="addInsuranceSubmitBtn">{{ $submitLabel }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
