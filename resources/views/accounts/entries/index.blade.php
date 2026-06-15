@extends('layouts.app')
@section('title', __('accounting.entries_title', ['type' => __('statuses.default.' . $type)]))

@section('content')
<!-- Page Header -->
<div class="uhms-page-header d-flex align-items-sm-center flex-sm-row flex-column gap-2">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ __('accounting.entries_title', ['type' => __('statuses.default.' . $type)]) }}
            <span class="badge badge-soft-{{ $type === 'income' ? 'success' : 'danger' }} border border-{{ $type === 'income' ? 'success' : 'danger' }} fs-13 fw-medium ms-2">
                {{ __('claims.total') }}: {{ $entries->total() }}
            </span>
        </h4>
    </div>
    <div>
        @can('accounts.entries.create')
        <a href="{{ route($type === 'income' ? 'admin.accounts.income.create' : 'admin.accounts.expenses.create') }}" class="btn btn-primary btn-md fs-13">
            <i class="ti ti-plus me-1"></i>{{ __('accounting.record_type', ['type' => __('statuses.default.' . $type)]) }}
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
<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-md bg-{{ $type === 'income' ? 'success' : 'danger' }} bg-opacity-10 rounded me-3">
                        <i class="ti ti-{{ $type === 'income' ? 'trending-up' : 'trending-down' }} fs-4 text-{{ $type === 'income' ? 'success' : 'danger' }}"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">GH₵ {{ number_format($totalAmount, 2) }}</h4>
                        <small class="text-muted">{{ __('accounting.filtered_total') }}</small>
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
                        <small class="text-muted">{{ __('accounting.this_month') }}</small>
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
                <label class="form-label small">{{ __('accounting.search') }}</label>
                <input type="text" name="search" class="form-control" placeholder="{{ __('accounting.search_entry_placeholder') }}" value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small">{{ __('accounting.category') }}</label>
                <select name="category_id" class="form-select">
                    <option value="">{{ __('accounting.all_categories') }}</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">{{ __('accounting.method') }}</label>
                <select name="payment_method" class="form-select">
                    <option value="">{{ __('accounting.all_methods') }}</option>
                    @foreach($paymentMethods as $pm)
                        <option value="{{ $pm->value }}" {{ request('payment_method') == $pm->value ? 'selected' : '' }}>{{ $pm->translatedLabel() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">{{ __('accounting.from') }}</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-1">
                <button aria-label="{{ __('accounting.search') }}" title="{{ __('accounting.search') }}" type="submit" class="btn btn-outline-primary w-100"><i class="ti ti-search"></i></button>
            </div>
            @if(request()->hasAny(['search', 'category_id', 'payment_method', 'date_from']))
            <div class="col-md-1">
                <a aria-label="{{ __('accounting.clear') }}" title="{{ __('accounting.clear') }}" href="{{ route($type === 'income' ? 'admin.accounts.income.index' : 'admin.accounts.expenses.index') }}" class="btn btn-outline-secondary w-100"><i class="ti ti-x"></i></a>
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
                        <th>{{ __('accounting.entry_number') }}</th>
                        <th>{{ __('accounting.date') }}</th>
                        <th>{{ __('accounting.category') }}</th>
                        <th>{{ __('accounting.description') }}</th>
                        <th>{{ __('accounting.method') }}</th>
                        <th class="text-end">{{ __('accounting.amount') }}</th>
                        <th>{{ __('accounting.recorded_by') }}</th>
                        <th>{{ __('accounting.status') }}</th>
                        @if($advancedAccountingEnabled)<th>{{ __('accounting.gl_status') }}</th>@endif
                        <th class="text-end">{{ __('accounting.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $entry)
                    <tr>
                        <td class="fw-medium">{{ $entry->entry_number }}</td>
                        <td>{{ $entry->entry_date->format('d M Y') }}</td>
                        <td><span class="badge bg-light text-dark">{{ $entry->category->name }}</span></td>
                        <td>{{ Str::limit($entry->description, 40) }}</td>
                        <td>{{ $entry->payment_method?->translatedLabel() ?? '-' }}</td>
                        <td class="text-end fw-medium">GH₵ {{ number_format($entry->amount, 2) }}</td>
                        <td>{{ $entry->recordedByUser->name ?? '-' }}</td>
                        <td>
                            @if($entry->is_approved)
                                <span class="badge bg-success">{{ __('accounting.approved') }}</span>
                            @else
                                <span class="badge bg-warning">{{ __('accounting.pending') }}</span>
                            @endif
                        </td>
                        @if($advancedAccountingEnabled)
                        <td>
                            <span class="badge bg-{{ match($entry->accounting_status) { 'posted' => 'success', 'failed' => 'danger', 'reversed' => 'secondary', default => 'warning' } }}">{{ ucfirst($entry->accounting_status ?? 'pending') }}</span>
                            @if($entry->journalEntry)
                                <a class="ms-1" href="{{ route('admin.accounting.journals.show', $entry->journalEntry) }}">{{ $entry->journalEntry->journal_number }}</a>
                            @endif
                            @if($entry->accounting_error)<div class="small text-danger mt-1" title="{{ $entry->accounting_error }}">{{ Str::limit($entry->accounting_error, 55) }}</div>@endif
                        </td>
                        @endif
                        <td class="text-end">
                            <div class="dropdown">
                                <button aria-label="{{ __('accounting.actions') }}" title="{{ __('accounting.actions') }}" type="button" class="btn btn-sm btn-white border dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    @if(!$entry->is_approved)
                                    @can('accounts.entries.approve')
                                    <li>
                                        <form method="POST" action="{{ route('admin.accounts.entries.approve', $entry) }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item">
                                                <i class="ti ti-check me-1"></i>{{ __('accounting.approve') }}
                                            </button>
                                        </form>
                                    </li>
                                    @endcan
                                    @can('accounts.entries.create')
                                    <li>
                                        <x-confirm-form :action="route('admin.accounts.entries.destroy', $entry)" method="DELETE"
                                            button-label="Delete" button-class="dropdown-item text-danger" icon="ti-trash"
                                            :confirm-title="__('accounting.delete_entry_confirm')" confirm-text="This accounting entry will be permanently deleted." :confirm-button="__('common.yes')" />
                                    </li>
                                    @endcan
                                    @endif
                                    @if($advancedAccountingEnabled && $entry->is_approved && !in_array($entry->accounting_status, ['posted', 'reversed'], true))
                                    @can('accounting.basic.batch.view')
                                    <li><a class="dropdown-item" href="{{ route('admin.accounting.basic-bridge.index', ['entry_id' => $entry->id, 'preview' => 1]) }}"><i class="ti ti-eye me-1"></i>{{ __('accounting.preview_posting') }}</a></li>
                                    @endcan
                                    @can('accounting.basic.post')
                                    <li><form method="POST" action="{{ route('admin.accounts.entries.post-to-gl', $entry) }}">@csrf<button class="dropdown-item text-primary" type="submit" onclick="return confirm('{{ __('accounting.confirm_post_to_gl') }}')"><i class="ti ti-send me-1"></i>{{ __('accounting.post_to_gl') }}</button></form></li>
                                    @endcan
                                    @endif
                                    @if($advancedAccountingEnabled && $entry->accounting_status === 'posted')
                                    @can('accounting.basic.reverse')
                                    <li><form class="px-3 py-2" method="POST" action="{{ route('admin.accounts.entries.reverse-gl', $entry) }}">@csrf<input class="form-control form-control-sm mb-2" name="reason" required placeholder="{{ __('accounting.reason_for_reversal') }}"><button class="btn btn-sm btn-outline-danger w-100" type="submit" onclick="return confirm('{{ __('accounting.confirm_reverse_gl') }}')">{{ __('accounting.reverse_gl') }}</button></form></li>
                                    @endcan
                                    @endif
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $advancedAccountingEnabled ? 10 : 9 }}" class="text-center text-muted py-4">
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
