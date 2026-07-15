@extends('layouts.app')
@section('title', __('nursing.treatments.title'))
@section('content')
<x-page-header :title="__('nursing.treatments.title')" icon="ti-first-aid-kit" />
<div class="card border shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
    <thead><tr><th>{{ __('nursing.common.patient') }}</th><th>{{ __('nursing.common.visit') }}</th><th>{{ __('nursing.common.type') }}</th><th>{{ __('nursing.common.description') }}</th><th></th></tr></thead>
    <tbody>@forelse($treatments as $treatment)<tr><td>{{ $treatment->patient?->patient_number }}</td><td>{{ $treatment->visit?->visit_number }}</td><td>{{ $treatment->type }}</td><td>{{ \Illuminate\Support\Str::limit($treatment->description, 80) }}</td><td class="text-end"><a class="btn btn-sm btn-primary" href="{{ route('nursing.treatments.show', $treatment->visit) }}">{{ __('nursing.common.view') }}</a></td></tr>@empty<tr><td colspan="5" class="text-center text-muted py-5">{{ __('nursing.treatments.empty') }}</td></tr>@endforelse</tbody>
</table></div></div><div class="mt-3">{{ $treatments->links() }}</div>
@endsection
