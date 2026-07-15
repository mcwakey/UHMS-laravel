@extends('layouts.app')
@section('title', __('reports.claims.title'))

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">{{ __('reports.claims.title') }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('common.dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ __('reports.claims.title') }}</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="{{ $workspaceRoutes->route('admin.reports.claims', array_merge(request()->query(), ['export' => 'excel'])) }}" class="btn btn-success btn-sm">
            <i class="ti ti-file-spreadsheet me-1"></i>{{ __('reports.actions.excel') }}
        </a>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-3">
        <div class="card border-start border-primary border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">{{ __('reports.claims.total_claims') }}</p>
                <h4 class="fw-bold mb-0">{{ number_format($stats['total_claims']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-success border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">{{ __('reports.kpi.claimed_amount') }}</p>
                <h4 class="fw-bold mb-0 text-success">₵{{ number_format($stats['total_claimed'], 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-info border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">{{ __('reports.claims.approved_amount') }}</p>
                <h4 class="fw-bold mb-0 text-info">₵{{ number_format($stats['total_approved'], 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-warning border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">{{ __('reports.investigations.pending') }}</p>
                <h4 class="fw-bold mb-0 text-warning">{{ number_format($stats['pending']) }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ $workspaceRoutes->route('admin.reports.claims') }}" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label">{{ __('reports.filters.date_from') }}</label>
                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ __('reports.filters.date_to') }}</label>
                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('reports.filters.insurance_provider') }}</label>
                <select name="provider_id" class="form-select">
                    <option value="">{{ __('reports.all_providers') }}</option>
                    @foreach($providers as $prov)
                    <option value="{{ $prov->id }}" {{ ($filters['provider_id'] ?? '') == $prov->id ? 'selected' : '' }}>{{ $prov->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ __('reports.status') }}</label>
                <select name="status" class="form-select">
                    <option value="">{{ __('common.all') }}</option>
                    @foreach(\App\Enums\ClaimStatus::cases() as $cs)
                    <option value="{{ $cs->value }}" {{ ($filters['status'] ?? '') == $cs->value ? 'selected' : '' }}>{{ $cs->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary">{{ __('reports.filter') }}</button>
                <a href="{{ $workspaceRoutes->route('admin.reports.claims') }}" class="btn btn-outline-secondary">{{ __('reports.clear') }}</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('reports.claims.claim_no') }}</th>
                    <th>{{ __('reports.claims.claim_date') }}</th>
                    <th>{{ __('reports.col_patient') }}</th>
                    <th>{{ __('reports.claims.provider') }}</th>
                    <th class="text-end">{{ __('reports.columns.claimed') }}</th>
                    <th class="text-end">{{ __('reports.claims.approved_amount') }}</th>
                    <th>{{ __('reports.status') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($claims as $claim)
                <tr>
                    <td><code>{{ $claim->claim_number }}</code></td>
                    <td>{{ $claim->created_at->format('d/m/Y') }}</td>
                    <td>{{ $claim->patient?->full_name ?? '—' }}</td>
                    <td>{{ $claim->insuranceProvider?->name ?? '—' }}</td>
                    <td class="text-end">₵{{ number_format($claim->total_amount, 2) }}</td>
                    <td class="text-end fw-semibold">₵{{ number_format($claim->approved_amount ?? 0, 2) }}</td>
                    <td>
                        @php
                            $statusColors = ['pending' => 'warning', 'submitted' => 'info', 'approved' => 'success', 'partially_approved' => 'primary', 'rejected' => 'danger', 'paid' => 'success'];
                            $sv = $claim->status instanceof \App\Enums\ClaimStatus ? $claim->status->value : $claim->status;
                        @endphp
                        <span class="badge bg-{{ $statusColors[$sv] ?? 'secondary' }}">{{ $claim->status instanceof \App\Enums\ClaimStatus ? $claim->status->label() : ucfirst($sv) }}</span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7"><x-empty-state message="{{ __('reports.empty.no_claims') }}" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($claims->hasPages())
    <div class="card-footer">{{ $claims->links() }}</div>
    @endif
</div>
@endsection
