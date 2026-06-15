@extends('layouts.app')
@section('title', __('accounting.bank_accounts'))

@section('content')
<x-page-header :title="__('accounting.bank_accounts')" icon="ti-building-bank">
    @can('accounting.bank_accounts.manage')
        <a href="{{ route('admin.accounting.bank.accounts.create') }}" class="btn btn-primary"><i class="ti ti-plus me-1"></i>{{ __('accounting.add_bank_account') }}</a>
    @endcan
</x-page-header>

@if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('accounting.bank_account') }}</th>
                        <th>{{ __('accounting.bank_name') }}</th>
                        <th>{{ __('accounting.masked_account_number') }}</th>
                        <th>{{ __('accounting.gl_account') }}</th>
                        <th class="text-end">{{ __('accounting.opening_balance') }}</th>
                        <th>{{ __('common.status') }}</th>
                        <th class="text-end">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bankAccounts as $account)
                    <tr>
                        <td class="fw-medium">{{ $account->name }}<div class="small text-muted">{{ $account->branch_name }}</div></td>
                        <td>{{ $account->bank_name }}</td>
                        <td>{{ $account->account_number_masked ?? '—' }}</td>
                        <td>{{ $account->glAccount?->code }} — {{ $account->glAccount?->name }}</td>
                        <td class="text-end">&#8373;{{ number_format($account->opening_balance, 2) }}</td>
                        <td><span class="badge bg-{{ $account->is_active ? 'success' : 'secondary' }}">{{ $account->is_active ? __('common.active') : __('common.inactive') }}</span></td>
                        <td class="text-end">
                            @can('accounting.bank_accounts.manage')
                            <a href="{{ route('admin.accounting.bank.accounts.edit', $account) }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-edit"></i></a>
                            @if($account->is_active)
                            <form method="POST" action="{{ route('admin.accounting.bank.accounts.disable', $account) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-danger"><i class="ti ti-ban"></i></button></form>
                            @else
                            <form method="POST" action="{{ route('admin.accounting.bank.accounts.activate', $account) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-success"><i class="ti ti-check"></i></button></form>
                            @endif
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">{{ __('common.no_records') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $bankAccounts->links() }}
    </div>
</div>
@endsection
