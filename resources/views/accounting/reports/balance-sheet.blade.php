@extends('layouts.app')
@section('title', 'Balance Sheet')

@php $money = fn ($n) => number_format((float) $n, 2); @endphp

@section('content')
<x-page-header title="Balance Sheet" icon="ti-scale" :description="'As of ' . \Illuminate\Support\Carbon::parse($report['as_of'])->format('d M Y')">
    <x-slot:actions>
        <a href="{{ route('admin.accounting.reports.profit-loss') }}" class="btn btn-outline-secondary btn-sm">Profit &amp; Loss</a>
    </x-slot:actions>
</x-page-header>

<div class="card mb-3"><div class="card-body py-2">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label small mb-1">As of</label><input type="date" name="date_to" class="form-control" value="{{ $report['as_of'] }}"></div>
        <div class="col-md-1"><button class="btn btn-outline-primary w-100"><i class="ti ti-search"></i></button></div>
    </form>
</div></div>

@if(! $report['is_balanced'])
<div class="alert alert-danger"><i class="ti ti-alert-triangle me-1"></i><strong>Out of balance.</strong> Assets ({{ $money($report['total_assets']) }}) ≠ Liabilities + Equity ({{ $money($report['total_liabilities_equity']) }}). Difference {{ $money($report['difference']) }}.</div>
@endif

@if(!empty($reconciliation))
<div class="card mb-3"><div class="card-header"><h6 class="mb-0"><i class="ti ti-checks me-1"></i>GL vs Operational Reconciliation</h6></div>
<div class="table-responsive"><table class="table table-sm mb-0">
    <thead class="table-light"><tr><th>Control</th><th class="text-end">GL Balance</th><th class="text-end">Operational</th><th class="text-end">Difference</th><th>Status</th></tr></thead>
    <tbody>@foreach($reconciliation as $c)
        <tr><td>{{ $c['label'] }}</td><td class="text-end">{{ $money($c['gl_balance']) }}</td><td class="text-end">{{ $money($c['operational_balance']) }}</td><td class="text-end">{{ $money($c['difference']) }}</td>
        <td>@if($c['matched'])<span class="badge bg-success-subtle text-success">Matched</span>@else<span class="badge bg-warning-subtle text-warning">Mismatch</span>@endif</td></tr>
    @endforeach</tbody>
</table></div></div>
@endif

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card"><div class="card-header"><h6 class="mb-0">Assets</h6></div><div class="card-body">
            <div class="table-responsive"><table class="table table-sm mb-0"><tbody>
                @foreach($report['groups']['assets']['rows'] as $r)<tr><td>{{ $r['code'] }} — {{ $r['name'] }}</td><td class="text-end">{{ $money($r['amount']) }}</td></tr>@endforeach
                <tr class="table-primary"><th>Total Assets</th><th class="text-end">{{ $money($report['total_assets']) }}</th></tr>
            </tbody></table></div>
        </div></div>
    </div>
    <div class="col-lg-6">
        <div class="card mb-3"><div class="card-header"><h6 class="mb-0">Liabilities</h6></div><div class="card-body">
            <div class="table-responsive"><table class="table table-sm mb-0"><tbody>
                @foreach($report['groups']['liabilities']['rows'] as $r)<tr><td>{{ $r['code'] }} — {{ $r['name'] }}</td><td class="text-end">{{ $money($r['amount']) }}</td></tr>@endforeach
                <tr><th>Total Liabilities</th><th class="text-end">{{ $money($report['groups']['liabilities']['total']) }}</th></tr>
            </tbody></table></div>
        </div></div>
        <div class="card"><div class="card-header"><h6 class="mb-0">Equity</h6></div><div class="card-body">
            <div class="table-responsive"><table class="table table-sm mb-0"><tbody>
                @foreach($report['groups']['equity']['rows'] as $r)<tr><td>{{ $r['code'] }} — {{ $r['name'] }}</td><td class="text-end">{{ $money($r['amount']) }}</td></tr>@endforeach
                <tr><th>Total Equity</th><th class="text-end">{{ $money($report['groups']['equity']['total']) }}</th></tr>
                <tr class="table-primary"><th>Liabilities + Equity</th><th class="text-end">{{ $money($report['total_liabilities_equity']) }}</th></tr>
            </tbody></table></div>
        </div></div>
    </div>
</div>
@endsection
