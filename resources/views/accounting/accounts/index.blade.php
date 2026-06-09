@extends('layouts.app')
@section('title', 'Chart of Accounts')

@section('content')
<x-page-header title="Chart of Accounts" icon="ti-list-tree">
    <x-slot:actions>
        @can('accounting.accounts.create')
            <a href="{{ route('admin.accounting.accounts.create') }}" class="btn btn-primary"><i class="ti ti-plus me-1"></i>New Account</a>
        @endcan
    </x-slot:actions>
</x-page-header>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small">Search</label>
                <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Code or name">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Type</label>
                <select name="type" class="form-select">
                    <option value="">All</option>
                    @foreach($types as $type)
                        <option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Status</label>
                <select name="active" class="form-select">
                    <option value="">All</option>
                    <option value="1" @selected(request('active') === '1')>Active</option>
                    <option value="0" @selected(request('active') === '0')>Inactive</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-outline-primary" type="submit"><i class="ti ti-search"></i></button>
                <a href="{{ route('admin.accounting.accounts.index') }}" class="btn btn-outline-secondary"><i class="ti ti-x"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Code</th>
                    <th>Account</th>
                    <th>Type</th>
                    <th>Parent</th>
                    <th>Normal</th>
                    <th class="text-end">Opening</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($accounts as $account)
                    <tr>
                        <td class="fw-semibold">{{ $account->code }}</td>
                        <td>
                            {{ $account->name }}
                            @if($account->is_control_account)<span class="badge bg-warning ms-1">Control</span>@endif
                            @if($account->is_cash_account)<span class="badge bg-success ms-1">Cash</span>@endif
                            @if($account->is_bank_account)<span class="badge bg-info ms-1">Bank</span>@endif
                        </td>
                        <td>{{ $account->type->label() }}</td>
                        <td>{{ $account->parent?->display_name ?? '-' }}</td>
                        <td>{{ $account->normal_balance->label() }}</td>
                        <td class="text-end">GH₵ {{ number_format((float) $account->opening_balance, 2) }}</td>
                        <td><span class="badge bg-{{ $account->is_active ? 'success' : 'secondary' }}">{{ $account->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="text-end">
                            @can('accounting.accounts.edit')
                                <a href="{{ route('admin.accounting.accounts.edit', $account) }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-edit"></i></a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No accounts found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3 d-flex justify-content-end">{{ $accounts->links() }}</div>
@endsection
