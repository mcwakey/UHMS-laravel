@extends('layouts.app')
@section('title', __('accounting.fiscal_years'))

@section('content')
<x-page-header :title="__('accounting.fiscal_years')" icon="ti-calendar-stats" />

@if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

@can('accounting.fiscal_years.manage')
<div class="card mb-3">
    <div class="card-header"><h5 class="card-title mb-0">{{ __('accounting.create_fiscal_year') }}</h5></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.accounting.fiscal-years.store') }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-4">
                <label class="form-label">{{ __('accounting.name') }}</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', 'FY ' . now()->year) }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('accounting.start') }}</label>
                <input type="date" name="start_date" class="form-control" value="{{ old('start_date', now()->startOfYear()->toDateString()) }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('accounting.end') }}</label>
                <input type="date" name="end_date" class="form-control" value="{{ old('end_date', now()->endOfYear()->toDateString()) }}" required>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100" type="submit"><i class="ti ti-plus me-1"></i>{{ __('common.create') }}</button>
            </div>
        </form>
    </div>
</div>
@endcan

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('accounting.name') }}</th>
                    <th>{{ __('accounting.start') }}</th>
                    <th>{{ __('accounting.end') }}</th>
                    <th>{{ __('accounting.periods') }}</th>
                    <th>{{ __('accounting.status') }}</th>
                    <th>{{ __('accounting.closed') }}</th>
                    <th class="text-end">{{ __('accounting.actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse($years as $year)
                <tr>
                    <td class="fw-semibold">{{ $year->name }}</td>
                    <td>{{ $year->start_date->format('d M Y') }}</td>
                    <td>{{ $year->end_date->format('d M Y') }}</td>
                    <td>{{ number_format($year->periods_count) }}</td>
                    <td><span class="badge bg-{{ $year->status->color() }}">{{ $year->status->translatedLabel() }}</span></td>
                    <td>{{ $year->closed_at?->format('d M Y H:i') ?? '-' }}</td>
                    <td class="text-end">
                        @if($year->status->value === 'open')
                            @can('accounting.fiscal_years.manage')
                                <x-confirm-form :action="route('admin.accounting.fiscal-years.close', $year)" method="PATCH" :button-label="__('accounting.closed')" button-class="btn btn-sm btn-outline-danger" icon="ti-lock" :confirm-title="__('accounting.close_fiscal_year')" :confirm-text="__('accounting.close_fiscal_year_text')" :confirm-button="__('accounting.closed')" />
                            @endcan
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">{{ __('accounting.no_fiscal_years_found') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3 d-flex justify-content-end">{{ $years->links() }}</div>
@endsection
