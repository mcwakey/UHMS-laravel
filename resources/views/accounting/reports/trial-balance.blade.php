@extends('layouts.app')
@section('title', 'Trial Balance')

@section('content')
<x-page-header title="Trial Balance" icon="ti-scale" />

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">Fiscal Year</label>
                <select name="fiscal_year_id" class="form-select">
                    <option value="">All</option>
                    @foreach($fiscalYears as $year)
                        <option value="{{ $year->id }}" @selected(request('fiscal_year_id') == $year->id)>{{ $year->name }}</option>
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
                <label class="form-label small">Type</label>
                <select name="account_type" class="form-select">
                    <option value="">All</option>
                    @foreach($types as $type)
                        <option value="{{ $type->value }}" @selected(request('account_type') === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Department</label>
                <select name="department_id" class="form-select">
                    <option value="">All</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <button class="btn btn-outline-primary w-100" type="submit"><i class="ti ti-search"></i></button>
            </div>
        </form>
    </div>
</div>

@unless($report['is_balanced'])
    <div class="alert alert-warning"><i class="ti ti-alert-triangle me-1"></i>Trial balance totals do not match.</div>
@endunless

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Account Code</th>
                    <th>Account Name</th>
                    <th class="text-end">Debit</th>
                    <th class="text-end">Credit</th>
                    <th class="text-end">Balance</th>
                </tr>
            </thead>
            <tbody>
            @forelse($report['rows'] as $row)
                <tr>
                    <td class="fw-semibold">{{ $row['account']->code }}</td>
                    <td>{{ $row['account']->name }}</td>
                    <td class="text-end">GH₵ {{ number_format($row['debit'], 2) }}</td>
                    <td class="text-end">GH₵ {{ number_format($row['credit'], 2) }}</td>
                    <td class="text-end">GH₵ {{ number_format($row['balance'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No posted journal lines found.</td></tr>
            @endforelse
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <th colspan="2">Totals</th>
                    <th class="text-end">GH₵ {{ number_format($report['total_debit'], 2) }}</th>
                    <th class="text-end">GH₵ {{ number_format($report['total_credit'], 2) }}</th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
