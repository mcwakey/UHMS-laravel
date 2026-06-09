@php($editing = isset($account))
<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label">Code <span class="text-danger">*</span></label>
        <input type="text" name="code" class="form-control" value="{{ old('code', $account->code ?? '') }}" required>
    </div>
    <div class="col-md-5">
        <label class="form-label">Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $account->name ?? '') }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Type <span class="text-danger">*</span></label>
        <select name="type" class="form-select" required>
            <option value="">Select type</option>
            @foreach($types as $type)
                <option value="{{ $type->value }}" @selected(old('type', isset($account) ? $account->type->value : '') === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Subtype</label>
        <input type="text" name="subtype" class="form-control" value="{{ old('subtype', $account->subtype ?? '') }}" placeholder="CURRENT_ASSET">
    </div>
    <div class="col-md-4">
        <label class="form-label">Parent Account</label>
        <select name="parent_id" class="form-select select2">
            <option value="">No parent</option>
            @foreach($parentAccounts as $parent)
                <option value="{{ $parent->id }}" @selected((string) old('parent_id', $account->parent_id ?? '') === (string) $parent->id)>{{ $parent->display_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Normal Balance</label>
        <select name="normal_balance" class="form-select">
            <option value="">Use type default</option>
            @foreach($normalBalances as $balance)
                <option value="{{ $balance->value }}" @selected(old('normal_balance', isset($account) ? $account->normal_balance->value : '') === $balance->value)>{{ $balance->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Opening Balance</label>
        <input type="number" step="0.01" name="opening_balance" class="form-control" value="{{ old('opening_balance', $account->opening_balance ?? '0.00') }}">
    </div>
    <div class="col-md-8">
        <label class="form-label">Description</label>
        <input type="text" name="description" class="form-control" value="{{ old('description', $account->description ?? '') }}">
    </div>
    <div class="col-12">
        <div class="d-flex flex-wrap gap-3">
            <label class="form-check">
                <input class="form-check-input" type="checkbox" name="is_cash_account" value="1" @checked(old('is_cash_account', $account->is_cash_account ?? false))>
                <span class="form-check-label">Cash account</span>
            </label>
            <label class="form-check">
                <input class="form-check-input" type="checkbox" name="is_bank_account" value="1" @checked(old('is_bank_account', $account->is_bank_account ?? false))>
                <span class="form-check-label">Bank account</span>
            </label>
            <label class="form-check">
                <input class="form-check-input" type="checkbox" name="is_control_account" value="1" @checked(old('is_control_account', $account->is_control_account ?? false))>
                <span class="form-check-label">Control account</span>
            </label>
            <label class="form-check">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $account->is_active ?? true))>
                <span class="form-check-label">Active</span>
            </label>
        </div>
    </div>
</div>
