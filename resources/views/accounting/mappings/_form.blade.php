@csrf
@if(isset($mapping)) @method('PUT') @endif
<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">{{ __('accounting.mapping_scope') }}</label>
        <input class="form-control" name="mapping_scope" list="accounting-mapping-scopes" required maxlength="50" value="{{ old('mapping_scope', $mapping->mapping_scope ?? '') }}">
        <datalist id="accounting-mapping-scopes">
            @foreach(['basic_income_category', 'basic_expense_category', 'basic_payment_method', 'basic_cash_account', 'basic_bank_account'] as $scope)<option value="{{ $scope }}">@endforeach
        </datalist>
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('accounting.mapping_key') }}</label>
        <input class="form-control" name="mapping_key" required maxlength="60" value="{{ old('mapping_key', $mapping->mapping_key ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('accounting.mapping_value') }}</label>
        <input class="form-control" name="mapping_value" required maxlength="80" value="{{ old('mapping_value', $mapping->mapping_value ?? '') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('accounting.account') }}</label>
        <select class="form-select" name="account_id" required>
            <option value="">{{ __('accounting.select_account') }}</option>
            @foreach($accounts as $account)
                <option value="{{ $account->id }}" @selected((string) old('account_id', $mapping->account_id ?? '') === (string) $account->id)>{{ $account->display_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('accounting.department') }}</label>
        <select class="form-select" name="department_id">
            <option value="">{{ __('accounting.any_department') }}</option>
            @foreach($departments as $department)
                <option value="{{ $department->id }}" @selected((string) old('department_id', $mapping->department_id ?? '') === (string) $department->id)>{{ $department->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('accounting.currency') }}</label>
        <input class="form-control text-uppercase" name="currency" maxlength="3" value="{{ old('currency', $mapping->currency ?? '') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('accounting.facility_id') }}</label>
        <input class="form-control" type="number" min="1" name="facility_id" value="{{ old('facility_id', $mapping->facility_id ?? '') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('accounting.branch_id') }}</label>
        <input class="form-control" type="number" min="1" name="branch_id" value="{{ old('branch_id', $mapping->branch_id ?? '') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('accounting.effective_from') }}</label>
        <input class="form-control" type="date" name="effective_from" required value="{{ old('effective_from', isset($mapping) ? $mapping->effective_from?->format('Y-m-d') : now()->toDateString()) }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('accounting.effective_to') }}</label>
        <input class="form-control" type="date" name="effective_to" value="{{ old('effective_to', isset($mapping) ? $mapping->effective_to?->format('Y-m-d') : '') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('accounting.priority') }}</label>
        <input class="form-control" type="number" name="priority" required value="{{ old('priority', $mapping->priority ?? 0) }}">
    </div>
    <div class="col-md-9">
        <label class="form-label">{{ __('accounting.notes') }}</label>
        <textarea class="form-control" name="notes" rows="2">{{ old('notes', $mapping->notes ?? '') }}</textarea>
    </div>
    <div class="col-md-3 d-flex align-items-end">
        <div class="form-check mb-2">
            <input type="hidden" name="is_active" value="0">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $mapping->is_active ?? true))>
            <label class="form-check-label" for="is_active">{{ __('common.active') }}</label>
        </div>
    </div>
</div>
<div class="mt-4 d-flex gap-2">
    <button class="btn btn-primary" type="submit">{{ __('common.save') }}</button>
    <a class="btn btn-outline-secondary" href="{{ route('admin.accounting.mappings.index') }}">{{ __('common.cancel') }}</a>
</div>
