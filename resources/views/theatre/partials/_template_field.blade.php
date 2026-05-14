@php
    $val = $field->_value ?? $field->default_value;
    $name = 'template_values['.$field->id.']';
    $req  = $field->is_required ? 'required' : '';
    $type = $field->input_type;
    $col  = in_array($type, ['textarea', 'file']) ? 'col-12' : 'col-md-6';
@endphp
<div class="{{ $col }}">
    <label class="form-label small">
        {{ $field->label }} @if($field->is_required)<span class="text-danger">*</span>@endif
    </label>
    @switch($type)
        @case('textarea')
            <textarea name="{{ $name }}" rows="2" class="form-control form-control-sm" {{ $req }}>{{ $val }}</textarea>
            @break
        @case('number')
            <input type="number" step="any" name="{{ $name }}" value="{{ $val }}" class="form-control form-control-sm" {{ $req }}>
            @break
        @case('date')
            <input type="date" name="{{ $name }}" value="{{ $val }}" class="form-control form-control-sm" {{ $req }}>
            @break
        @case('time')
            <input type="time" name="{{ $name }}" value="{{ $val }}" class="form-control form-control-sm" {{ $req }}>
            @break
        @case('datetime')
            <input type="datetime-local" name="{{ $name }}" value="{{ $val }}" class="form-control form-control-sm" {{ $req }}>
            @break
        @case('checkbox')
            <div class="form-check">
                <input type="hidden" name="{{ $name }}" value="0">
                <input type="checkbox" class="form-check-input" name="{{ $name }}" value="1" {{ ((string)$val === '1' || $val === true) ? 'checked' : '' }}>
            </div>
            @break
        @case('select')
            <select name="{{ $name }}" class="form-select form-select-sm" {{ $req }}>
                <option value="">— Select —</option>
                @foreach((array) ($field->options ?? []) as $opt)
                    @php $optVal = is_array($opt) ? ($opt['value'] ?? '') : $opt; $optLabel = is_array($opt) ? ($opt['label'] ?? $optVal) : $opt; @endphp
                    <option value="{{ $optVal }}" @selected((string)$val === (string)$optVal)>{{ $optLabel }}</option>
                @endforeach
            </select>
            @break
        @case('file')
            <input type="text" name="{{ $name }}" value="{{ $val }}" class="form-control form-control-sm" placeholder="File reference / URL" {{ $req }}>
            @break
        @default
            <input type="text" name="{{ $name }}" value="{{ $val }}" class="form-control form-control-sm" {{ $req }}>
    @endswitch
    @if($field->field_key)<small class="text-muted">{{ $field->field_key }}</small>@endif
</div>
