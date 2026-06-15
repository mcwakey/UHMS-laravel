@extends('layouts.app')
@section('title', __('accounting.bank_reconciliation'))

@section('content')
<x-page-header :title="__('accounting.reconciliations')" icon="ti-arrows-diff">
    @can('accounting.bank_reconciliation.manage')
        <a href="{{ route('admin.accounting.bank.reconciliations.create') }}" class="btn btn-primary"><i class="ti ti-plus me-1"></i>{{ __('accounting.prepare_reconciliation') }}</a>
    @endcan
</x-page-header>

@if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light"><tr>
                    <th>{{ __('accounting.bank_account') }}</th><th>{{ __('accounting.statement_period') }}</th>
                    <th class="text-end">{{ __('accounting.statement_balance') }}</th><th class="text-end">{{ __('accounting.book_balance') }}</th>
                    <th class="text-end">{{ __('accounting.difference') }}</th><th>{{ __('common.status') }}</th><th></th>
                </tr></thead>
                <tbody>
                    @forelse($reconciliations as $rec)
                    <tr>
                        <td>{{ $rec->bankAccount?->name }}</td>
                        <td>{{ $rec->period_start->format('d M Y') }} – {{ $rec->period_end->format('d M Y') }}</td>
                        <td class="text-end">&#8373;{{ number_format($rec->statement_closing_balance, 2) }}</td>
                        <td class="text-end">&#8373;{{ number_format($rec->book_closing_balance, 2) }}</td>
                        <td class="text-end {{ abs((float)$rec->difference) > 0.001 ? 'text-danger fw-semibold' : 'text-success' }}">&#8373;{{ number_format($rec->difference, 2) }}</td>
                        <td><span class="badge bg-{{ ['approved'=>'success','reversed'=>'dark','reopened'=>'warning'][$rec->status] ?? 'info' }}">{{ __('statuses.default.' . $rec->status) }}</span></td>
                        <td class="text-end"><a href="{{ route('admin.accounting.bank.reconciliations.show', $rec) }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-eye"></i></a></td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">{{ __('common.no_records') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $reconciliations->links() }}
    </div>
</div>
@endsection
