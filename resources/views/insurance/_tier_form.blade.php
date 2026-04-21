<div class="modal-header">
    <h5 class="modal-title">{{ isset($tier) && $tier ? 'Edit Tier: ' . $tier->name : 'Add Tier' }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">

    {{-- Basic Info --}}
    <div class="row mb-3">
        <div class="col-md-5">
            <label class="form-label">Tier Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" value="{{ $tier?->name }}" required placeholder="e.g. Gold, Silver, Standard">
        </div>
        <div class="col-md-3">
            <label class="form-label">Code</label>
            <input type="text" name="code" class="form-control" value="{{ $tier?->code }}" maxlength="30" placeholder="e.g. GLD">
        </div>
        <div class="col-md-2">
            <label class="form-label">Sort Order</label>
            <input type="number" name="sort_order" class="form-control" value="{{ $tier?->sort_order ?? 0 }}" min="0">
        </div>
        <div class="col-md-2 d-flex flex-column justify-content-end">
            <div class="form-check mb-1">
                <input type="checkbox" name="is_default" class="form-check-input" value="1" id="isDefault{{ $tier?->id }}" {{ $tier?->is_default ? 'checked' : '' }}>
                <label class="form-check-label" for="isDefault{{ $tier?->id }}">Default</label>
            </div>
            <div class="form-check">
                <input type="checkbox" name="is_active" class="form-check-input" value="1" id="isActive{{ $tier?->id }}" {{ ($tier === null || $tier->is_active) ? 'checked' : '' }}>
                <label class="form-check-label" for="isActive{{ $tier?->id }}">Active</label>
            </div>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="2" placeholder="Optional notes about this tier">{{ $tier?->description }}</textarea>
    </div>

    {{-- Base Constraints --}}
    <h6 class="fw-semibold mt-3 mb-2 text-secondary">Base Constraints <small class="text-muted fw-normal">(apply to all members unless overridden)</small></h6>
    <div class="row g-2 mb-2">
        <div class="col-md-2">
            <label class="form-label small">Coverage %</label>
            <input type="number" name="coverage_percentage" class="form-control form-control-sm" value="{{ $tier?->coverage_percentage ?? 100 }}" step="0.01" min="0" max="100">
        </div>
        <div class="col-md-2">
            <label class="form-label small">Per Visit (&#8373;)</label>
            <input type="number" name="per_visit_limit" class="form-control form-control-sm" value="{{ $tier?->per_visit_limit }}" step="0.01" min="0" placeholder="No limit">
        </div>
        <div class="col-md-2">
            <label class="form-label small">Monthly (&#8373;)</label>
            <input type="number" name="max_per_month" class="form-control form-control-sm" value="{{ $tier?->max_per_month }}" step="0.01" min="0" placeholder="No limit">
        </div>
        <div class="col-md-2">
            <label class="form-label small">Annual (&#8373;)</label>
            <input type="number" name="annual_limit" class="form-control form-control-sm" value="{{ $tier?->annual_limit }}" step="0.01" min="0" placeholder="No limit">
        </div>
        <div class="col-md-2">
            <label class="form-label small">Max Visits/Month</label>
            <input type="number" name="max_visits_per_month" class="form-control form-control-sm" value="{{ $tier?->max_visits_per_month }}" min="1" step="1" placeholder="No limit">
        </div>
    </div>
    <div class="row g-2 mb-3">
        <div class="col-md-3">
            <label class="form-label small">Min Days Between Visits</label>
            <input type="number" name="min_visit_interval_days" class="form-control form-control-sm" value="{{ $tier?->min_visit_interval_days }}" min="1" step="1" placeholder="No restriction">
        </div>
        <div class="col-md-3">
            <label class="form-label small">Max Beneficiaries / Holder</label>
            <input type="number" name="max_beneficiaries" class="form-control form-control-sm" value="{{ $tier?->max_beneficiaries }}" min="1" step="1" placeholder="Unlimited">
        </div>
    </div>

    {{-- Holder Overrides --}}
    <details class="mb-3">
        <summary class="fw-semibold text-primary small" style="cursor:pointer">
            <i class="ti ti-user-check me-1"></i>Card Holder Overrides
            <span class="text-muted fw-normal">(leave blank to use base)</span>
        </summary>
        <div class="row g-2 mt-1">
            <div class="col-md-3">
                <label class="form-label small">Per Visit (&#8373;)</label>
                <input type="number" name="holder_per_visit_limit" class="form-control form-control-sm" value="{{ $tier?->holder_per_visit_limit }}" step="0.01" min="0" placeholder="Use base">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Monthly (&#8373;)</label>
                <input type="number" name="holder_max_per_month" class="form-control form-control-sm" value="{{ $tier?->holder_max_per_month }}" step="0.01" min="0" placeholder="Use base">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Annual (&#8373;)</label>
                <input type="number" name="holder_annual_limit" class="form-control form-control-sm" value="{{ $tier?->holder_annual_limit }}" step="0.01" min="0" placeholder="Use base">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Max Visits/Month</label>
                <input type="number" name="holder_max_visits_per_month" class="form-control form-control-sm" value="{{ $tier?->holder_max_visits_per_month }}" min="1" step="1" placeholder="Use base">
            </div>
        </div>
    </details>

    {{-- Beneficiary Overrides --}}
    <details class="mb-2">
        <summary class="fw-semibold text-info small" style="cursor:pointer">
            <i class="ti ti-users me-1"></i>Beneficiary Overrides
            <span class="text-muted fw-normal">(leave blank to use base)</span>
        </summary>
        <div class="row g-2 mt-1">
            <div class="col-md-3">
                <label class="form-label small">Per Visit (&#8373;)</label>
                <input type="number" name="beneficiary_per_visit_limit" class="form-control form-control-sm" value="{{ $tier?->beneficiary_per_visit_limit }}" step="0.01" min="0" placeholder="Use base">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Monthly (&#8373;)</label>
                <input type="number" name="beneficiary_max_per_month" class="form-control form-control-sm" value="{{ $tier?->beneficiary_max_per_month }}" step="0.01" min="0" placeholder="Use base">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Annual (&#8373;)</label>
                <input type="number" name="beneficiary_annual_limit" class="form-control form-control-sm" value="{{ $tier?->beneficiary_annual_limit }}" step="0.01" min="0" placeholder="Use base">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Max Visits/Month</label>
                <input type="number" name="beneficiary_max_visits_per_month" class="form-control form-control-sm" value="{{ $tier?->beneficiary_max_visits_per_month }}" min="1" step="1" placeholder="Use base">
            </div>
        </div>
    </details>

</div>
<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
    <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
</div>
