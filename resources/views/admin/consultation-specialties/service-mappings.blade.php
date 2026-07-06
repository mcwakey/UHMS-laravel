@extends('layouts.app')
@section('title', __('consultation_specialties.billing.admin.service_mappings'))

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1"><h4 class="fw-bold mb-0"><i class="ti ti-receipt me-2"></i>{{ __('consultation_specialties.billing.admin.service_mappings') }}</h4></div>
</div>
@include('admin.consultation-specialties.partials.flash')
@include('admin.consultation-specialties.partials.nav')

<div class="alert alert-info">{{ __('consultation_specialties.billing.admin.auto_bill_warning') }}</div>

<div class="card mb-3">
    <div class="card-header"><h5 class="card-title mb-0">{{ __('consultation_specialties.billing.admin.create_mapping') }}</h5></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.consultation-specialties.service-mappings.store') }}" class="row g-2 align-items-end">
            @csrf
            @include('admin.consultation-specialties.partials.service-mapping-fields', ['mapping' => null])
            <div class="col-md-1"><button class="btn btn-primary w-100">{{ __('consultation_specialties.admin.add') }}</button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('consultation_specialties.admin.profile') }}</th>
                        <th>{{ __('consultation_specialties.billing.default_service') }}</th>
                        <th>{{ __('consultation_specialties.billing.admin.context') }}</th>
                        <th>{{ __('consultation_specialties.admin.department') }}</th>
                        <th>{{ __('consultation_specialties.billing.admin.priority') }}</th>
                        <th>{{ __('consultation_specialties.billing.auto_bill') }}</th>
                        <th>{{ __('consultation_specialties.admin.status') }}</th>
                        <th class="text-end">{{ __('consultation_specialties.admin.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($mappings as $mapping)
                    <tr>
                        <form method="POST" action="{{ route('admin.consultation-specialties.service-mappings.update', $mapping) }}">
                            @csrf @method('PATCH')
                            @include('admin.consultation-specialties.partials.service-mapping-fields', ['mapping' => $mapping])
                            <td class="text-end">
                                <button class="btn btn-sm btn-primary">{{ __('consultation_specialties.admin.update') }}</button>
                                @can('consultation-specialties.delete')<button form="delete-service-mapping-{{ $mapping->id }}" class="btn btn-sm btn-outline-danger">{{ __('consultation_specialties.admin.delete') }}</button>@endcan
                            </td>
                        </form>
                        <form id="delete-service-mapping-{{ $mapping->id }}" method="POST" action="{{ route('admin.consultation-specialties.service-mappings.destroy', $mapping) }}">@csrf @method('DELETE')</form>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">{{ __('consultation_specialties.admin.no_records') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($mappings->hasPages())<div class="card-footer">{{ $mappings->links() }}</div>@endif
</div>
@endsection
