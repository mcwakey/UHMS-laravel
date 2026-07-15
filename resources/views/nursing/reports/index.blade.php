@extends('layouts.app')
@section('title', __('nursing.reports.title'))
@section('content')
<x-page-header :title="__('nursing.reports.title')" :description="__('nursing.reports.subtitle', ['department' => $department->name])" icon="ti-report-analytics" />
<div class="row g-3 mb-4">@foreach(['active', 'waiting_for_triage', 'vitals_incomplete', 'nursing_action_required', 'completed_today', 'high_risk'] as $key)<div class="col-xl-2 col-md-4 col-6"><div class="card border h-100"><div class="card-body"><div class="small text-muted">{{ __('nursing.metrics.'.$key) }}</div><div class="fs-2 fw-bold">{{ $metrics[$key] }}</div></div></div></div>@endforeach</div>
<div class="card border shadow-sm"><div class="card-header"><h5 class="mb-0">{{ __('nursing.reports.daily_attendance') }}</h5></div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>{{ __('nursing.common.recorded_at') }}</th><th class="text-end">{{ __('nursing.common.visit') }}</th></tr></thead><tbody>@forelse($daily as $row)<tr><td>{{ \Illuminate\Support\Carbon::parse($row->day)->translatedFormat('D d M') }}</td><td class="text-end fw-bold">{{ $row->total }}</td></tr>@empty<tr><td colspan="2" class="text-center text-muted py-4">{{ __('nursing.queue.empty') }}</td></tr>@endforelse</tbody></table></div></div>
@endsection
