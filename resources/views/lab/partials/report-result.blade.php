@php
    $result = $item->result;
    $serviceCriteria = $item->service?->investigationCriteria?->where('is_active', true) ?? collect();
    $serviceHeaders = $item->service?->investigationHeaders?->where('is_active', true) ?? collect();
    $values = $result?->values ?? collect();
    $valuesByCriteria = $values->keyBy('criteria_id');
    $flagClass = fn ($flag) => match ($flag) {
        'normal' => 'result-flag-normal',
        'high', 'low', 'abnormal' => 'result-flag-alert',
        default => '',
    };
@endphp

<section class="result-report-section {{ $result?->is_abnormal ? 'result-report-abnormal' : '' }}">
    <div class="result-report-heading">
        <div>
            <div class="result-report-kicker">{{ $item->service?->code ?? $item->labTest?->code }}</div>
            <h2>{{ $item->display_name }}</h2>
        </div>
        <span class="result-status {{ $result?->is_abnormal ? 'result-status-alert' : 'result-status-normal' }}">
            {{ $result?->is_abnormal ? __('investigations.abnormal') : __('investigations.normal') }}
        </span>
    </div>

    @if($values->isNotEmpty())
        @foreach($serviceHeaders as $header)
            @php $headerCriteria = $serviceCriteria->where('header_id', $header->id); @endphp
            @if($headerCriteria->isNotEmpty())
                <h3 class="result-group-title">{{ $header->name }}</h3>
                <table class="result-table">
                    <thead>
                        <tr>
                            <th>{{ __('lab.parameter_col') }}</th>
                            <th>{{ __('lab.value_col') }}</th>
                            <th>{{ __('lab.unit_col') }}</th>
                            <th>{{ __('lab.reference_col') }}</th>
                            <th>{{ __('lab.flag_col') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($headerCriteria as $criterion)
                            @php $value = $valuesByCriteria->get($criterion->id); @endphp
                            <tr class="{{ $flagClass($value?->flag) }}">
                                <td>{{ $criterion->name }}</td>
                                <td class="result-value">{{ $value?->value ?? '—' }}</td>
                                <td>{{ $value?->unit ?? $criterion->unit }}</td>
                                <td>{{ $value?->reference_range ?? $criterion->reference_range }}</td>
                                <td>{{ $value?->flag ? ucfirst($value->flag) : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @endforeach

        @php
            $ungroupedValues = $values->filter(function ($value) use ($serviceCriteria) {
                $criterion = $serviceCriteria->firstWhere('id', $value->criteria_id);
                return $criterion === null || $criterion->header_id === null;
            });
        @endphp
        @if($ungroupedValues->isNotEmpty())
            <table class="result-table">
                <thead>
                    <tr>
                        <th>{{ __('lab.parameter_col') }}</th>
                        <th>{{ __('lab.value_col') }}</th>
                        <th>{{ __('lab.unit_col') }}</th>
                        <th>{{ __('lab.reference_col') }}</th>
                        <th>{{ __('lab.flag_col') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ungroupedValues as $value)
                        <tr class="{{ $flagClass($value->flag) }}">
                            <td>{{ $value->name }}</td>
                            <td class="result-value">{{ $value->value }}</td>
                            <td>{{ $value->unit }}</td>
                            <td>{{ $value->reference_range }}</td>
                            <td>{{ $value->flag ? ucfirst($value->flag) : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @elseif($result?->result_text)
        <div class="result-narrative">{!! nl2br(e($result->result_text)) !!}</div>
    @elseif($result?->result_value)
        <div class="result-summary-value">{{ $result->overallResultDisplay($item->service) }}</div>
    @endif

    @if($result?->remarks)
        <div class="result-remarks">
            <strong>{{ __('lab.remarks_label') }}:</strong> {{ $result->remarks }}
        </div>
    @endif

    @if($result?->result_file)
        <div class="result-attachment">
            {{ __('lab.attachment_label') }}: {{ $result->result_file_name ?? basename($result->result_file) }}
        </div>
    @endif
</section>
