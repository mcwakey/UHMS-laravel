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
    'card' => true,
    'local' => false,
    'searchable' => false,
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
    $localPerPage = $currentPerPage ?? (is_array($perPageOptions) && count($perPageOptions) ? $perPageOptions[0] : 10);
    $hasFooter = $paginator && is_object($paginator)
        && (($showSummary && method_exists($paginator, 'total')) || $showPerPage || ($pagination && method_exists($paginator, 'hasPages') && $paginator->hasPages()));
    $hasLocalFooter = $local && ! $paginator && ($showSummary || $showPerPage || $pagination);
    $tableId = $attributes->get('id') ?: 'dataTable-'.\Illuminate\Support\Str::uuid();
@endphp

{{--
    Standard table wrapper. Provide the header via <x-slot:head> and the body
    rows in the default slot. Optional footer controls render paginator summary,
    per-page selector, and pagination links.
--}}
<div
    {{ $attributes->merge(['class' => $card ? 'card mb-0' : 'mb-0']) }}
    @if($local && ! $paginator) data-local-data-table @endif
>
    @if($local && ! $paginator && $searchable)
        <div class="{{ $card ? 'card-header' : 'border-bottom p-3' }}">
            <div class="input-group input-group-sm" style="max-width: 320px;">
                <span class="input-group-text"><i class="ti ti-search"></i></span>
                <input type="search" class="form-control" placeholder="{{ __('common.search') }}" data-local-table-search>
            </div>
        </div>
    @endif

    <div class="{{ $card ? 'card-body p-0' : 'p-0' }}">
        @if($responsive)<div class="table-responsive">@endif
            <table class="{{ $tClass }}">
                @isset($head)
                    <thead class="bg-light">{{ $head }}</thead>
                @endisset
                <tbody>{{ $slot }}</tbody>
            </table>
        @if($responsive)</div>@endif
    </div>

    @if($hasFooter || $hasLocalFooter)
        <div class="{{ $card ? 'card-footer' : 'border-top p-3' }} d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
            <div class="d-flex flex-wrap align-items-center gap-2 small text-muted">
                @if($showSummary && $paginator && method_exists($paginator, 'total'))
                    <span>
                        {{ __($summaryLabel, [
                            'from' => $paginator->firstItem() ?? 0,
                            'to' => $paginator->lastItem() ?? 0,
                            'total' => $paginator->total(),
                        ]) }}
                    </span>
                @elseif($showSummary && $local && ! $paginator)
                    <span data-local-table-summary data-label="{{ __($summaryLabel, ['from' => '__FROM__', 'to' => '__TO__', 'total' => '__TOTAL__']) }}"></span>
                @endif

                @if($showPerPage && $paginator)
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
                @elseif($showPerPage && $local && ! $paginator)
                    <span class="d-flex align-items-center gap-1">
                        <label for="{{ $tableId }}PerPage" class="mb-0">{{ $perPageLabel }}</label>
                        <select id="{{ $tableId }}PerPage" class="form-select form-select-sm w-auto" data-local-table-per-page>
                            @foreach($perPageOptions as $perPageOption)
                                @php
                                    $perPageValue = is_string($perPageOption) ? strtolower($perPageOption) : $perPageOption;
                                    $perPageText = $perPageValue === 'all' ? __('common.all') : $perPageOption;
                                @endphp
                                <option value="{{ $perPageValue }}" @selected((string) $localPerPage === (string) $perPageValue)>{{ $perPageText }}</option>
                            @endforeach
                        </select>
                    </span>
                @endif
            </div>

            @if($pagination && $paginator && method_exists($paginator, 'hasPages') && $paginator->hasPages())
                <div>
                    {{ $paginator->withQueryString()->links() }}
                </div>
            @elseif($pagination && $local && ! $paginator)
                <nav data-local-table-pagination aria-label="{{ __('common.pagination') }}"></nav>
            @endif
        </div>
    @endif
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const initLocalDataTable = function (wrapper) {
                    if (wrapper.dataset.localDataTableReady === '1') return;
                    wrapper.dataset.localDataTableReady = '1';

                    const tbody = wrapper.querySelector('tbody');
                    if (!tbody) return;

                    const rows = Array.from(tbody.querySelectorAll('tr'));
                    const summary = wrapper.querySelector('[data-local-table-summary]');
                    const perPageSelect = wrapper.querySelector('[data-local-table-per-page]');
                    const pagination = wrapper.querySelector('[data-local-table-pagination]');
                    const searchInput = wrapper.querySelector('[data-local-table-search]');
                    let page = 1;

                    const rowText = function (row) {
                        if (!row.dataset.localSearchText) {
                            row.dataset.localSearchText = row.textContent.toLowerCase();
                        }

                        return row.dataset.localSearchText;
                    };

                    const selectedPerPage = function (filteredCount) {
                        if (!perPageSelect || perPageSelect.value === 'all') {
                            return Math.max(filteredCount, 1);
                        }

                        return Math.max(parseInt(perPageSelect.value, 10) || 10, 1);
                    };

                    const renderPagination = function (totalPages) {
                        if (!pagination) return;

                        pagination.innerHTML = '';
                        if (totalPages <= 1) return;

                        const list = document.createElement('ul');
                        list.className = 'pagination pagination-sm mb-0';

                        const addButton = function (label, targetPage, disabled, active) {
                            const item = document.createElement('li');
                            item.className = 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '');

                            const button = document.createElement('button');
                            button.type = 'button';
                            button.className = 'page-link';
                            button.textContent = label;
                            button.disabled = disabled;
                            button.addEventListener('click', function () {
                                page = targetPage;
                                render();
                            });

                            item.appendChild(button);
                            list.appendChild(item);
                        };

                        addButton('<', Math.max(page - 1, 1), page === 1, false);
                        for (let i = 1; i <= totalPages; i++) {
                            addButton(String(i), i, false, i === page);
                        }
                        addButton('>', Math.min(page + 1, totalPages), page === totalPages, false);

                        pagination.appendChild(list);
                    };

                    const render = function () {
                        const search = searchInput ? searchInput.value.trim().toLowerCase() : '';
                        const filteredRows = rows.filter(function (row) {
                            return !search || rowText(row).includes(search);
                        });
                        const total = filteredRows.length;
                        const perPage = selectedPerPage(total);
                        const totalPages = Math.max(Math.ceil(total / perPage), 1);

                        if (page > totalPages) page = totalPages;

                        const fromIndex = total === 0 ? 0 : (page - 1) * perPage;
                        const toIndex = total === 0 ? 0 : Math.min(fromIndex + perPage, total);
                        const visible = new Set(filteredRows.slice(fromIndex, toIndex));

                        rows.forEach(function (row) {
                            row.classList.toggle('d-none', !visible.has(row));
                        });

                        if (summary) {
                            summary.textContent = summary.dataset.label
                                .replace('__FROM__', total === 0 ? 0 : fromIndex + 1)
                                .replace('__TO__', toIndex)
                                .replace('__TOTAL__', total);
                        }

                        renderPagination(totalPages);
                    };

                    if (perPageSelect) {
                        perPageSelect.addEventListener('change', function () {
                            page = 1;
                            render();
                        });
                    }

                    if (searchInput) {
                        searchInput.addEventListener('input', function () {
                            page = 1;
                            render();
                        });
                    }

                    render();
                };

                document.querySelectorAll('[data-local-data-table]').forEach(initLocalDataTable);
            });
        </script>
    @endpush
@endonce
