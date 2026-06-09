@extends('layouts.app')
@section('title', 'Journal Entries')

@section('content')
<x-page-header title="Journal Entries" icon="ti-journal">
    <x-slot:actions>
        @can('accounting.journals.create')
            <a href="{{ route('admin.accounting.journals.create') }}" class="btn btn-primary"><i class="ti ti-plus me-1"></i>New Journal</a>
        @endcan
    </x-slot:actions>
</x-page-header>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small">Search</label>
                <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Journal, reference, description">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
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
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-outline-primary" type="submit"><i class="ti ti-search"></i></button>
                <a href="{{ route('admin.accounting.journals.index') }}" class="btn btn-outline-secondary"><i class="ti ti-x"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Journal</th>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Reference</th>
                    <th>Status</th>
                    <th class="text-end">Debit</th>
                    <th>Created By</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($entries as $entry)
                <tr>
                    <td class="fw-semibold">{{ $entry->journal_number }}</td>
                    <td>{{ $entry->entry_date?->format('d M Y') }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($entry->description, 55) }}</td>
                    <td>{{ $entry->reference_number ?? '-' }}</td>
                    <td><span class="badge bg-{{ $entry->status->color() }}">{{ $entry->status->label() }}</span></td>
                    <td class="text-end">GH₵ {{ number_format($entry->total_debit, 2) }}</td>
                    <td>{{ $entry->createdBy?->name ?? '-' }}</td>
                    <td class="text-end">
                        <a href="{{ route('admin.accounting.journals.show', $entry) }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-eye"></i></a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No journal entries found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3 d-flex justify-content-end">{{ $entries->links() }}</div>
@endsection
