@extends('layouts.app')
@section('title', __('consultation_specialties.admin.order_sets'))

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1"><h4 class="fw-bold mb-0"><i class="ti ti-packages me-2"></i>{{ __('consultation_specialties.admin.order_sets') }} · {{ $profile->name }}</h4></div>
    @can('consultation-specialties.configure')<a href="{{ route('admin.consultation-specialties.order-sets.create', $profile) }}" class="btn btn-primary btn-sm"><i class="ti ti-plus me-1"></i>{{ __('consultation_specialties.admin.create') }}</a>@endcan
</div>
@include('admin.consultation-specialties.partials.flash')
@include('admin.consultation-specialties.partials.nav')

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light"><tr><th>{{ __('consultation_specialties.admin.name') }}</th><th>{{ __('consultation_specialties.admin.code') }}</th><th>{{ __('consultation_specialties.admin.category') }}</th><th class="text-center">{{ __('consultation_specialties.admin.order_set_items') }}</th><th class="text-center">{{ __('consultation_specialties.admin.application_history') }}</th><th class="text-center">{{ __('consultation_specialties.admin.status') }}</th><th class="text-end">{{ __('consultation_specialties.admin.actions') }}</th></tr></thead>
                <tbody>
                @forelse($orderSets as $orderSet)
                    <tr>
                        <td class="fw-semibold">{{ $orderSet->name }}</td>
                        <td><code>{{ $orderSet->code }}</code></td>
                        <td>{{ $orderSet->category ?: __('consultation_specialties.admin.none') }}</td>
                        <td class="text-center"><span class="badge bg-soft-primary">{{ $orderSet->items_count }}</span></td>
                        <td class="text-center"><span class="badge bg-soft-secondary">{{ $orderSet->applications_count }}</span></td>
                        <td class="text-center"><span class="badge bg-{{ $orderSet->is_active ? 'success' : 'danger' }}">{{ $orderSet->is_active ? __('consultation_specialties.admin.active') : __('consultation_specialties.admin.inactive') }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('admin.consultation-specialties.order-sets.show', [$profile, $orderSet]) }}" class="btn btn-sm btn-outline-primary">{{ __('consultation_specialties.admin.view') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">{{ __('consultation_specialties.admin.no_records') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($orderSets->hasPages())<div class="card-footer">{{ $orderSets->links() }}</div>@endif
</div>
@endsection
