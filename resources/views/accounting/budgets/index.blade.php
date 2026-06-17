@extends('layouts.app')
@section('title', __('accounting.budgets'))

@php $money = fn ($n) => 'GHS '.number_format((float) $n, 2); @endphp

@section('content')
<x-page-header :title="__('accounting.budgets')" icon="ti-chart-pie" description="Approved budgets, actuals, commitments and available balances." />

<div class="row g-3 mb-3">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><h5 class="card-title mb-0">Create Draft Budget</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.accounting.budgets.store') }}" class="row g-2">
                    @csrf
                    <div class="col-12">
                        <label class="form-label small">Fiscal Year</label>
                        <select name="fiscal_year_id" class="form-select" required>
                            @foreach($fiscalYears as $year)
                                <option value="{{ $year->id }}" @selected(request('fiscal_year_id') == $year->id)>{{ $year->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12"><label class="form-label small">Name</label><input name="name" class="form-control" required></div>
                    <div class="col-12">
                        <label class="form-label small">Enforcement</label>
                        <select name="enforcement_mode" class="form-select">
                            <option value="warning">{{ __('accounting.warning_with_acknowledgement') }}</option>
                            <option value="blocking">{{ __('accounting.block_procurement_over_budget') }}</option>
                        </select>
                    </div>
                    <div class="col-12"><label class="form-label small">Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                    <div class="col-12"><button class="btn btn-primary w-100">Create Budget</button></div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="row g-2">
            @foreach(['adjusted_budget' => 'Adjusted Budget', 'actual' => 'Actual', 'open_commitments' => 'Open Commitments', 'available' => 'Available'] as $key => $label)
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body text-center py-3">
                            <small class="text-muted d-block">{{ $label }}</small>
                            <strong>{{ $money($summary['totals'][$key] ?? 0) }}</strong>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Budget</th><th>Fiscal Year</th><th>Status</th><th>Lines</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                @forelse($budgets as $budget)
                    <tr>
                        <td>
                            <strong>{{ $budget->name }}</strong>
                            <small class="d-block text-muted">{{ ucfirst($budget->enforcement_mode) }} enforcement</small>
                        </td>
                        <td>{{ $budget->fiscalYear?->name }}</td>
                        <td><span class="badge bg-secondary">{{ $budget->status }}</span></td>
                        <td>{{ $budget->lines->count() }}</td>
                        <td class="text-end">
                            @if($budget->status === \App\Models\Budget::STATUS_DRAFT)
                                <form method="POST" action="{{ route('admin.accounting.budgets.lines.store', $budget) }}" class="d-inline-flex gap-1">
                                    @csrf
                                    <select name="department_id" class="form-select form-select-sm" style="width: 150px"><option value="">Unassigned</option>@foreach($departments as $department)<option value="{{ $department->id }}">{{ $department->name }}</option>@endforeach</select>
                                    <select name="account_id" class="form-select form-select-sm" style="width: 170px">@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->code }} {{ $account->name }}</option>@endforeach</select>
                                    <input name="amount" type="number" step="0.01" min="0" class="form-control form-control-sm" style="width: 120px" placeholder="Amount">
                                    <button class="btn btn-sm btn-outline-primary">Add Line</button>
                                </form>
                                <form method="POST" action="{{ route('admin.accounting.budgets.submit', $budget) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-secondary">Submit</button></form>
                            @elseif($budget->status === \App\Models\Budget::STATUS_SUBMITTED)
                                <form method="POST" action="{{ route('admin.accounting.budgets.approve', $budget) }}" class="d-inline">@csrf<button class="btn btn-sm btn-success">Approve</button></form>
                            @else
                                <span class="text-muted small">Approved lines locked</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No budgets yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="card-title mb-0">Budget Availability</h5></div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light"><tr><th>Department</th><th>Account</th><th class="text-end">Budget</th><th class="text-end">Actual</th><th class="text-end">Commitments</th><th class="text-end">Available</th></tr></thead>
            <tbody>
                @forelse($summary['rows'] as $row)
                    <tr>
                        <td>{{ $row['department'] }}</td>
                        <td>{{ $row['account'] }}</td>
                        <td class="text-end">{{ $money($row['adjusted_budget']) }}</td>
                        <td class="text-end">{{ $money($row['actual']) }}</td>
                        <td class="text-end">{{ $money($row['open_commitments']) }}</td>
                        <td class="text-end fw-semibold">{{ $money($row['available']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Approve a budget to see availability.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
