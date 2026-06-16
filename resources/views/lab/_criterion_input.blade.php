@php
    $savedCriterionValue = $item->result?->values?->firstWhere('criteria_id', $c->id);
    $criterionValue = old("values.{$c->id}.value", $savedCriterionValue?->value ?? $c->default_value);
    $criterionFlag = old("values.{$c->id}.flag", $savedCriterionValue?->flag);
@endphp
<div class="row g-2 mb-2 align-items-end investigation-criterion-row" data-reference-range="{{ $c->reference_range }}">
    <div class="col-md-5">
        <label class="form-label small mb-1">{{ $c->name }}@if($c->is_required) <span class="text-danger">*</span>@endif</label>
        @switch($c->input_type)
            @case('number')
                <input type="number" step="any" name="values[{{ $c->id }}][value]" class="form-control form-control-sm criterion-value-input" value="{{ $criterionValue }}" @if($c->is_required) required @endif>
                @break
            @case('select')
                <select name="values[{{ $c->id }}][value]" class="form-select form-select-sm criterion-value-input" @if($c->is_required) required @endif>
                    <option value="">— {{ __('lab.select_category_opt') }} —</option>
                    @foreach((array) ($c->options ?? []) as $opt)
                        <option value="{{ $opt }}" @selected($criterionValue === $opt)>{{ $opt }}</option>
                    @endforeach
                </select>
                @break
            @case('textarea')
                <textarea name="values[{{ $c->id }}][value]" class="form-control form-control-sm criterion-value-input" rows="2" @if($c->is_required) required @endif>{{ $criterionValue }}</textarea>
                @break
            @case('boolean')
                <select name="values[{{ $c->id }}][value]" class="form-select form-select-sm criterion-value-input">
                    <option value="">— —</option>
                    <option value="yes" @selected($criterionValue === 'yes')>{{ __('lab.yes_label') }}</option>
                    <option value="no"  @selected($criterionValue === 'no')>{{ __('lab.no_label') }}</option>
                </select>
                @break
            @default
                <input type="text" name="values[{{ $c->id }}][value]" class="form-control form-control-sm criterion-value-input" value="{{ $criterionValue }}" @if($c->is_required) required @endif>
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
        <select name="values[{{ $c->id }}][flag]" class="form-select form-select-sm criterion-flag-select">
            <option value="">—</option>
            <option value="normal" @selected($criterionFlag === 'normal')>{{ __('investigations.normal') }}</option>
            <option value="high" @selected($criterionFlag === 'high')>{{ __('lab.high_label') }}</option>
            <option value="low" @selected($criterionFlag === 'low')>{{ __('lab.low_label') }}</option>
            <option value="abnormal" @selected($criterionFlag === 'abnormal')>{{ __('investigations.abnormal') }}</option>
        </select>
    </div>
</div>
