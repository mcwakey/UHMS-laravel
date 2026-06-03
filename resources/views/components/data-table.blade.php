@props([
    'paginator' => null,
    'striped' => false,
    'hover' => true,
    'alignMiddle' => true,
    'responsive' => true,
    'tableClass' => null,
    'pagination' => true,
])

@php
    $tClass = 'table'
        .($hover ? ' table-hover' : '')
        .($striped ? ' table-striped' : '')
        .($alignMiddle ? ' align-middle' : '')
        .' mb-0'
        .($tableClass ? ' '.$tableClass : '');
@endphp

{{--
    Standard table wrapper. Provide the header via <x-slot:head> (a <tr>...</tr>)
    and the body rows (incl. the @forelse/@empty) in the default slot. It only
    wraps markup — it never loads or hides data. Pagination renders bottom-right
    when a paginator with pages is passed.
--}}
<div {{ $attributes->merge(['class' => 'card']) }}>
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
    @if($pagination && $paginator && is_object($paginator) && method_exists($paginator, 'hasPages') && $paginator->hasPages())
        <div class="card-footer d-flex justify-content-end">
            {{ $paginator->withQueryString()->links() }}
        </div>
    @endif
</div>
