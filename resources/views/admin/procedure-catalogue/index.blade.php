@extends('layouts.app')
@section('title', __('procedures.procedure_catalogue'))

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0"><i class="ti ti-stethoscope me-2"></i>{{ __('procedures.procedure_catalogue') }}</h4>
        <small class="text-muted">{{ __('procedures.procedure_services_count', ['count' => $services->total() ?? $services->count()]) }}</small>
    </div>
</div>

@if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
@if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.procedure-catalogue.index') }}" class="row g-2 align-items-end">
            <div class="col-md-6">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="{{ __('procedures.search_procedure_service') }}" value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary btn-sm w-100" type="submit"><i class="ti ti-search me-1"></i>{{ __('common.search') }}</button>
            </div>
            @if(request('search'))
                <div class="col-md-2">
                    <a class="btn btn-outline-secondary btn-sm w-100" href="{{ route('admin.procedure-catalogue.index') }}">{{ __('common.clear') }}</a>
                </div>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('common.code') }}</th>
                        <th>{{ __('procedures.service_col') }}</th>
                        <th>{{ __('common.department') }}</th>
                        <th class="text-end">{{ __('common.price') }}</th>
                        <th>{{ __('common.status') }}</th>
                        <th class="text-end">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($services as $service)
                        <tr>
                            <td><code>{{ $service->code }}</code></td>
                            <td>{{ $service->name }}</td>
                            <td>{{ optional($service->department)->name ?? '—' }}</td>
                            <td class="text-end">{{ number_format((float) $service->price, 2) }}</td>
                            <td>
                                @if($service->is_active)
                                    <span class="badge bg-success-subtle text-success">{{ __('common.active') }}</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">{{ __('common.inactive') }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary"
                                   href="{{ route('admin.procedure-catalogue.show', $service) }}">
                                    <i class="ti ti-settings me-1"></i>{{ __('procedures.configure') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty-state :message="__('procedures.no_procedure_services')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if(method_exists($services, 'links'))
        <div class="card-footer">{{ $services->links() }}</div>
    @endif
</div>
@endsection
