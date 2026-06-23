@extends('layouts.app')
@section('title', 'Corporate Sponsors')

@section('content')
<x-page-header title="Corporate Sponsors" icon="ti-building-bank">
    <x-slot:actions>
        @if($canManage)
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#sponsorModal"><i class="ti ti-plus me-1"></i>New Sponsor</button>
        @endif
    </x-slot:actions>
</x-page-header>

<div class="row g-3 mb-4">
    <div class="col-md-3"><x-stat-card title="Total Sponsors" :value="$stats['total'] ?? 0" icon="ti-building-bank" variant="primary" /></div>
    <div class="col-md-3"><x-stat-card title="Active" :value="$stats['active'] ?? 0" icon="ti-circle-check" variant="success" /></div>
</div>

<x-filter-bar :action="route('admin.billing.sponsors.index')" :reset-url="route('admin.billing.sponsors.index')">
    <div class="col-md-5">
        <label class="form-label small">{{ __('common.search') }}</label>
        <input type="text" name="search" class="form-control" placeholder="Name or code..." value="{{ request('search') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label small">{{ __('common.status') }}</label>
        <select name="status" class="form-select">
            <option value="">All</option>
            <option value="active" @selected(request('status') === 'active')>{{ __('common.active') }}</option>
            <option value="inactive" @selected(request('status') === 'inactive')>{{ __('common.inactive') }}</option>
        </select>
    </div>
</x-filter-bar>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light"><tr><th>Code</th><th>Name</th><th>Contact</th><th class="text-end">Credit Limit</th><th class="text-center">Invoices</th><th class="text-end">Outstanding</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    @forelse($sponsors as $sponsor)
                        <tr>
                            <td class="fw-medium">{{ $sponsor->code }}</td>
                            <td><div class="fw-medium">{{ $sponsor->name }}</div><small class="text-muted">{{ $sponsor->email }}</small></td>
                            <td><div>{{ $sponsor->contact_person ?? 'N/A' }}</div><small class="text-muted">{{ $sponsor->phone }}</small></td>
                            <td class="text-end">{{ $sponsor->credit_limit !== null ? '₵'.number_format($sponsor->credit_limit, 2) : 'N/A' }}</td>
                            <td class="text-center">{{ $sponsor->invoices_count }}</td>
                            <td class="text-end">{{ '₵'.number_format($sponsor->outstanding_balance ?? 0, 2) }}</td>
                            <td><span class="badge bg-{{ $sponsor->is_active ? 'success' : 'secondary' }}">{{ $sponsor->is_active ? __('common.active') : __('common.inactive') }}</span></td>
                            <td class="text-end">
                                @if($canManage)
                                    <form method="POST" action="{{ route('admin.billing.sponsors.toggle', $sponsor) }}">
                                        @csrf @method('PATCH')
                                        <button class="btn btn-sm btn-outline-secondary">{{ $sponsor->is_active ? 'Deactivate' : 'Activate' }}</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">No sponsors found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($sponsors->hasPages())<div class="card-footer">{{ $sponsors->withQueryString()->links() }}</div>@endif
</div>

@if($canManage)
<div class="modal fade" id="sponsorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <form method="POST" action="{{ route('admin.billing.sponsors.store') }}">
            @csrf
            <div class="modal-header"><h5 class="modal-title">New Sponsor</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body row g-3">
                <div class="col-md-6"><label class="form-label">Name</label><input name="name" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">Code</label><input name="code" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Contact Person</label><input name="contact_person" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Phone</label><input name="phone" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Credit Limit</label><input type="number" step="0.01" name="credit_limit" class="form-control"></div>
                <div class="col-12"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="2"></textarea></div>
                <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button><button class="btn btn-primary">{{ __('common.save') }}</button></div>
        </form>
    </div></div>
</div>
@endif
@endsection
