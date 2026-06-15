@extends('layouts.app')
@section('title', __('accounting.dashboard'))

@section('content')
<x-page-header :title="__('accounting.dashboard')" :description="__('accounting.dashboard_description')" icon="ti-calculator">
    <x-slot:actions>
        @can('accounting.journals.create')
            <a href="{{ route('admin.accounting.journals.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i>{{ __('accounting.new_journal') }}
            </a>
        @endcan
    </x-slot:actions>
</x-page-header>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card"><div class="card-body py-3">
            <div class="text-muted small">{{ __('accounting.accounts') }}</div>
            <div class="fs-4 fw-bold">{{ number_format($stats['accounts']) }}</div>
            <div class="small text-success">{{ __('accounting.active_accounts', ['count' => number_format($stats['active_accounts'])]) }}</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body py-3">
            <div class="text-muted small">{{ __('accounting.open_periods') }}</div>
            <div class="fs-4 fw-bold">{{ number_format($stats['open_periods']) }}</div>
            <div class="small text-muted">{{ __('accounting.posting_windows') }}</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body py-3">
            <div class="text-muted small">{{ __('accounting.draft_journals') }}</div>
            <div class="fs-4 fw-bold">{{ number_format($stats['draft_journals']) }}</div>
            <div class="small text-warning">{{ __('accounting.awaiting_post') }}</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body py-3">
            <div class="text-muted small">{{ __('accounting.ledger_balance_check') }}</div>
            <div class="fs-5 fw-bold {{ $stats['period_balanced'] ? 'text-success' : 'text-danger' }}">
                {{ $stats['period_balanced'] ? __('accounting.balanced') : __('accounting.out_of_balance') }}
            </div>
            <div class="small text-muted">GH₵ {{ number_format($stats['period_debits'], 2) }} / GH₵ {{ number_format($stats['period_credits'], 2) }}</div>
        </div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">{{ __('accounting.recent_journals') }}</h5>
                @can('accounting.journals.view')
                    <a href="{{ route('admin.accounting.journals.index') }}" class="btn btn-sm btn-outline-primary">{{ __('common.view_all') }}</a>
                @endcan
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('accounting.journal') }}</th>
                            <th>{{ __('common.date') }}</th>
                            <th>{{ __('common.description') }}</th>
                            <th>{{ __('common.status') }}</th>
                            <th class="text-end">{{ __('common.total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($stats['recent_journals'] as $entry)
                        <tr>
                            <td><a href="{{ route('admin.accounting.journals.show', $entry) }}" class="fw-semibold">{{ $entry->journal_number }}</a></td>
                            <td>{{ $entry->entry_date?->format('d M Y') }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($entry->description, 55) }}</td>
                            <td><span class="badge bg-{{ $entry->status->color() }}">{{ $entry->status->translatedLabel() }}</span></td>
                            <td class="text-end">GH₵ {{ number_format($entry->total_debit, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">{{ __('accounting.no_journal_entries_yet') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">{{ __('accounting.accounting_setup') }}</h5></div>
            <div class="list-group list-group-flush">
                <a class="list-group-item list-group-item-action" href="{{ route('admin.accounting.accounts.index') }}"><i class="ti ti-list-tree me-2"></i>{{ __('accounting.chart_of_accounts') }}</a>
                <a class="list-group-item list-group-item-action" href="{{ route('admin.accounting.fiscal-years.index') }}"><i class="ti ti-calendar-stats me-2"></i>{{ __('accounting.fiscal_years') }}</a>
                <a class="list-group-item list-group-item-action" href="{{ route('admin.accounting.periods.index') }}"><i class="ti ti-calendar-time me-2"></i>{{ __('accounting.accounting_periods') }}</a>
                <a class="list-group-item list-group-item-action" href="{{ route('admin.accounting.settings.index') }}"><i class="ti ti-settings-dollar me-2"></i>{{ __('accounting.accounting_settings') }}</a>
                @can('accounting.failed_postings.view')
                    <a class="list-group-item list-group-item-action" href="{{ route('admin.accounting.posting-attempts.index') }}"><i class="ti ti-history-toggle me-2"></i>{{ __('accounting.posting_attempts') }}</a>
                @endcan
                @can('accounting.mappings.view')
                    <a class="list-group-item list-group-item-action" href="{{ route('admin.accounting.mappings.index') }}"><i class="ti ti-arrows-random me-2"></i>{{ __('accounting.account_mappings') }}</a>
                @endcan
                @can('accounting.close_readiness.view')
                    <a class="list-group-item list-group-item-action" href="{{ route('admin.accounting.close-readiness') }}"><i class="ti ti-checkup-list me-2"></i>{{ __('accounting.close_readiness') }}</a>
                @endcan
            </div>
        </div>
    </div>
</div>
@endsection
