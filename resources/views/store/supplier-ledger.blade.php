@extends('layouts.app')
@section('title', 'Supplier Ledger — ' . $supplier->name)

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0"><i class="ti ti-notebook me-2"></i>Supplier Ledger — {{ $supplier->name }}</h4>
        <small class="text-muted">{{ $supplier->contact_person ? $supplier->contact_person . ' — ' : '' }}{{ $supplier->phone ?? '' }}</small>
    </div>
    <div>
        <a href="{{ route('admin.store.suppliers.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="ti ti-arrow-left me-1"></i>Back to Suppliers
        </a>
        @can('store.purchase.create')
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addLedgerEntryModal">
            <i class="ti ti-plus me-1"></i>Manual Entry
        </button>
        @endcan
    </div>
</div>

@if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
@if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

<div class="row g-2 mb-3">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-3">
                <small class="text-muted text-uppercase">Outstanding Balance</small>
                <h3 class="fw-bold mb-0 {{ $balance > 0 ? 'text-danger' : 'text-success' }}">
                    {{ number_format($balance, 2) }}
                </h3>
                <small class="text-muted">{{ $balance > 0 ? 'Facility owes supplier' : ($balance < 0 ? 'Supplier owes facility' : 'Settled') }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <form method="GET" class="card border-0 shadow-sm h-100">
            <div class="card-body py-3 row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-1">Search</label>
                    <input name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Description">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">From</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">To</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">{{ __('common.type') }}</label>
                    <select name="entry_type" class="form-select form-select-sm">
                        <option value="">{{ __('store.all_types') }}</option>
                        @foreach($types as $t)
                            <option value="{{ $t }}" @selected(request('entry_type') === $t)>{{ str_replace('_', ' ', $t) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">{{ __('store.debit_credit') }}</label>
                    <select name="debit_credit" class="form-select form-select-sm">
                        <option value="">{{ __('store.both') }}</option>
                        <option value="debit" @selected(request('debit_credit') === 'debit')>{{ __('store.debit') }}</option>
                        <option value="credit" @selected(request('debit_credit') === 'credit')>{{ __('store.credit') }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">{{ __('stock.source') }}</label>
                    <select name="source_type" class="form-select form-select-sm">
                        <option value="">{{ __('store.any') }}</option>
                        @foreach($sourceTypes as $sourceType)
                            <option value="{{ $sourceType }}" @selected(request('source_type') === $sourceType)>{{ class_basename($sourceType) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-primary btn-sm" type="submit"><i class="ti ti-filter me-1"></i>{{ __('common.filter') }}</button>
                </div>
                <div class="col-md-2 d-grid">
                    <a href="{{ route('admin.store.suppliers.ledger', $supplier) }}" class="btn btn-outline-secondary btn-sm">{{ __('common.reset') }}</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('common.date') }}</th>
                        <th>{{ __('common.type') }}</th>
                        <th>{{ __('common.description') }}</th>
                        <th class="text-end">{{ __('store.debit') }}</th>
                        <th class="text-end">{{ __('store.credit') }}</th>
                        <th class="text-end">{{ __('common.balance') }}</th>
                        <th>{{ __('stock.source') }}</th>
                        <th>{{ __('stock.by') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($entries as $entry)
                    <tr>
                        <td>{{ $entry->entry_date->format('Y-m-d') }}</td>
                        <td><span class="badge bg-light text-dark">{{ str_replace('_', ' ', $entry->entry_type) }}</span></td>
                        <td>{{ $entry->description }}</td>
                        <td class="text-end text-danger">{{ $entry->debit > 0 ? number_format($entry->debit, 2) : '' }}</td>
                        <td class="text-end text-success">{{ $entry->credit > 0 ? number_format($entry->credit, 2) : '' }}</td>
                        <td class="text-end fw-medium">{{ number_format((float) $entry->balance_after, 2) }}</td>
                        <td>
                            @if($url = $entry->sourceUrl())
                                <a href="{{ $url }}" class="btn btn-sm btn-outline-primary">{{ $entry->sourceLabel() }}</a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="small text-muted">{{ optional($entry->creator)->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-empty-state message="No ledger entries." /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if(method_exists($entries, 'links'))
    <div class="card-footer">{{ $entries->links() }}</div>
    @endif
</div>

@can('store.purchase.create')
{{-- Record Entry Modal --}}
<div class="modal fade" id="addLedgerEntryModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('admin.store.suppliers.ledger.store', $supplier) }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Record Manual Supplier Ledger Entry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label small">Type *</label>
                    <select name="entry_type" class="form-select form-select-sm" required>
                        @foreach($manualTypes as $t)
                            <option value="{{ $t }}">{{ str_replace('_', ' ', $t) }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">Goods received and supplier returns are created from their source workflows only.</small>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Date</label>
                    <input type="date" name="entry_date" value="{{ now()->toDateString() }}" class="form-control form-control-sm">
                </div>
                <div class="mb-2">
                    <label class="form-label small">Amount *</label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-sm" required>
                    <small class="text-muted">Payment/Credit Note reduces balance; Debit Note increases balance.</small>
                </div>
                <div class="mt-2">
                    <label class="form-label small">Description *</label>
                    <textarea name="description" rows="2" class="form-control form-control-sm" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm">Save</button>
            </div>
        </form>
    </div>
</div>
@endcan
@endsection
