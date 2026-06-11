@extends('layouts.app')
@section('title', __('reports.accounting.balance_sheet'))

@php $money = fn ($n) => number_format((float) $n, 2); @endphp

@section('content')
<x-page-header title="{{ __('reports.accounting.balance_sheet') }}" icon="ti-scale" :description="__('reports.accounting_labels.as_of') . ' ' . \Illuminate\Support\Carbon::parse($report['as_of'])->format('d M Y')">
    <x-slot:actions>
        <a href="{{ route('admin.accounting.reports.profit-loss') }}" class="btn btn-outline-secondary btn-sm">{{ __('reports.accounting.profit_loss') }}</a>
    </x-slot:actions>
</x-page-header>

<div class="card mb-3"><div class="card-body py-2">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label small mb-1">{{ __('reports.accounting_labels.as_of') }}</label><input type="date" name="date_to" class="form-control" value="{{ $report['as_of'] }}"></div>
        <div class="col-md-1"><button class="btn btn-outline-primary w-100"><i class="ti ti-search"></i></button></div>
    </form>
</div></div>

@if(! $report['is_balanced'])
<div class="alert alert-danger"><i class="ti ti-alert-triangle me-1"></i><strong>{{ __('reports.empty.out_of_balance') }}</strong> {{ __('reports.accounting_labels.assets') }} ({{ $money($report['total_assets']) }}) ≠ {{ __('reports.accounting_labels.liabilities_equity') }} ({{ $money($report['total_liabilities_equity']) }}). {{ __('reports.accounting_labels.difference') }} {{ $money($report['difference']) }}.</div>
@endif

@if(!empty($reconciliation))
<div class="card mb-3"><div class="card-header"><h6 class="mb-0"><i class="ti ti-checks me-1"></i>{{ __('reports.accounting_labels.gl_reconciliation') }}</h6></div>
<div class="table-responsive"><table class="table table-sm mb-0">
    <thead class="table-light"><tr><th>{{ __('reports.accounting_labels.control') }}</th><th class="text-end">{{ __('reports.accounting_labels.gl_balance') }}</th><th class="text-end">{{ __('reports.accounting_labels.operational') }}</th><th class="text-end">{{ __('reports.accounting_labels.difference') }}</th><th>{{ __('reports.status') }}</th></tr></thead>
    <tbody>@foreach($reconciliation as $c)
        <tr><td>{{ $c['label'] }}</td><td class="text-end">{{ $money($c['gl_balance']) }}</td><td class="text-end">{{ $money($c['operational_balance']) }}</td><td class="text-end">{{ $money($c['difference']) }}</td>
        <td>@if($c['matched'])<span class="badge bg-success-subtle text-success">{{ __('reports.statuses.matched') }}</span>@else<span class="badge bg-warning-subtle text-warning">{{ __('reports.statuses.mismatch') }}</span>@endif</td></tr>
    @endforeach</tbody>
</table></div></div>
@endif

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card"><div class="card-header"><h6 class="mb-0">{{ __('reports.accounting_labels.assets') }}</h6></div><div class="card-body">
            <div class="table-responsive"><table class="table table-sm mb-0"><tbody>
                @foreach($report['groups']['assets']['rows'] as $r)<tr><td>{{ $r['code'] }} — {{ $r['name'] }}</td><td class="text-end">{{ $money($r['amount']) }}</td></tr>@endforeach
                <tr class="table-primary"><th>{{ __('reports.accounting_labels.total_assets') }}</th><th class="text-end">{{ $money($report['total_assets']) }}</th></tr>
            </tbody></table></div>
        </div></div>
    </div>
    <div class="col-lg-6">
        <div class="card mb-3"><div class="card-header"><h6 class="mb-0">{{ __('reports.accounting_labels.liabilities') }}</h6></div><div class="card-body">
            <div class="table-responsive"><table class="table table-sm mb-0"><tbody>
                @foreach($report['groups']['liabilities']['rows'] as $r)<tr><td>{{ $r['code'] }} — {{ $r['name'] }}</td><td class="text-end">{{ $money($r['amount']) }}</td></tr>@endforeach
                <tr><th>{{ __('reports.accounting_labels.total_liabilities') }}</th><th class="text-end">{{ $money($report['groups']['liabilities']['total']) }}</th></tr>
            </tbody></table></div>
        </div></div>
        <div class="card"><div class="card-header"><h6 class="mb-0">{{ __('reports.accounting_labels.equity') }}</h6></div><div class="card-body">
            <div class="table-responsive"><table class="table table-sm mb-0"><tbody>
                @foreach($report['groups']['equity']['rows'] as $r)<tr><td>{{ $r['code'] }} — {{ $r['name'] }}</td><td class="text-end">{{ $money($r['amount']) }}</td></tr>@endforeach
                <tr><th>{{ __('reports.accounting_labels.total_equity') }}</th><th class="text-end">{{ $money($report['groups']['equity']['total']) }}</th></tr>
                <tr class="table-primary"><th>{{ __('reports.accounting_labels.liabilities_equity') }}</th><th class="text-end">{{ $money($report['total_liabilities_equity']) }}</th></tr>
            </tbody></table></div>
        </div></div>
    </div>
</div>
@endsection
