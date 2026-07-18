@extends('layouts.app')
@section('title', __('medication_administration.emergency_medication_board'))

@section('content')
<div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
    <div>
        <h4 class="fw-bold mb-1">{{ __('medication_administration.emergency_medication_board') }}</h4>
        <p class="text-muted mb-0">{{ __('medication_administration.emergency_board_description') }}</p>
    </div>
    <a href="{{ $workspaceRoutes->route('admin.medication-administration.reports') }}" class="btn btn-outline-primary btn-sm">{{ __('medication_administration.reports') }}</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-lg-2"><div class="card bg-danger text-white border-0"><div class="card-body py-3"><div class="small opacity-75">{{ __('medication_administration.stat_due') }}</div><div class="h4 mb-0">{{ $stat_due->count() }}</div></div></div></div>
    <div class="col-6 col-lg-2"><div class="card bg-info text-white border-0"><div class="card-body py-3"><div class="small opacity-75">{{ __('medication_administration.due_now') }}</div><div class="h4 mb-0">{{ $counts['due_now'] ?? 0 }}</div></div></div></div>
    <div class="col-6 col-lg-2"><div class="card bg-danger-subtle border-0"><div class="card-body py-3"><div class="small text-danger">{{ __('medication_administration.overdue') }}</div><div class="h4 mb-0 text-danger">{{ $counts['overdue'] ?? 0 }}</div></div></div></div>
    <div class="col-6 col-lg-2"><div class="card bg-light border-0"><div class="card-body py-3"><div class="small text-muted">{{ __('medication_administration.administered_today') }}</div><div class="h4 mb-0">{{ $administered_today->count() }}</div></div></div></div>
    <div class="col-6 col-lg-2"><div class="card bg-light border-0"><div class="card-body py-3"><div class="small text-muted">{{ __('medication_administration.prn_sos') }}</div><div class="h4 mb-0">{{ $prn->count() }}</div></div></div></div>
</div>

<div class="card">
    <div class="card-header"><h5 class="card-title mb-0">{{ __('medication_administration.emergency_medication_tasks') }}</h5></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>{{ __('medication_administration.patient') }}</th>
                        <th>{{ __('medication_administration.medication') }}</th>
                        <th>{{ __('medication_administration.dose_route') }}</th>
                        <th>{{ __('medication_administration.scheduled') }}</th>
                        <th>{{ __('medication_administration.status') }}</th>
                        <th>{{ __('medication_administration.prescribed_by') }}</th>
                        <th>{{ __('medication_administration.administered_by') }}</th>
                        <th class="text-end">{{ __('medication_administration.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schedules as $schedule)
                    @php $order = $schedule->medicationOrder; $modalId = 'emergency-dose-'.$schedule->id; @endphp
                    <tr class="{{ $schedule->medicationOrder->frequency?->is_stat ? 'table-danger' : '' }}">
                        <td>
                            <div class="fw-semibold">{{ $order->patient->full_name ?? $order->visit->patient->full_name ?? 'Patient' }}</div>
                            <small class="text-muted">
                                {{ $order->emergencyCase->emergency_number ?? $order->visit->visit_number ?? __('medication_administration.emergency_visit') }}
                                @if($order->emergencyCase?->bay)
                                    - {{ $order->emergencyCase->bay->name }}
                                @endif
                            </small>
                        </td>
                        <td>{{ $order->display_name }} @if($order->frequency?->is_stat)<span class="badge bg-danger ms-1">STAT</span>@endif</td>
                        <td>{{ trim(($schedule->dose ?? $order->dose).' '.($schedule->dose_unit ?? $order->dose_unit)) }} {{ $schedule->route ? '· '.strtoupper($schedule->route) : '' }}</td>
                        <td>{{ $schedule->scheduled_at?->format('d M H:i') }}</td>
                        <td><x-status-badge :status="$schedule->clinicalTask?->status ?? $schedule->status" domain="mar" soft /></td>
                        <td>{{ $order->prescriber->name ?? '—' }}</td>
                        <td>{{ $schedule->administration->administeredBy->name ?? '—' }}</td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1 flex-wrap">
                                @if($order->visit)
                                    @can('emergency.mar_chart.view')
                                    <a href="{{ $workspaceRoutes->route('admin.emergency.mar-chart', $order->visit) }}" class="btn btn-sm btn-outline-primary">{{ __('medication_administration.view_mar') }}</a>
                                    @endcan
                                @endif
                                @if(!$schedule->administration && !in_array($schedule->status, ['GIVEN','CANCELLED','VOIDED'], true))
                                    @can('medication_administration.administer')
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">{{ __('medication_administration.administer') }}</button>
                                    @endcan
                                @else
                                    <span class="text-muted small align-self-center">{{ __('medication_administration.closed') }}</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8"><x-empty-state :message="__('medication_administration.no_emergency_tasks')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@foreach($schedules as $schedule)
    @if(!$schedule->administration)
        @include('medication-administration.partials.administer-modal', ['schedule' => $schedule, 'order' => $schedule->medicationOrder, 'stockLocations' => $stockLocations, 'modalId' => 'emergency-dose-'.$schedule->id])
    @endif
@endforeach
@endsection

@push('scripts')
<script>
setTimeout(function () {
    window.UhmsInertia.reload({ preserveScroll: true, preserveState: true });
}, 60000);
document.querySelectorAll('.med-status').forEach(function (select) {
    function update() {
        var wrap = select.closest('.modal-body').querySelector('.med-reason-wrap');
        if (wrap) wrap.classList.toggle('d-none', ['GIVEN', 'PARTIALLY_GIVEN'].includes(select.value));
    }
    select.addEventListener('change', update);
    update();
});
document.querySelectorAll('.med-source-stock').forEach(function (select) {
    function update() {
        var wrap = select.closest('.modal-body').querySelector('.med-stock-location-wrap');
        if (wrap) wrap.classList.toggle('d-none', select.value === 'PATIENT_DISPENSED_STOCK');
    }
    select.addEventListener('change', update);
    update();
});
</script>
@endpush
