@extends('layouts.app')
@section('title', 'Cash Flow Statement')

@php $money = fn ($n) => 'GHS '.number_format((float) $n, 2); @endphp

@section('content')
<x-page-header title="Cash Flow Statement" icon="ti-arrows-exchange" description="Direct-method cash movement across cash and bank accounts." />

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-1">Cash / Bank Account</label>
                <select name="account_id" class="form-select">
                    <option value="">All cash and bank accounts</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}" @selected((string) request('account_id') === (string) $account->id)>{{ $account->display_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">{{ __('common.from') }}</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">{{ __('common.to') }}</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-primary w-100" type="submit"><i class="ti ti-search me-1"></i>{{ __('reports.actions.load') }}</button>
            </div>
            <div class="col-md-3">
                <a class="btn btn-outline-success w-100" href="{{ route('admin.accounting.reports.cash-flow.export', request()->query()) }}">
                    <i class="ti ti-download me-1"></i>Export CSV
                </a>
            </div>
        </form>
    </div>
</div>

<div class="row g-2 mb-3">
    <div class="col-md-4 col-12">
        <div class="card h-100">
            <div class="card-body py-2 text-center">
                <small class="text-muted d-block">Opening cash and bank</small>
                <strong>{{ $money($report['opening_balance']) }}</strong>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-12">
        <div class="card h-100">
            <div class="card-body py-2 text-center">
                <small class="text-muted d-block">Net cash movement</small>
                <strong class="{{ $report['net_change'] >= 0 ? 'text-success' : 'text-danger' }}">{{ $money($report['net_change']) }}</strong>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-12">
        <div class="card h-100 border-primary">
            <div class="card-body py-2 text-center">
                <small class="text-muted d-block">Closing cash and bank</small>
                <strong class="text-primary">{{ $money($report['closing_balance']) }}</strong>
            </div>
        </div>
    </div>
</div>

@foreach($report['sections'] as $section)
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">{{ $section['label'] }}</h5>
            <span class="fw-semibold {{ $section['net'] >= 0 ? 'text-success' : 'text-danger' }}">{{ $money($section['net']) }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('reports.col_date') }}</th>
                        <th>{{ __('reports.columns.journal_number') }}</th>
                        <th>{{ __('reports.columns.description') }}</th>
                        <th>Counterpart</th>
                        <th>{{ __('reports.filters.source') }}</th>
                        <th class="text-end">Inflow</th>
                        <th class="text-end">Outflow</th>
                        <th class="text-end">Net</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($section['rows'] as $row)
                        <tr>
                            <td><small>{{ $row['date'] }}</small></td>
                            <td><small>{{ $row['journal'] }}</small></td>
                            <td>
                                {{ $row['description'] }}
                                <small class="text-muted d-block">{{ $row['cash_accounts'] }}</small>
                            </td>
                            <td><small>{{ $row['counterpart_accounts'] }}</small></td>
                            <td><small>{{ $row['source'] }}</small></td>
                            <td class="text-end text-success">{{ $row['inflow'] > 0 ? $money($row['inflow']) : '' }}</td>
                            <td class="text-end text-danger">{{ $row['outflow'] > 0 ? $money($row['outflow']) : '' }}</td>
                            <td class="text-end fw-semibold">{{ $money($row['net']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-3">No cash movement in this section.</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="5">Section total</th>
                        <th class="text-end text-success">{{ $money($section['inflows']) }}</th>
                        <th class="text-end text-danger">{{ $money($section['outflows']) }}</th>
                        <th class="text-end">{{ $money($section['net']) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endforeach
@endsection
