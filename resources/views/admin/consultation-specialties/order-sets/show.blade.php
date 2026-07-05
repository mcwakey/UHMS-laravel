@extends('layouts.app')
@section('title', $orderSet->name)

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0"><i class="{{ $orderSet->icon ?: 'ti ti-packages' }} me-2"></i>{{ $orderSet->name }}</h4>
        <div class="text-muted small"><code>{{ $orderSet->code }}</code> · {{ $profile->name }}</div>
    </div>
    @can('consultation-specialties.configure')<a href="{{ route('admin.consultation-specialties.order-sets.edit', [$profile, $orderSet]) }}" class="btn btn-primary btn-sm">{{ __('consultation_specialties.admin.edit') }}</a>@endcan
</div>
@include('admin.consultation-specialties.partials.flash')
@include('admin.consultation-specialties.partials.nav')

@if($orderSet->applications_count > 0)
    <div class="alert alert-warning">{{ __('consultation_specialties.admin.application_history_exists') }}</div>
@endif

@can('consultation-specialties.configure')
<div class="card mb-3">
    <div class="card-header"><h5 class="card-title mb-0">{{ __('consultation_specialties.admin.order_set_items') }}</h5></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.consultation-specialties.order-sets.items.store', [$profile, $orderSet]) }}" class="row g-2 align-items-end">
            @csrf
            @include('admin.consultation-specialties.partials.order-set-item-fields', ['item' => null])
            <div class="col-md-1"><button class="btn btn-primary w-100">{{ __('consultation_specialties.admin.add') }}</button></div>
        </form>
    </div>
</div>
@endcan

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light"><tr><th>{{ __('consultation_specialties.admin.type') }}</th><th>{{ __('consultation_specialties.admin.label') }}</th><th>{{ __('consultation_specialties.admin.apply_mode') }}</th><th>{{ __('consultation_specialties.admin.target') }}</th><th>{{ __('consultation_specialties.admin.sort_order') }}</th><th>{{ __('consultation_specialties.admin.status') }}</th><th class="text-end">{{ __('consultation_specialties.admin.actions') }}</th></tr></thead>
                <tbody>
                @forelse($orderSet->items as $item)
                    <tr>
                        <form method="POST" action="{{ route('admin.consultation-specialties.order-sets.items.update', [$profile, $orderSet, $item]) }}">
                            @csrf @method('PATCH')
                            @include('admin.consultation-specialties.partials.order-set-item-fields', ['item' => $item])
                            <td class="text-end">
                                @can('consultation-specialties.configure')<button class="btn btn-sm btn-primary">{{ __('consultation_specialties.admin.update') }}</button>@endcan
                                @can('consultation-specialties.delete')<button form="delete-item-{{ $item->id }}" class="btn btn-sm btn-outline-danger">{{ __('consultation_specialties.admin.delete') }}</button>@endcan
                            </td>
                        </form>
                        <form id="delete-item-{{ $item->id }}" method="POST" action="{{ route('admin.consultation-specialties.order-sets.items.destroy', [$profile, $orderSet, $item]) }}">@csrf @method('DELETE')</form>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">{{ __('consultation_specialties.admin.no_records') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
