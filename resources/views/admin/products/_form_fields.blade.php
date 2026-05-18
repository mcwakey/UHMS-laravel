<div class="row g-2">
    <div class="col-md-6">
        <label class="form-label small">Name *</label>
        <input type="text" name="name" class="form-control form-control-sm" required value="{{ old('name', $product->name ?? '') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label small">Code</label>
        <input type="text" name="code" class="form-control form-control-sm" value="{{ old('code', $product->code ?? '') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label small">Unit</label>
        <input type="text" name="unit" class="form-control form-control-sm" placeholder="e.g. pcs, ml, box" value="{{ old('unit', $product->unit ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label small">Type *</label>
        <select name="product_type" class="form-select form-select-sm" required>
            @foreach($types as $t)
                <option value="{{ $t->value }}" @selected(old('product_type', $product->product_type?->value ?? '') === $t->value)>{{ $t->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label small">Reorder level</label>
        <input type="number" step="0.0001" min="0" name="reorder_level" class="form-control form-control-sm" value="{{ old('reorder_level', $product->reorder_level ?? 0) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label small">Default unit cost</label>
        <input type="number" step="0.01" min="0" name="default_cost" class="form-control form-control-sm" value="{{ old('default_cost', $product->default_cost ?? 0) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label small">Base price (selling price)</label>
        <input type="number" step="0.01" min="0" name="base_price" class="form-control form-control-sm"
               placeholder="Cash & carry price"
               value="{{ old('base_price', $product->base_price ?? '') }}">
    </div>
    <div class="col-12">
        <div class="form-check">
            <input type="checkbox" class="form-check-input" name="is_billable" value="1"
                   id="is_billable_{{ $product->id ?? 'new' }}"
                   @checked(old('is_billable', $product->is_billable ?? false))>
            <label class="form-check-label small" for="is_billable_{{ $product->id ?? 'new' }}">
                Is Billable <span class="text-muted">(can appear on patient invoices)</span>
            </label>
        </div>
    </div>
    <div class="col-12">
        <label class="form-label small">Description</label>
        <textarea name="description" rows="2" class="form-control form-control-sm">{{ old('description', $product->description ?? '') }}</textarea>
    </div>
    <div class="col-12">
        <label class="form-label small">Departments (linked stores/departments this product serves)</label>
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
            <label class="form-check-label small" for="active-{{ $product->id }}">Active</label>
        </div>
    </div>
    @endisset
</div>
