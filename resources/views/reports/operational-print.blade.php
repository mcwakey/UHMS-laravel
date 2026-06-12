<x-print-layout :title="$printMeta['title']" :subtitle="$printMeta['subtitle']" :generated-at="$printMeta['generatedAt']">
    <div class="mb-3 small">
        <strong>{{ __('reports.generated_by') }}:</strong> {{ $printMeta['generatedBy'] }}
    </div>

    <div class="mb-3 small">
        <strong>{{ __('reports.print.filter_summary') }}:</strong>
        @forelse($printMeta['filters'] as $key => $value)
            @if($value !== null && $value !== '')
                <span class="me-2">{{ __('reports.filters.' . $key) }}: {{ $value }}</span>
            @endif
        @empty
            <span>{{ __('reports.all_statuses') }}</span>
        @endforelse
    </div>

    <table class="table table-sm table-bordered align-middle">
        <thead>
            <tr>
                @foreach($columns as $column)
                    <th>{{ $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($row as $cell)
                        <td>{{ $cell ?: '-' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}" class="text-center">{{ __('reports.no_data') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</x-print-layout>
