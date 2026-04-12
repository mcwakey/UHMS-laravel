@extends('layouts.app')
@section('title', 'Insurance Claims')

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Insurance Claims
            <span class="badge badge-soft-primary border border-primary fs-13 fw-medium ms-2">Total: {{ $claims->total() }}</span>
        </h4>
    </div>
    <div class="text-end d-flex gap-2">
        @can('claims.export')
        <a href="{{ route('admin.claims.export', request()->all()) }}" class="btn btn-outline-success btn-md fs-13">
            <i class="ti ti-file-spreadsheet me-1"></i>Export CSV
        </a>
        @endcan
        @can('claims.create')
        <a href="{{ route('admin.claims.create') }}" class="btn btn-primary btn-md fs-13">
            <i class="ti ti-plus me-1"></i>New Claim
        </a>
        @endcan
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<!-- Stats Cards -->
<div class="row mb-3">
    <div class="col-md-3 col-sm-6">
        <div class="card">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-md bg-warning bg-opacity-10 rounded me-3">
                        <i class="ti ti-file-text fs-4 text-warning"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">{{ $stats['draft'] ?? 0 }}</h4>
                        <small class="text-muted">Draft</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-md bg-info bg-opacity-10 rounded me-3">
                        <i class="ti ti-send fs-4 text-info"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">{{ ($stats['submitted'] ?? 0) + ($stats['under_review'] ?? 0) }}</h4>
                        <small class="text-muted">Pending Review</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-md bg-success bg-opacity-10 rounded me-3">
                        <i class="ti ti-check fs-4 text-success"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">{{ ($stats['approved'] ?? 0) + ($stats['partially_approved'] ?? 0) }}</h4>
                        <small class="text-muted">Approved</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-md bg-primary bg-opacity-10 rounded me-3">
                        <i class="ti ti-currency-dollar fs-4 text-primary"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">GH₵ {{ number_format($stats['total_approved_amount'] ?? 0, 2) }}</h4>
                        <small class="text-muted">Total Approved</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.claims.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Search claim #, patient..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status->value }}" {{ request('status') == $status->value ? 'selected' : '' }}>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="provider_id" class="form-select">
                    <option value="">All Providers</option>
                    @foreach($providers as $provider)
                        <option value="{{ $provider->id }}" {{ request('provider_id') == $provider->id ? 'selected' : '' }}>{{ $provider->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control" placeholder="From" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-outline-primary w-100"><i class="ti ti-search"></i></button>
            </div>
            @if(request()->hasAny(['search', 'status', 'provider_id', 'date_from']))
            <div class="col-md-1">
                <a href="{{ route('admin.claims.index') }}" class="btn btn-outline-secondary w-100"><i class="ti ti-x"></i></a>
            </div>
            @endif
        </form>
    </div>
</div>

<!-- Claims Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Claim #</th>
                        <th>Patient</th>
                        <th>Provider</th>
                        <th>Claim Date</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Approved</th>
                        <th>Items</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($claims as $claim)
                    <tr>
                        <td>
                            <a href="{{ route('admin.claims.show', $claim) }}" class="fw-medium text-primary">
                                {{ $claim->claim_number }}
                            </a>
                        </td>
                        <td>{{ $claim->patient->first_name }} {{ $claim->patient->last_name }}</td>
                        <td><span class="badge bg-{{ $claim->insuranceProvider->type->color() }}">{{ $claim->insuranceProvider->short_name ?? $claim->insuranceProvider->name }}</span></td>
                        <td>{{ $claim->claim_date->format('d M Y') }}</td>
                        <td class="text-end fw-medium">GH₵ {{ number_format($claim->total_amount, 2) }}</td>
                        <td class="text-end">
                            @if($claim->approved_amount)
                                GH₵ {{ number_format($claim->approved_amount, 2) }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td><span class="badge bg-soft-info">{{ $claim->items_count }}</span></td>
                        <td><span class="badge bg-{{ $claim->status->color() }}">{{ $claim->status->label() }}</span></td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-white border dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a href="{{ route('admin.claims.show', $claim) }}" class="dropdown-item">
                                            <i class="ti ti-eye me-1"></i>View
                                        </a>
                                    </li>
                                    @if($claim->is_editable)
                                    <li>
                                        <form method="POST" action="{{ route('admin.claims.submit', $claim) }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item">
                                                <i class="ti ti-send me-1"></i>Submit
                                            </button>
                                        </form>
                                    </li>
                                    @endif
                                    @if($claim->is_reviewable)
                                    @can('claims.approve')
                                    <li>
                                        <a href="{{ route('admin.claims.review', $claim) }}" class="dropdown-item">
                                            <i class="ti ti-checklist me-1"></i>Review
                                        </a>
                                    </li>
                                    @endcan
                                    @endif
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="ti ti-file-off fs-2 d-block mb-2"></i>
                            No claims found
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($claims->hasPages())
<div class="d-flex justify-content-end mt-3">
    {{ $claims->withQueryString()->links() }}
</div>
@endif
@endsection
