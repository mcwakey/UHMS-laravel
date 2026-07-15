@php
    use App\Enums\PatientFinancialRiskLevel;
    use App\Enums\PatientFinancialRiskReason;
    $editing = (bool) ($financialRisk && $financialRisk->status->occupiesActiveSlot());
    $action = $editing
        ? $workspaceRoutes->route('admin.patients.financial-risk.update', [$patient, $financialRisk])
        : $workspaceRoutes->route('admin.patients.financial-risk.store', $patient);
@endphp

<div class="modal fade" id="frClassifyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ $action }}">
            @csrf
            @if($editing)@method('PUT')@endif
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $editing ? __('patient_financial_risk.form.edit_heading') : __('patient_financial_risk.form.classify_heading') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="fr_risk_level">{{ __('patient_financial_risk.fields.risk_level') }}</label>
                            <select name="risk_level" id="fr_risk_level" class="form-select" required>
                                @foreach(PatientFinancialRiskLevel::selectable() as $level)
                                    <option value="{{ $level->value }}" @selected(old('risk_level', $financialRisk?->risk_level?->value) === $level->value)>{{ $level->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="fr_primary_reason">{{ __('patient_financial_risk.fields.primary_reason') }}</label>
                            <select name="primary_reason" id="fr_primary_reason" class="form-select" required>
                                @foreach(PatientFinancialRiskReason::selectable() as $reason)
                                    <option value="{{ $reason->value }}" @selected(old('primary_reason', $financialRisk?->primary_reason?->value) === $reason->value)>{{ $reason->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="fr_reason_details">{{ __('patient_financial_risk.fields.reason_details') }}</label>
                            <textarea name="reason_details" id="fr_reason_details" rows="2" class="form-control" maxlength="1000" placeholder="{{ __('patient_financial_risk.form.reason_placeholder') }}">{{ old('reason_details', $financialRisk?->reason_details) }}</textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="fr_credit_limit">{{ __('patient_financial_risk.fields.credit_limit') }}</label>
                            <input type="number" step="0.01" min="0" name="credit_limit" id="fr_credit_limit" class="form-control" value="{{ old('credit_limit', $financialRisk?->credit_limit) }}">
                            <div class="form-text">{{ __('patient_financial_risk.form.credit_limit_help') }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="fr_effective_from">{{ __('patient_financial_risk.fields.effective_from') }}</label>
                            <input type="date" name="effective_from" id="fr_effective_from" class="form-control" required value="{{ old('effective_from', optional($financialRisk?->effective_from)->toDateString() ?? now()->toDateString()) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="fr_reference">{{ __('patient_financial_risk.fields.reference') }}</label>
                            <input type="text" name="reference" id="fr_reference" class="form-control" maxlength="191" value="{{ old('reference', $financialRisk?->reference) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="fr_review_due_at">{{ __('patient_financial_risk.fields.review_due_at') }}</label>
                            <input type="date" name="review_due_at" id="fr_review_due_at" class="form-control" value="{{ old('review_due_at', optional($financialRisk?->review_due_at)->toDateString()) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="fr_expires_at">{{ __('patient_financial_risk.fields.expires_at') }}</label>
                            <input type="date" name="expires_at" id="fr_expires_at" class="form-control" value="{{ old('expires_at', optional($financialRisk?->expires_at)->toDateString()) }}">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('patient_financial_risk.actions.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('patient_financial_risk.actions.save') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
