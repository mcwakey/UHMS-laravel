@php $a = $bankAccount ?? null; @endphp
@if($errors->any())
<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">{{ __('accounting.bank_account') }} <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $a?->name) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('accounting.bank_name') }} <span class="text-danger">*</span></label>
        <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name', $a?->bank_name) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('accounting.branch_name') }}</label>
        <input type="text" name="branch_name" class="form-control" value="{{ old('branch_name', $a?->branch_name) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('accounting.account_name') }}</label>
        <input type="text" name="account_name" class="form-control" value="{{ old('account_name', $a?->account_name) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('accounting.account_number') }} @unless($a)<span class="text-danger">*</span>@endunless</label>
        <input type="text" name="account_number" class="form-control" placeholder="{{ $a?->account_number_masked }}" @unless($a) required @endunless>
        <div class="form-text">{{ __('accounting.masked_account_number') }}</div>
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('common.currency') ?? 'Currency' }} <span class="text-danger">*</span></label>
        <input type="text" name="currency" class="form-control text-uppercase" maxlength="3" value="{{ old('currency', $a?->currency ?? 'GHS') }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('accounting.opening_date') }}</label>
        <input type="date" name="opening_date" class="form-control" value="{{ old('opening_date', optional($a?->opening_date)->format('Y-m-d')) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('accounting.gl_account') }} <span class="text-danger">*</span></label>
        <select name="gl_account_id" class="form-select" required>
            <option value="">{{ __('accounting.select') }}</option>
            @foreach($glAccounts as $gl)
                <option value="{{ $gl->id }}" @selected(old('gl_account_id', $a?->gl_account_id) == $gl->id)>{{ $gl->code }} — {{ $gl->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('accounting.opening_balance') }}</label>
        <input type="number" step="0.01" name="opening_balance" class="form-control" value="{{ old('opening_balance', $a?->opening_balance ?? 0) }}">
    </div>
    <div class="col-12">
        <label class="form-label">{{ __('common.notes') ?? 'Notes' }}</label>
        <textarea name="notes" class="form-control" rows="2">{{ old('notes', $a?->notes) }}</textarea>
    </div>
    <div class="col-12 form-check ms-2">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" class="form-check-input" name="is_active" value="1" id="is_active" @checked(old('is_active', $a?->is_active ?? true))>
        <label class="form-check-label" for="is_active">{{ __('common.active') }}</label>
    </div>
</div>
