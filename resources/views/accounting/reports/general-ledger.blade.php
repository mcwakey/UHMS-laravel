@extends('layouts.app')
@section('title', 'General Ledger')

@section('content')
<x-page-header title="General Ledger" icon="ti-books" />

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small">Account</label>
                <select name="account_id" class="form-select select2" required>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}" @selected(request('account_id', $report['account']->id ?? null) == $account->id)>{{ $account->display_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">From</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small">To</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Source</label>
                <select name="source_module" class="form-select">
                    <option value="">All</option>
                    @foreach($sourceModules as $source)
                        <option value="{{ $source }}" @selected(request('source_module') === $source)>{{ $source }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-primary w-100" type="submit"><i class="ti ti-search me-1"></i>Load</button>
            </div>
        </form>
    </div>
</div>

@if($report)
<div class="card">
    <div class="card-header d-flex justify-content-between">
        <h5 class="card-title mb-0">{{ $report['account']->display_name }}</h5>
        <span class="fw-semibold">Closing: GH₵ {{ number_format($report['closing_balance'], 2) }}</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Journal No</th>
                    <th>Description</th>
                    <th>Reference</th>
                    <th class="text-end">Debit</th>
                    <th class="text-end">Credit</th>
                    <th class="text-end">Running Balance</th>
                </tr>
            </thead>
            <tbody>
                <tr class="table-light">
                    <td colspan="6" class="fw-semibold">Opening Balance</td>
                    <td class="text-end fw-semibold">GH₵ {{ number_format($report['opening_balance'], 2) }}</td>
                </tr>
                @forelse($report['rows'] as $row)
                    @php($line = $row['line'])
                    <tr>
                        <td>{{ $line->journalEntry->entry_date?->format('d M Y') }}</td>
                        <td><a href="{{ route('admin.accounting.journals.show', $line->journalEntry) }}">{{ $line->journalEntry->journal_number }}</a></td>
                        <td>{{ $line->description ?: $line->journalEntry->description }}</td>
                        <td>{{ $line->journalEntry->reference_number ?? '-' }}</td>
                        <td class="text-end">GH₵ {{ number_format((float) $line->debit, 2) }}</td>
                        <td class="text-end">GH₵ {{ number_format((float) $line->credit, 2) }}</td>
                        <td class="text-end fw-semibold">GH₵ {{ number_format($row['running_balance'], 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No ledger activity found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@else
    <div class="alert alert-info">Create an account before opening the ledger.</div>
@endif
@endsection
