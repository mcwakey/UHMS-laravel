<div class="row g-2">
    <div class="col-md-6">
        <label class="form-label small">{{ __('common.name') }} *</label>
        <input type="text" name="name" class="form-control form-control-sm" required value="{{ old('name', $product->name ?? '') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label small">{{ __('common.code') }}</label>
        <input type="text" name="code" class="form-control form-control-sm" value="{{ old('code', $product->code ?? '') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label small">{{ __('products.unit') }}</label>
        <input type="text" name="unit" class="form-control form-control-sm" placeholder="{{ __('products.example_unit_placeholder') }}" value="{{ old('unit', $product->unit ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label small">{{ __('common.type') }} *</label>
        <select name="product_type" class="form-select form-select-sm" required>
            @foreach($types as $t)
                <option value="{{ $t->value }}" @selected(old('product_type', $product->product_type?->value ?? '') === $t->value)>{{ $t->translatedLabel() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label small">{{ __('products.reorder_level') }}</label>
        <input type="number" step="0.0001" min="0" name="reorder_level" class="form-control form-control-sm" value="{{ old('reorder_level', $product->reorder_level ?? 0) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label small">{{ __('products.default_unit_cost') }}</label>
        <input type="number" step="0.01" min="0" name="default_cost" class="form-control form-control-sm" value="{{ old('default_cost', $product->default_cost ?? 0) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label small">{{ __('products.base_price_selling') }}</label>
        <input type="number" step="0.01" min="0" name="base_price" class="form-control form-control-sm"
               placeholder="{{ __('products.cash_carry_price') }}"
               value="{{ old('base_price', $product->base_price ?? '') }}">
    </div>
    <div class="col-12">
        <div class="form-check">
            <input type="checkbox" class="form-check-input" name="is_billable" value="1"
                   id="is_billable_{{ $product->id ?? 'new' }}"
                   @checked(old('is_billable', $product->is_billable ?? false))>
            <label class="form-check-label small" for="is_billable_{{ $product->id ?? 'new' }}">
                {{ __('products.is_billable') }} <span class="text-muted">({{ __('products.can_appear_invoices') }})</span>
            </label>
        </div>
    </div>
    <div class="col-12">
        <label class="form-label small">{{ __('common.description') }}</label>
        <textarea name="description" rows="2" class="form-control form-control-sm">{{ old('description', $product->description ?? '') }}</textarea>
    </div>
    <div class="col-12">
        <label class="form-label small">{{ __('products.departments_served') }}</label>
        <div class="row g-1">
            @php $selected = isset($product) ? $product->departments->pluck('id')->all() : (array) old('department_ids', []); @endphp
            @foreach($departments as $d)
                <div class="col-md-4">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="department_ids[]" value="{{ $d->id }}" id="dept-{{ $product->id ?? 'new' }}-{{ $d->id }}"
                               @checked(in_array($d->id, $selected))>
                        <label class="form-check-label small" for="dept-{{ $product->id ?? 'new' }}-{{ $d->id }}">{{ $d->name }}</label>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @isset($product)
    <div class="col-12">
        <div class="form-check">
            <input type="checkbox" class="form-check-input" name="is_active" value="1" id="active-{{ $product->id }}" @checked($product->is_active)>
            <label class="form-check-label small" for="active-{{ $product->id }}">{{ __('common.active') }}</label>
        </div>
    </div>
    @endisset
</div>
