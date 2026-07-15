@extends('layouts.app')
@section('title', __('claims.insurance_claims'))

@section('content')
<x-page-header :title="__('claims.insurance_claims')" icon="ti-file-dollar">
    <span class="badge badge-soft-primary border border-primary fs-13 fw-medium ms-2">{{ __('claims.total') }}: {{ $claims->total() }}</span>
    <x-slot:actions>
        <a href="{{ $workspaceRoutes->route('admin.claims.eligible-visits') }}" class="btn btn-outline-primary btn-md fs-13">
            <i class="ti ti-user-check me-1"></i>{{ __('claims.eligible_visits') }}
        </a>
        <a href="{{ $workspaceRoutes->route('admin.claims.nhia.index') }}" class="btn btn-outline-info btn-md fs-13">
            <i class="ti ti-shield-check me-1"></i>{{ __('claims.provider_claims') }}
        </a>
        @can('claims.export')
        <a href="{{ $workspaceRoutes->route('admin.claims.export', request()->all()) }}" class="btn btn-outline-success btn-md fs-13">
            <i class="ti ti-file-spreadsheet me-1"></i>{{ __('claims.export_csv') }}
        </a>
        @endcan
        @can('claims.create')
        <a href="{{ $workspaceRoutes->route('admin.claims.create') }}" class="btn btn-primary btn-md fs-13">
            <i class="ti ti-plus me-1"></i>{{ __('claims.new_claim') }}
        </a>
        @endcan
    </x-slot:actions>
</x-page-header>

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
                        <small class="text-muted">{{ __('claims.draft') }}</small>
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
                        <small class="text-muted">{{ __('claims.pending_review') }}</small>
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
                        <small class="text-muted">{{ __('claims.approved') }}</small>
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
                        <small class="text-muted">{{ __('claims.total_approved') }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ $workspaceRoutes->route('admin.claims.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="{{ __('claims.search_placeholder') }}" value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">{{ __('claims.all_statuses') }}</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status->value }}" {{ request('status') == $status->value ? 'selected' : '' }}>{{ $status->translatedLabel() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="provider_id" class="form-select">
                    <option value="">{{ __('claims.all_providers') }}</option>
                    @foreach($providers as $provider)
                        <option value="{{ $provider->id }}" {{ request('provider_id') == $provider->id ? 'selected' : '' }}>{{ $provider->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="insurance_type_id" class="form-select">
                    <option value="">{{ __('claims.all_claim_types') }}</option>
                    @foreach($insuranceTypes as $type)
                        <option value="{{ $type->id }}" {{ request('insurance_type_id') == $type->id ? 'selected' : '' }}>{{ $type->code }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control" placeholder="From" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-1">
                <button aria-label="{{ __('claims.search') }}" title="{{ __('claims.search') }}" type="submit" class="btn btn-outline-primary w-100"><i class="ti ti-search"></i></button>
            </div>
            @if(request()->hasAny(['search', 'status', 'provider_id', 'date_from']))
            <div class="col-md-1">
                <a aria-label="{{ __('claims.clear') }}" title="{{ __('claims.clear') }}" href="{{ $workspaceRoutes->route('admin.claims.index') }}" class="btn btn-outline-secondary w-100"><i class="ti ti-x"></i></a>
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
                        <th>{{ __('claims.claim_number') }}</th>
                        <th>{{ __('claims.patient') }}</th>
                        <th>{{ __('claims.provider') }}</th>
                        <th>{{ __('claims.claim_type') }}</th>
                        <th>{{ __('claims.claim_date') }}</th>
                        <th class="text-end">{{ __('claims.total') }}</th>
                        <th class="text-end">{{ __('claims.approved') }}</th>
                        <th>{{ __('claims.items') }}</th>
                        <th>{{ __('claims.status') }}</th>
                        <th class="text-end">{{ __('claims.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($claims as $claim)
                    <tr>
                        <td>
                            <a href="{{ $workspaceRoutes->route('admin.claims.show', $claim) }}" class="fw-medium text-primary">
                                {{ $claim->claim_number }}
                            </a>
                        </td>
                        <td>{{ $claim->patient->first_name }} {{ $claim->patient->last_name }}</td>
                        <td><span class="badge bg-{{ $claim->insuranceProvider->type->color() }}">{{ $claim->insuranceProvider->short_name ?? $claim->insuranceProvider->name }}</span></td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary">{{ $claim->claim_type_code ?: $claim->insuranceType?->code ?: 'GENERIC' }}</span>
                            <small class="text-muted d-block">{{ $claim->claim_workflow_code ?: $claim->insuranceProvider?->claimWorkflowCode() }}</small>
                        </td>
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
                        <td><x-status-badge :status="$claim->status" /></td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button aria-label="{{ __('claims.actions') }}" title="{{ __('claims.actions') }}" class="btn btn-sm btn-white border dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a href="{{ $workspaceRoutes->route('admin.claims.show', $claim) }}" class="dropdown-item">
                                            <i class="ti ti-eye me-1"></i>{{ __('claims.view') }}
                                        </a>
                                    </li>
                                    @if($claim->is_editable)
                                    <li>
                                        <form method="POST" action="{{ $workspaceRoutes->route('admin.claims.submit', $claim) }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item">
                                                <i class="ti ti-send me-1"></i>{{ __('claims.submit') }}
                                            </button>
                                        </form>
                                    </li>
                                    @endif
                                    @if($claim->is_reviewable)
                                    @can('claims.approve')
                                    <li>
                                        <a href="{{ $workspaceRoutes->route('admin.claims.review', $claim) }}" class="dropdown-item">
                                            <i class="ti ti-checklist me-1"></i>{{ __('claims.review') }}
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
                        <td colspan="10" class="text-center text-muted py-4">
                            <i class="ti ti-file-off fs-2 d-block mb-2"></i>
                            {{ __('claims.no_claims_found') }}
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
