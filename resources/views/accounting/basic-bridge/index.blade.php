@extends('layouts.app')
@section('title', __('accounting.basic_posting_bridge'))

@section('content')
<x-page-header :title="__('accounting.basic_posting_bridge')" :description="__('accounting.basic_posting_bridge_description')" icon="ti-arrows-transfer-up" />
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

<div class="card mb-3"><div class="card-body">
    <form method="GET" class="row g-3 align-items-end">
        <input type="hidden" name="preview" value="1">
        <div class="col-md-2"><label class="form-label">{{ __('accounting.from') }}</label><input type="date" class="form-control" name="from" value="{{ request('from') }}"></div>
        <div class="col-md-2"><label class="form-label">{{ __('accounting.to') }}</label><input type="date" class="form-control" name="to" value="{{ request('to') }}"></div>
        <div class="col-md-2"><label class="form-label">{{ __('accounting.entry_type') }}</label><select class="form-select" name="entry_type"><option value="">{{ __('common.all') }}</option><option value="income" @selected(request('entry_type') === 'income')>Income</option><option value="expense" @selected(request('entry_type') === 'expense')>Expense</option></select></div>
        <div class="col-md-3"><label class="form-label">{{ __('accounting.category') }}</label><select class="form-select" name="category_id"><option value="">{{ __('common.all') }}</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label">{{ __('accounting.source_id') }}</label><input type="number" class="form-control" name="entry_id" value="{{ request('entry_id') }}"></div>
        <div class="col-md-1"><button class="btn btn-primary w-100" type="submit"><i class="ti ti-eye"></i></button></div>
    </form>
</div></div>

@if($preview)
<form method="POST" action="{{ route('admin.accounting.basic-bridge.execute') }}">
@csrf
<div class="alert alert-info">{{ __('accounting.batch_preview_summary', ['eligible' => $preview['eligible'], 'blocked' => $preview['ineligible']]) }}</div>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
    <thead><tr><th></th><th>{{ __('accounting.entry_number') }}</th><th>{{ __('accounting.date') }}</th><th>{{ __('accounting.entry_type') }}</th><th>{{ __('accounting.category') }}</th><th class="text-end">{{ __('accounting.amount') }}</th><th>{{ __('accounting.status') }}</th><th>{{ __('accounting.resolution_note') }}</th></tr></thead>
    <tbody>
    @forelse($preview['rows'] as $row)
        <tr>
            <td>@if($row['result']['eligible'])<input type="checkbox" name="entry_ids[]" value="{{ $row['entry']->id }}" checked>@endif</td>
            <td>{{ $row['entry']->entry_number }}</td>
            <td>{{ $row['entry']->entry_date->format('d M Y') }}</td>
            <td>{{ ucfirst($row['entry']->type->value) }}</td>
            <td>{{ $row['entry']->category?->name }}</td>
            <td class="text-end">GHS {{ number_format($row['entry']->amount, 2) }}</td>
            <td><span class="badge bg-{{ $row['result']['eligible'] ? 'success' : 'danger' }}">{{ $row['result']['eligible'] ? __('accounting.eligible') : __('accounting.blocked') }}</span></td>
            <td>{{ implode(' ', $row['result']['reasons']) }}</td>
        </tr>
    @empty
        <tr><td colspan="8" class="text-center text-muted py-4">{{ __('accounting.no_entries_for_posting') }}</td></tr>
    @endforelse
    </tbody>
</table></div></div>
@can('accounting.basic.batch.execute')
<div class="form-check my-3"><input class="form-check-input" type="checkbox" name="confirmation" value="1" id="confirmation" required><label class="form-check-label" for="confirmation">{{ __('accounting.confirm_batch_posting') }}</label></div>
<button class="btn btn-danger" type="submit" @disabled($preview['eligible'] === 0)>{{ __('accounting.execute_posting') }}</button>
@endcan
</form>
@endif
@endsection
