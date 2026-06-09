@extends('layouts.app')
@section('title', $title)

@section('content')
<x-page-header :title="$title" icon="ti-layout-dashboard"
    :description="$department?->name ? ($department->name . ' · ' . now()->format('D, d M Y')) : now()->format('D, d M Y')">
    <x-slot:actions>
        @if(auth()->user()?->hasAnyRole(['Super Admin', 'Admin']))
        <form method="GET" class="d-inline-block">
            <select name="as" class="form-select form-select-sm d-inline-block" style="width:auto" onchange="this.form.submit()" aria-label="Preview dashboard">
                @foreach([
                    'management' => 'Management',
                    'consultation' => 'Consultation / OPD',
                    'emergency' => 'Emergency / Casualty',
                    'admission' => 'Admission / Ward',
                    'pharmacy' => 'Pharmacy',
                    'investigation' => 'Investigations',
                    'theatre' => 'Theatre & Procedures',
                    'billing' => 'Billing / Cashier',
                    'claims' => 'Insurance / Claims',
                    'stock' => 'Stock & Store',
                    'blood_bank' => 'Blood Bank',
                    'accounting' => 'Accounting',
                    'hr' => 'HR / Payroll',
                    'reception' => 'Reception / Front Desk',
                ] as $value => $label)
                    <option value="{{ $value }}" @selected(($key ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
        @endif
        <a href="{{ url()->current() }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-refresh me-1"></i>Refresh</a>
    </x-slot:actions>
</x-page-header>

@if(!empty($is_preview))
<div class="alert alert-info d-flex align-items-center gap-2 py-2"><i class="ti ti-eye"></i>
    <span>Previewing the <strong>{{ $title }}</strong>. Your own dashboard is <strong>{{ \Illuminate\Support\Str::headline($resolved_key) }}</strong>.</span>
</div>
@endif

{{-- KPIs --}}
@if(!empty($kpis))
<div class="row g-3 mb-3">
    @foreach($kpis as $kpi)
    <div class="col-xl-3 col-md-4 col-sm-6">
        <x-stat-card :title="$kpi['title']" :value="$kpi['value']" :icon="$kpi['icon']"
            :variant="$kpi['variant']" :route="$kpi['route'] ?? null" :format="$kpi['format'] ?? null" />
    </div>
    @endforeach
</div>
@endif

{{-- Critical alerts --}}
@if(!empty($alerts))
<div class="row g-2 mb-3">
    @foreach($alerts as $alert)
    <div class="col-md-6 col-xl-4">
        <a href="{{ $alert['route'] ?? 'javascript:void(0)' }}" class="text-decoration-none">
            <div class="alert alert-{{ $alert['variant'] }} d-flex align-items-center justify-content-between mb-0 py-2">
                <span><i class="ti {{ $alert['icon'] }} me-1"></i>{{ $alert['title'] }}</span>
                <span class="badge bg-{{ $alert['variant'] }}">{{ $alert['count'] }}</span>
            </div>
        </a>
    </div>
    @endforeach
</div>
@endif

<div class="row g-3">
    {{-- Work queues --}}
    <div class="col-lg-8">
        @forelse(array_filter($queues ?? []) as $queue)
            @include('admin.dashboards.partials.work-queue', ['queue' => $queue])
        @empty
            @if(empty($kpis))
                <x-empty-state icon="ti-layout-dashboard" title="Nothing to show yet"
                    message="There is no department activity to display for your role right now." />
            @endif
        @endforelse
    </div>

    {{-- Quick actions + reports --}}
    <div class="col-lg-4">
        @if(!empty($quick_actions))
        <div class="card mb-3">
            <div class="card-header"><h6 class="fw-bold mb-0"><i class="ti ti-bolt me-1"></i>Quick Actions</h6></div>
            <div class="card-body d-grid gap-2">
                @foreach($quick_actions as $action)
                <a href="{{ $action['route'] }}" class="btn btn-{{ $action['variant'] === 'primary' ? 'primary' : 'outline-secondary' }} text-start">
                    <i class="ti {{ $action['icon'] }} me-1"></i>{{ $action['label'] }}
                </a>
                @endforeach
            </div>
        </div>
        @endif

        @if(!empty($reports))
        <div class="card">
            <div class="card-header"><h6 class="fw-bold mb-0"><i class="ti ti-report me-1"></i>Reports</h6></div>
            <div class="list-group list-group-flush">
                @foreach($reports as $report)
                <a href="{{ $report['route'] }}" class="list-group-item list-group-item-action d-flex align-items-center">
                    <i class="ti {{ $report['icon'] }} me-2 text-muted"></i>{{ $report['label'] }}
                    <i class="ti ti-chevron-right ms-auto text-muted"></i>
                </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
