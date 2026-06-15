@extends('layouts.app')
@section('title', __('accounting.statement_import'))

@section('content')
<x-page-header :title="__('accounting.statement_import') . ' · ' . ($import->original_filename ?? $import->id)" icon="ti-file-text" />

@if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">{{ __('common.status') }}</div><span class="badge bg-{{ ['imported'=>'success','approved'=>'success','rejected'=>'danger'][$import->status] ?? 'warning' }}">{{ __('statuses.default.' . $import->status) }}</span></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">{{ __('accounting.statement_lines') }}</div><div class="h5 mb-0">{{ $import->line_count }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">{{ __('accounting.opening_statement_balance') }}</div><div class="h5 mb-0">&#8373;{{ number_format($import->opening_balance, 2) }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">{{ __('accounting.closing_statement_balance') }}</div><div class="h5 mb-0">&#8373;{{ number_format($import->closing_balance, 2) }}</div></div></div></div>
</div>

@can('accounting.bank_statements.reject')
@if(!in_array($import->status, ['rejected','approved']))
<form method="POST" action="{{ route('admin.accounting.bank.imports.reject', $import) }}" class="card mb-3" onsubmit="return confirm('{{ __('accounting.reject_import') }}?')">
    @csrf
    <div class="card-body d-flex gap-2 align-items-end">
        <div class="flex-grow-1"><label class="form-label">{{ __('common.reason') }}</label><input type="text" name="reason" class="form-control" required></div>
        <button class="btn btn-outline-danger"><i class="ti ti-ban me-1"></i>{{ __('accounting.reject_import') }}</button>
    </div>
</form>
@endif
@endcan

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle">
                <thead class="table-light"><tr>
                    <th>#</th><th>{{ __('accounting.transaction_date') }}</th><th>{{ __('accounting.reference') }}</th><th>{{ __('accounting.description') }}</th>
                    <th class="text-end">{{ __('accounting.debit_amount') }}</th><th class="text-end">{{ __('accounting.credit_amount') }}</th><th>{{ __('accounting.match_status') }}</th>
                </tr></thead>
                <tbody>
                    @foreach($import->lines as $line)
                    <tr>
                        <td>{{ $line->line_number }}</td>
                        <td>{{ optional($line->transaction_date)->format('d M Y') }}</td>
                        <td>{{ $line->reference }}</td>
                        <td>{{ $line->description }}</td>
                        <td class="text-end">@if($line->debit_amount > 0)&#8373;{{ number_format($line->debit_amount, 2) }}@endif</td>
                        <td class="text-end">@if($line->credit_amount > 0)&#8373;{{ number_format($line->credit_amount, 2) }}@endif</td>
                        <td><span class="badge bg-{{ $line->match_status === 'matched' ? 'success' : ($line->match_status === 'partially_matched' ? 'info' : 'secondary') }}">{{ __('statuses.default.' . $line->match_status) }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
