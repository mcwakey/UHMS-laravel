<div class="row g-2 mb-2 align-items-end">
    <div class="col-md-5">
        <label class="form-label small mb-1">{{ $c->name }}@if($c->is_required) <span class="text-danger">*</span>@endif</label>
        @switch($c->input_type)
            @case('number')
                <input type="number" step="any" name="values[{{ $c->id }}][value]" class="form-control form-control-sm" value="{{ $c->default_value }}" @if($c->is_required) required @endif>
                @break
            @case('select')
                <select name="values[{{ $c->id }}][value]" class="form-select form-select-sm" @if($c->is_required) required @endif>
                    <option value="">— {{ __('lab.select_category_opt') }} —</option>
                    @foreach((array) ($c->options ?? []) as $opt)
                        <option value="{{ $opt }}" @selected($c->default_value === $opt)>{{ $opt }}</option>
                    @endforeach
                </select>
                @break
            @case('textarea')
                <textarea name="values[{{ $c->id }}][value]" class="form-control form-control-sm" rows="2" @if($c->is_required) required @endif>{{ $c->default_value }}</textarea>
                @break
            @case('boolean')
                <select name="values[{{ $c->id }}][value]" class="form-select form-select-sm">
                    <option value="">— —</option>
                    <option value="yes" @selected($c->default_value === 'yes')>{{ __('lab.yes_label') }}</option>
                    <option value="no"  @selected($c->default_value === 'no')>{{ __('lab.no_label') }}</option>
                </select>
                @break
            @default
                <input type="text" name="values[{{ $c->id }}][value]" class="form-control form-control-sm" value="{{ $c->default_value }}" @if($c->is_required) required @endif>
        @endswitch
        <input type="hidden" name="values[{{ $c->id }}][name]" value="{{ $c->name }}">
        <input type="hidden" name="values[{{ $c->id }}][unit]" value="{{ $c->unit }}">
        <input type="hidden" name="values[{{ $c->id }}][reference_range]" value="{{ $c->reference_range }}">
    </div>
    <div class="col-md-3">
        <label class="form-label small mb-1">{{ __('lab.unit_col') }}</label>
        <input type="text" class="form-control form-control-sm" value="{{ $c->unit }}" disabled>
    </div>
    <div class="col-md-2">
        <label class="form-label small mb-1">{{ __('lab.range_label') }}</label>
        <input type="text" class="form-control form-control-sm" value="{{ $c->reference_range }}" disabled>
    </div>
    <div class="col-md-2">
        <label class="form-label small mb-1">{{ __('lab.flag_col') }}</label>
        <select name="values[{{ $c->id }}][flag]" class="form-select form-select-sm">
            <option value="">—</option>
            <option value="normal">{{ __('investigations.normal') }}</option>
            <option value="high">{{ __('lab.high_label') }}</option>
            <option value="low">{{ __('lab.low_label') }}</option>
            <option value="abnormal">{{ __('investigations.abnormal') }}</option>
        </select>
    </div>
</div>
