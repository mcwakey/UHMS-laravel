@extends('layouts.app')
@section('title', __('accounting.chart_of_accounts'))

@section('content')
<x-page-header :title="__('accounting.chart_of_accounts')" icon="ti-list-tree">
    <x-slot:actions>
        @can('accounting.accounts.create')
            <a href="{{ route('admin.accounting.accounts.create') }}" class="btn btn-primary"><i class="ti ti-plus me-1"></i>{{ __('accounting.new_account') }}</a>
        @endcan
    </x-slot:actions>
</x-page-header>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small">{{ __('common.search') }}</label>
                <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="{{ __('accounting.code_or_name') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small">{{ __('common.type') }}</label>
                <select name="type" class="form-select">
                    <option value="">{{ __('common.all') }}</option>
                    @foreach($types as $type)
                        <option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ $type->translatedLabel() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">{{ __('common.status') }}</label>
                <select name="active" class="form-select">
                    <option value="">{{ __('common.all') }}</option>
                    <option value="1" @selected(request('active') === '1')>{{ __('common.active') }}</option>
                    <option value="0" @selected(request('active') === '0')>{{ __('common.inactive') }}</option>
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
                    <th>{{ __('accounting.code') }}</th>
                    <th>{{ __('accounting.account') }}</th>
                    <th>{{ __('common.type') }}</th>
                    <th>{{ __('accounting.parent') }}</th>
                    <th>{{ __('accounting.normal') }}</th>
                    <th class="text-end">{{ __('accounting.opening') }}</th>
                    <th>{{ __('common.status') }}</th>
                    <th class="text-end">{{ __('common.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($accounts as $account)
                    <tr>
                        <td class="fw-semibold">{{ $account->code }}</td>
                        <td>
                            {{ $account->name }}
                            @if($account->is_control_account)<span class="badge bg-warning ms-1">{{ __('accounting.control') }}</span>@endif
                            @if($account->is_cash_account)<span class="badge bg-success ms-1">{{ __('accounting.cash') }}</span>@endif
                            @if($account->is_bank_account)<span class="badge bg-info ms-1">{{ __('accounting.bank') }}</span>@endif
                        </td>
                        <td>{{ $account->type->translatedLabel() }}</td>
                        <td>{{ $account->parent?->display_name ?? '-' }}</td>
                        <td>{{ $account->normal_balance->translatedLabel() }}</td>
                        <td class="text-end">GH₵ {{ number_format((float) $account->opening_balance, 2) }}</td>
                        <td><span class="badge bg-{{ $account->is_active ? 'success' : 'secondary' }}">{{ $account->is_active ? __('common.active') : __('common.inactive') }}</span></td>
                        <td class="text-end">
                            @can('accounting.accounts.edit')
                                <a href="{{ route('admin.accounting.accounts.edit', $account) }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-edit"></i></a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">{{ __('accounting.no_accounts_found') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3 d-flex justify-content-end">{{ $accounts->links() }}</div>
@endsection
