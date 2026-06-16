@extends('layouts.app')
@section('title', 'Budget Commitments')

@php $money = fn ($n) => 'GHS '.number_format((float) $n, 2); @endphp

@section('content')
<x-page-header title="Budget Commitments" icon="ti-lock-dollar" description="Open encumbrances against approved budgets." />

<div class="card mb-3">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.accounting.commitments.store') }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-2"><label class="form-label small">Fiscal Year</label><select name="fiscal_year_id" class="form-select">@foreach($fiscalYears as $year)<option value="{{ $year->id }}">{{ $year->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label small">Department</label><select name="department_id" class="form-select"><option value="">Unassigned</option>@foreach($departments as $department)<option value="{{ $department->id }}">{{ $department->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label small">Account</label><select name="account_id" class="form-select">@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->code }} {{ $account->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label small">Amount</label><input name="amount" type="number" step="0.01" min="0.01" class="form-control" required></div>
            <div class="col-md-2"><label class="form-label small">Reference</label><input name="source_reference" class="form-control"></div>
            <div class="col-md-1"><button class="btn btn-primary w-100">Add</button></div>
            <div class="col-12"><label class="form-check"><input type="checkbox" name="over_budget_acknowledged" value="1" class="form-check-input"> <span class="form-check-label">Acknowledge over-budget warning if applicable</span></label></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Reference</th><th>Status</th><th class="text-end">Original</th><th class="text-end">Remaining</th><th>Warning</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                @forelse($commitments as $commitment)
                    <tr>
                        <td>{{ $commitment->source_reference ?: 'Manual #'.$commitment->id }}</td>
                        <td><span class="badge bg-secondary">{{ $commitment->status }}</span></td>
                        <td class="text-end">{{ $money($commitment->original_amount) }}</td>
                        <td class="text-end fw-semibold">{{ $money($commitment->remaining_amount) }}</td>
                        <td>{!! $commitment->is_over_budget ? '<span class="badge bg-warning text-dark">Over budget</span>' : '<span class="text-muted">Within budget</span>' !!}</td>
                        <td class="text-end">
                            @if(in_array($commitment->status, [\App\Models\BudgetCommitment::STATUS_ACTIVE, \App\Models\BudgetCommitment::STATUS_PARTIALLY_RELEASED], true))
                                <form method="POST" action="{{ route('admin.accounting.commitments.release', $commitment) }}" class="d-inline-flex gap-1">
                                    @csrf
                                    <input name="amount" type="number" step="0.01" min="0.01" max="{{ $commitment->remaining_amount }}" class="form-control form-control-sm" style="width: 120px" placeholder="Amount">
                                    <button class="btn btn-sm btn-outline-success">Release</button>
                                </form>
                                <form method="POST" action="{{ route('admin.accounting.commitments.cancel', $commitment) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-danger">Cancel</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No commitments yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
