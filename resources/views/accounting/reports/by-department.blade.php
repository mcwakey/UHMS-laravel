@extends('layouts.app')
@section('title', $title)

@php $money = fn ($n) => number_format((float) $n, 2); @endphp

@section('content')
<x-page-header :title="$title" icon="ti-building-bank" description="{{ __('reports.accounting.description') }}" />

<div class="card mb-3"><div class="card-body py-2">
    <form method="GET" action="{{ route($route) }}" class="row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label small mb-1">{{ __('common.from') }}</label><input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}"></div>
        <div class="col-md-3"><label class="form-label small mb-1">{{ __('common.to') }}</label><input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}"></div>
        <div class="col-md-1"><button class="btn btn-outline-primary w-100"><i class="ti ti-search"></i></button></div>
    </form>
</div></div>

<div class="card mb-3"><div class="card-body py-2 d-flex justify-content-between"><span class="fw-semibold">{{ __('reports.grand_total') }}</span><span class="fw-bold fs-5">{{ $money($report['grand_total']) }}</span></div></div>

@forelse($report['departments'] as $dept)
<div class="card mb-2">
    <div class="card-header d-flex justify-content-between align-items-center py-2">
        <h6 class="mb-0">{{ $dept['department'] }}</h6>
        <strong>{{ $money($dept['total']) }}</strong>
    </div>
    <div class="table-responsive"><table class="table table-sm mb-0">
        <tbody>@foreach($dept['accounts'] as $a)<tr><td class="ps-4">{{ $a['code'] }} — {{ $a['name'] }}</td><td class="text-end">{{ $money($a['amount']) }}</td></tr>@endforeach</tbody>
    </table></div>
</div>
@empty
    <x-empty-state icon="ti-building-bank" title="{{ __('reports.no_data') }}" message="{{ __('reports.empty.no_department_data') }}" />
@endforelse
@endsection
