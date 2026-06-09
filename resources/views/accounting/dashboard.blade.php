@extends('layouts.app')
@section('title', 'Accounting Dashboard')

@section('content')
<x-page-header title="Accounting Dashboard" description="Double-entry accounting foundation." icon="ti-calculator">
    <x-slot:actions>
        @can('accounting.journals.create')
            <a href="{{ route('admin.accounting.journals.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i>New Journal
            </a>
        @endcan
    </x-slot:actions>
</x-page-header>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card"><div class="card-body py-3">
            <div class="text-muted small">Accounts</div>
            <div class="fs-4 fw-bold">{{ number_format($stats['accounts']) }}</div>
            <div class="small text-success">{{ number_format($stats['active_accounts']) }} active</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body py-3">
            <div class="text-muted small">Open Periods</div>
            <div class="fs-4 fw-bold">{{ number_format($stats['open_periods']) }}</div>
            <div class="small text-muted">Posting windows</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body py-3">
            <div class="text-muted small">Draft Journals</div>
            <div class="fs-4 fw-bold">{{ number_format($stats['draft_journals']) }}</div>
            <div class="small text-warning">Awaiting post</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body py-3">
            <div class="text-muted small">Ledger Balance Check</div>
            <div class="fs-5 fw-bold {{ $stats['period_balanced'] ? 'text-success' : 'text-danger' }}">
                {{ $stats['period_balanced'] ? 'Balanced' : 'Out of Balance' }}
            </div>
            <div class="small text-muted">GH₵ {{ number_format($stats['period_debits'], 2) }} / GH₵ {{ number_format($stats['period_credits'], 2) }}</div>
        </div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Recent Journals</h5>
                @can('accounting.journals.view')
                    <a href="{{ route('admin.accounting.journals.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                @endcan
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Journal</th>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($stats['recent_journals'] as $entry)
                        <tr>
                            <td><a href="{{ route('admin.accounting.journals.show', $entry) }}" class="fw-semibold">{{ $entry->journal_number }}</a></td>
                            <td>{{ $entry->entry_date?->format('d M Y') }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($entry->description, 55) }}</td>
                            <td><span class="badge bg-{{ $entry->status->color() }}">{{ $entry->status->label() }}</span></td>
                            <td class="text-end">GH₵ {{ number_format($entry->total_debit, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No journal entries yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Accounting Setup</h5></div>
            <div class="list-group list-group-flush">
                <a class="list-group-item list-group-item-action" href="{{ route('admin.accounting.accounts.index') }}"><i class="ti ti-list-tree me-2"></i>Chart of Accounts</a>
                <a class="list-group-item list-group-item-action" href="{{ route('admin.accounting.fiscal-years.index') }}"><i class="ti ti-calendar-stats me-2"></i>Fiscal Years</a>
                <a class="list-group-item list-group-item-action" href="{{ route('admin.accounting.periods.index') }}"><i class="ti ti-calendar-time me-2"></i>Accounting Periods</a>
                <a class="list-group-item list-group-item-action" href="{{ route('admin.accounting.settings.index') }}"><i class="ti ti-settings-dollar me-2"></i>Accounting Settings</a>
            </div>
        </div>
    </div>
</div>
@endsection
