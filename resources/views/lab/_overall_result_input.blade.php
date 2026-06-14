@php
    /** @var \App\Models\LabRequestItem $item */
    $service = $item->service ?? null;
    $ort = $service ? $service->overallResultType() : \App\Models\ServiceCatalog::OVERALL_RESULT_FREE_TEXT;
@endphp
{{-- The configured overall result type drives which input is shown. Stored values
     are canonical (numeric / true|false / positive|negative); labels are translated. --}}
<input type="hidden" name="overall_result_type" value="{{ $ort }}">

@if($ort === \App\Models\ServiceCatalog::OVERALL_RESULT_NUMERIC)
    <div class="mb-3">
        <label class="form-label">{{ __('lab.overall_result_label') }} <span class="text-danger">*</span></label>
        <div class="input-group">
            <input type="number" step="any" name="overall_result_value" class="form-control" required
                   value="{{ old('overall_result_value') }}" placeholder="{{ __('investigations.numeric_value') }}">
            @if($service?->overall_result_unit)
                <span class="input-group-text">{{ $service->overall_result_unit }}</span>
            @endif
        </div>
        @if($service && ($service->overall_result_min_value !== null || $service->overall_result_max_value !== null))
            <div class="form-text">
                {{ __('investigations.normal_range') }}:
                {{ $service->overall_result_min_value !== null ? rtrim(rtrim(number_format((float) $service->overall_result_min_value, 4, '.', ''), '0'), '.') : '—' }}
                –
                {{ $service->overall_result_max_value !== null ? rtrim(rtrim(number_format((float) $service->overall_result_max_value, 4, '.', ''), '0'), '.') : '—' }}
                {{ $service->overall_result_unit }}
            </div>
        @endif
    </div>

@elseif($ort === \App\Models\ServiceCatalog::OVERALL_RESULT_BOOLEAN)
    <div class="mb-3">
        <label class="form-label">{{ __('lab.overall_result_label') }} <span class="text-danger">*</span></label>
        <select name="overall_result_value" class="form-select" required>
            <option value="">{{ __('investigations.select_overall_result') }}</option>
            <option value="true">{{ $service->trueLabel() }}</option>
            <option value="false">{{ $service->falseLabel() }}</option>
        </select>
    </div>

@elseif($ort === \App\Models\ServiceCatalog::OVERALL_RESULT_POSITIVE_NEGATIVE)
    <div class="mb-3">
        <label class="form-label">{{ __('lab.overall_result_label') }} <span class="text-danger">*</span></label>
        <select name="overall_result_value" class="form-select" required>
            <option value="">{{ __('investigations.select_overall_result') }}</option>
            <option value="positive">{{ $service->positiveLabel() }}</option>
            <option value="negative">{{ $service->negativeLabel() }}</option>
        </select>
    </div>

@else
    {{-- free_text (default + backward compatible) --}}
    <div class="mb-3">
        <label class="form-label">{{ __('lab.result_value_label') }} <span class="text-danger">*</span></label>
        <textarea name="result_value" class="form-control" rows="2" required placeholder="{{ __('lab.enter_result_placeholder') }}"></textarea>
    </div>
@endif
