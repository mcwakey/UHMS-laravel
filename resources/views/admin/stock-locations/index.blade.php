@extends('layouts.app')

@section('title', __('stock.admin_stock_locations'))

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">{{ __('stock.admin_stock_locations') }}</h4>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createLocationModal">
            <i class="ti ti-plus"></i> {{ __('stock.admin_new_location') }}
        </button>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(isset($errors) && $errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover mb-0">
                <thead>
                    <tr>
                        <th>{{ __('stock.name') }}</th><th>{{ __('stock.type') }}</th><th>{{ __('stock.department') }}</th><th>{{ __('stock.main_store') }}?</th><th>{{ __('stock.active') }}</th><th>{{ __('stock.notes') }}</th><th class="text-end">{{ __('stock.actions') ?? 'Actions' }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($locations as $loc)
                    <tr>
                        <td><strong>{{ $loc->name }}</strong> @if($loc->is_main)<span class="badge bg-primary-subtle text-primary ms-1">{{ __('stock.system_default') }}</span>@endif</td>
                        <td><span class="badge bg-secondary text-uppercase">{{ $loc->type }}</span></td>
                        <td>{{ $loc->department->name ?? '—' }}</td>
                        <td>@if($loc->is_main)<span class="badge bg-warning text-dark">{{ __('stock.main_store') }}</span>@else<span class="text-muted">—</span>@endif</td>
                        <td>
                            @if($loc->is_active)
                                <span class="badge bg-success">{{ __('stock.active') }}</span>
                            @else
                                <span class="badge bg-danger">{{ __('stock.inactive') }}</span>
                            @endif
                        </td>
                        <td style="max-width: 260px;">
                            @if($loc->notes)
                                <span title="{{ $loc->notes }}">{{ \Illuminate\Support\Str::limit($loc->notes, 70) }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($loc->is_main)
                                <span class="text-muted small">{{ __('stock.protected') }}</span>
                            @else
                                <button class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="modal" data-bs-target="#editLocationModal{{ $loc->id }}" aria-label="{{ __('stock.edit_location') }}" title="{{ __('stock.edit_location') }}">
                                    <i class="ti ti-edit"></i>
                                </button>
                                <form action="{{ route('admin.stock-locations.toggle', $loc) }}" method="POST" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-sm btn-outline-secondary" title="{{ __('stock.toggle_active') }}">
                                        <i class="ti ti-toggle-right"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty-state message="{{ __('stock.no_stock_locations_yet') }}" /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@foreach($locations as $loc)
    @unless($loc->is_main)
    <div class="modal fade" id="editLocationModal{{ $loc->id }}" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('admin.stock-locations.update', $loc) }}" method="POST" class="modal-content">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('stock.edit_stock_location') }} {{ $loc->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @include('admin.stock-locations._form', ['loc' => $loc])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('stock.cancel') }}</button>
                    <button class="btn btn-primary">{{ __('stock.save') }}</button>
                </div>
            </form>
        </div>
    </div>
    @endunless
@endforeach

{{-- Create modal --}}
<div class="modal fade" id="createLocationModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('admin.stock-locations.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">{{ __('stock.new_stock_location') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @include('admin.stock-locations._form', ['loc' => null])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('stock.cancel') }}</button>
                <button class="btn btn-primary">{{ __('stock.create') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
