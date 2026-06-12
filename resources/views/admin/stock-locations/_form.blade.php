@php($loc = $loc ?? null)
<div class="mb-3">
    <label class="form-label">{{ __('stock.name') }}</label>
    <input type="text" name="name" class="form-control" required maxlength="191"
           value="{{ old('name', $loc?->name) }}">
</div>
<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">{{ __('stock.type') }}</label>
        <select name="type" class="form-select" required>
            @foreach($types as $t)
                <option value="{{ $t }}" @selected(old('type', $loc?->type) === $t)>{{ ucfirst($t) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">{{ __('stock.department') }}</label>
        <select name="department_id" class="form-select">
            <option value="">{{ __('stock.none_department') }}</option>
            @foreach($departments as $d)
                <option value="{{ $d->id }}" @selected(old('department_id', $loc?->department_id) == $d->id)>{{ $d->name }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-check">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" class="form-check-input" name="is_active" value="1"
                   @checked(old('is_active', $loc?->is_active ?? 1))>
            <span class="form-check-label">{{ __('stock.active') }}</span>
        </label>
    </div>
    <input type="hidden" name="is_main" value="0">
    <div class="col-md-6 mb-3">
        <div class="small text-muted">{{ __('stock.main_store_info') }}</div>
    </div>
</div>
<div class="mb-3">
    <label class="form-label">{{ __('stock.notes') }}</label>
    <textarea name="notes" class="form-control" rows="2" maxlength="500">{{ old('notes', $loc?->notes) }}</textarea>
</div>
