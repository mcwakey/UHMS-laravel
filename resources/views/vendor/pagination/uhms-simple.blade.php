@if ($paginator->hasPages())
<nav role="navigation" aria-label="{{ __('Pagination Navigation') }}">
    <ul class="pagination pagination-boxed mb-0">

        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <li class="page-item disabled" aria-disabled="true">
                <span class="page-link"><i class="ti ti-chevron-left"></i></span>
            </li>
        @else
            <li class="page-item">
                <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">
                    <i class="ti ti-chevron-left"></i>
                </a>
            </li>
        @endif

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <li class="page-item">
                <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">
                    <i class="ti ti-chevron-right"></i>
                </a>
            </li>
        @else
            <li class="page-item disabled" aria-disabled="true">
                <span class="page-link"><i class="ti ti-chevron-right"></i></span>
            </li>
        @endif

    </ul>
</nav>
@endif
