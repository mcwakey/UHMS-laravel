@props([
    'paginator' => null,
    'striped' => false,
    'hover' => true,
    'alignMiddle' => true,
    'responsive' => true,
    'tableClass' => null,
    'pagination' => true,
    'showSummary' => false,
    'summaryLabel' => null,
    'showPerPage' => false,
    'perPageLabel' => null,
    'perPageOptions' => [10, 25, 50, 100],
    'currentPerPage' => null,
])

@php
    $tClass = 'table'
        .($hover ? ' table-hover' : '')
        .($striped ? ' table-striped' : '')
        .($alignMiddle ? ' align-middle' : '')
        .' mb-0'
        .($tableClass ? ' '.$tableClass : '');

    $summaryLabel ??= 'common.showing_results';
    $perPageLabel ??= __('common.per_page');
    $currentPerPage = $currentPerPage ?? ($paginator && method_exists($paginator, 'perPage') ? $paginator->perPage() : null);
    $hasFooter = $paginator && is_object($paginator)
        && (($showSummary && method_exists($paginator, 'total')) || $showPerPage || ($pagination && method_exists($paginator, 'hasPages') && $paginator->hasPages()));
    $tableId = $attributes->get('id', 'dataTable');
@endphp

{{--
    Standard table wrapper. Provide the header via <x-slot:head> and the body
    rows in the default slot. Optional footer controls render paginator summary,
    per-page selector, and pagination links.
--}}
<div {{ $attributes->merge(['class' => 'card mb-0']) }}>
    <div class="card-body p-0">
        @if($responsive)<div class="table-responsive">@endif
            <table class="{{ $tClass }}">
                @isset($head)
                    <thead class="bg-light">{{ $head }}</thead>
                @endisset
                <tbody>{{ $slot }}</tbody>
            </table>
        @if($responsive)</div>@endif
    </div>

    @if($hasFooter)
        <div class="card-footer d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
            <div class="d-flex flex-wrap align-items-center gap-2 small text-muted">
                @if($showSummary && method_exists($paginator, 'total'))
                    <span>
                        {{ __($summaryLabel, [
                            'from' => $paginator->firstItem() ?? 0,
                            'to' => $paginator->lastItem() ?? 0,
                            'total' => $paginator->total(),
                        ]) }}
                    </span>
                @endif

                @if($showPerPage)
                    <span class="d-flex align-items-center gap-1">
                        <label for="{{ $tableId }}PerPage" class="mb-0">{{ $perPageLabel }}</label>
                        <select id="{{ $tableId }}PerPage" class="form-select form-select-sm w-auto" data-filter-per-page>
                            @foreach($perPageOptions as $perPageOption)
                                @php
                                    $perPageValue = is_string($perPageOption) ? strtolower($perPageOption) : $perPageOption;
                                    $perPageText = $perPageValue === 'all' ? __('common.all') : $perPageOption;
                                @endphp
                                <option value="{{ $perPageValue }}" @selected((string) $currentPerPage === (string) $perPageValue)>{{ $perPageText }}</option>
                            @endforeach
                        </select>
                    </span>
                @endif
            </div>

            @if($pagination && method_exists($paginator, 'hasPages') && $paginator->hasPages())
                <div>
                    {{ $paginator->withQueryString()->links() }}
                </div>
            @endif
        </div>
    @endif
</div>
