@extends('layouts.app')
@section('title', __('accounting.ap_aging'))

@section('content')
<x-page-header :title="__('accounting.accounts_payable_aging')" icon="ti-clock-dollar" :description="__('accounting.as_of') . ' ' . \Illuminate\Support\Carbon::parse($report['as_of'])->format('d M Y')">
    <x-slot:actions>
        <a href="{{ route('admin.accounts-payable.payables') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-file-dollar me-1"></i>{{ __('accounting.payables') }}</a>
    </x-slot:actions>
</x-page-header>

<div class="card mb-3"><div class="card-body py-2">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label small mb-1">{{ __('accounting.as_of') }}</label><input type="date" name="as_of" class="form-control" value="{{ $report['as_of'] }}"></div>
        <div class="col-md-4"><label class="form-label small mb-1">{{ __('accounting.supplier') }}</label>
            <select name="supplier_id" class="form-select"><option value="">{{ __('accounting.all_suppliers') }}</option>
                @foreach($suppliers as $s)<option value="{{ $s->id }}" @selected((string)request('supplier_id')===(string)$s->id)>{{ $s->name }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-1"><button class="btn btn-outline-primary w-100"><i class="ti ti-search"></i></button></div>
    </form>
</div></div>

<div class="row g-2 mb-3">
    @foreach($report['buckets'] as $b)
    <div class="col"><div class="card h-100"><div class="card-body py-2 text-center">
        <small class="text-muted d-block">{{ $b['label'] }}</small>
        <strong class="fs-5">{{ number_format($b['total'], 2) }}</strong>
        <small class="text-muted d-block">{{ __('accounting.item_count', ['count' => $b['count']]) }}</small>
    </div></div></div>
    @endforeach
    <div class="col"><div class="card h-100 border-primary"><div class="card-body py-2 text-center">
        <small class="text-muted d-block">{{ __('accounting.total_payable') }}</small>
        <strong class="fs-5 text-primary">{{ number_format($report['grand_total'], 2) }}</strong>
        <small class="text-muted d-block">{{ __('accounting.item_count', ['count' => $report['grand_count']]) }}</small>
    </div></div></div>
</div>

<div class="card"><div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr>
            <th>{{ __('accounting.supplier') }}</th><th>{{ __('accounting.reference') }}</th><th class="text-end">{{ __('accounting.original') }}</th><th class="text-end">{{ __('accounting.paid') }}</th>
            <th class="text-end">{{ __('accounting.returns_credits') }}</th><th class="text-end">{{ __('accounting.balance') }}</th><th>{{ __('accounting.due') }}</th><th class="text-end">{{ __('accounting.age_days') }}</th><th>{{ __('accounting.bucket') }}</th>
        </tr></thead>
        <tbody>
        @forelse($report['rows'] as $r)
            <tr @class(['table-warning' => $r['bucket'] === 'b120_plus'])>
                <td><a href="{{ route('admin.accounts-payable.statement', $r['supplier_id']) }}" class="fw-medium text-primary">{{ $r['supplier_name'] }}</a></td>
                <td><small>{{ $r['grn_number'] ?? '—' }}@if($r['po_number']) · {{ $r['po_number'] }}@endif</small></td>
                <td class="text-end">{{ number_format($r['original_amount'], 2) }}</td>
                <td class="text-end">{{ number_format($r['paid_amount'], 2) }}</td>
                <td class="text-end">{{ number_format($r['adjustment_amount'], 2) }}</td>
                <td class="text-end fw-semibold">{{ number_format($r['balance'], 2) }}</td>
                <td><small>{{ $r['due_date'] ?? '—' }}</small></td>
                <td class="text-end">{{ $r['days_overdue'] }}</td>
                <td><small>{{ $report['buckets'][$r['bucket']]['label'] }}</small></td>
            </tr>
        @empty
            <tr><td colspan="9"><x-empty-state icon="ti-clock-dollar" :title="__('accounting.no_outstanding_payables')" :message="__('accounting.nothing_in_ap_aging')" /></td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>
@endsection
