@extends('layouts.app')

@section('title', 'Stock Locations')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Stock Locations</h4>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createLocationModal">
            <i class="ti ti-plus"></i> New Location
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
                        <th>Name</th><th>Type</th><th>Department</th><th>Main?</th><th>Active</th><th>Notes</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($locations as $loc)
                    <tr>
                        <td><strong>{{ $loc->name }}</strong></td>
                        <td><span class="badge bg-secondary text-uppercase">{{ $loc->type }}</span></td>
                        <td>{{ $loc->department->name ?? '—' }}</td>
                        <td>@if($loc->is_main)<span class="badge bg-warning">Main Store</span>@endif</td>
                        <td>
                            @if($loc->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-danger">Inactive</span>
                            @endif
                        </td>
                        <td class="text-truncate" style="max-width: 240px;">{{ $loc->notes }}</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary"
                                    data-bs-toggle="modal" data-bs-target="#editLocationModal{{ $loc->id }}">
                                <i class="ti ti-edit"></i>
                            </button>
                            <form action="{{ route('admin.stock-locations.toggle', $loc) }}" method="POST" class="d-inline">
                                @csrf @method('PATCH')
                                <button class="btn btn-sm btn-outline-secondary" title="Toggle active">
                                    <i class="ti ti-toggle-right"></i>
                                </button>
                            </form>
                        </td>
                    </tr>

                    {{-- Edit modal --}}
                    <div class="modal fade" id="editLocationModal{{ $loc->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <form action="{{ route('admin.stock-locations.update', $loc) }}" method="POST" class="modal-content">
                                @csrf @method('PUT')
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit {{ $loc->name }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    @include('admin.stock-locations._form', ['loc' => $loc])
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button class="btn btn-primary">Save</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No stock locations yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Create modal --}}
<div class="modal fade" id="createLocationModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('admin.stock-locations.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">New Stock Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @include('admin.stock-locations._form', ['loc' => null])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary">Create</button>
            </div>
        </form>
    </div>
</div>
@endsection
