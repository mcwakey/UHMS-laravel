@extends('layouts.app')
@section('title', ucfirst($type) . ' Entries')

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ ucfirst($type) }} Entries
            <span class="badge badge-soft-{{ $type === 'income' ? 'success' : 'danger' }} border border-{{ $type === 'income' ? 'success' : 'danger' }} fs-13 fw-medium ms-2">
                Total: {{ $entries->total() }}
            </span>
        </h4>
    </div>
    <div>
        @can('accounts.entries.create')
        <a href="{{ route($type === 'income' ? 'admin.accounts.income.create' : 'admin.accounts.expenses.create') }}" class="btn btn-primary btn-md fs-13">
            <i class="ti ti-plus me-1"></i>Record {{ ucfirst($type) }}
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
    <div class="col-md-6">
        <div class="card">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-md bg-{{ $type === 'income' ? 'success' : 'danger' }} bg-opacity-10 rounded me-3">
                        <i class="ti ti-{{ $type === 'income' ? 'trending-up' : 'trending-down' }} fs-4 text-{{ $type === 'income' ? 'success' : 'danger' }}"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">GH₵ {{ number_format($totalAmount, 2) }}</h4>
                        <small class="text-muted">Filtered Total</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-md bg-primary bg-opacity-10 rounded me-3">
                        <i class="ti ti-calendar-stats fs-4 text-primary"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">GH₵ {{ number_format($monthTotal, 2) }}</h4>
                        <small class="text-muted">This Month</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route($type === 'income' ? 'admin.accounts.income.index' : 'admin.accounts.expenses.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Search entry #, description..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="category_id" class="form-select">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="payment_method" class="form-select">
                    <option value="">All Methods</option>
                    @foreach($paymentMethods as $pm)
                        <option value="{{ $pm->value }}" {{ request('payment_method') == $pm->value ? 'selected' : '' }}>{{ $pm->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-outline-primary w-100"><i class="ti ti-search"></i></button>
            </div>
            @if(request()->hasAny(['search', 'category_id', 'payment_method', 'date_from']))
            <div class="col-md-1">
                <a href="{{ route($type === 'income' ? 'admin.accounts.income.index' : 'admin.accounts.expenses.index') }}" class="btn btn-outline-secondary w-100"><i class="ti ti-x"></i></a>
            </div>
            @endif
        </form>
    </div>
</div>

<!-- Entries Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Entry #</th>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Method</th>
                        <th class="text-end">Amount</th>
                        <th>Recorded By</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $entry)
                    <tr>
                        <td class="fw-medium">{{ $entry->entry_number }}</td>
                        <td>{{ $entry->entry_date->format('d M Y') }}</td>
                        <td><span class="badge bg-light text-dark">{{ $entry->category->name }}</span></td>
                        <td>{{ Str::limit($entry->description, 40) }}</td>
                        <td>{{ $entry->payment_method?->label() ?? '-' }}</td>
                        <td class="text-end fw-medium">GH₵ {{ number_format($entry->amount, 2) }}</td>
                        <td>{{ $entry->recordedByUser->name ?? '-' }}</td>
                        <td>
                            @if($entry->is_approved)
                                <span class="badge bg-success">Approved</span>
                            @else
                                <span class="badge bg-warning">Pending</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-white border dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    @if(!$entry->is_approved)
                                    @can('accounts.entries.approve')
                                    <li>
                                        <form method="POST" action="{{ route('admin.accounts.entries.approve', $entry) }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item">
                                                <i class="ti ti-check me-1"></i>Approve
                                            </button>
                                        </form>
                                    </li>
                                    @endcan
                                    @can('accounts.entries.create')
                                    <li>
                                        <form method="POST" action="{{ route('admin.accounts.entries.destroy', $entry) }}" onsubmit="return confirm('Delete this entry?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="ti ti-trash me-1"></i>Delete
                                            </button>
                                        </form>
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
                            No {{ $type }} entries found
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($entries->hasPages())
<div class="d-flex justify-content-end mt-3">
    {{ $entries->withQueryString()->links() }}
</div>
@endif
@endsection
