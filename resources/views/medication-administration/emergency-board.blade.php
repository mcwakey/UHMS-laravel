@extends('layouts.app')
@section('title', 'Emergency Medication Board')

@section('content')
<div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
    <div>
        <h4 class="fw-bold mb-1">Emergency Medication Board</h4>
        <p class="text-muted mb-0">STAT, due, overdue, PRN/SOS, and administered emergency medications.</p>
    </div>
    <a href="{{ route('admin.medication-administration.reports') }}" class="btn btn-outline-primary btn-sm">Reports</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-lg-2"><div class="card bg-danger text-white border-0"><div class="card-body py-3"><div class="small opacity-75">STAT Due</div><div class="h4 mb-0">{{ $stat_due->count() }}</div></div></div></div>
    <div class="col-6 col-lg-2"><div class="card bg-info text-white border-0"><div class="card-body py-3"><div class="small opacity-75">Due Now</div><div class="h4 mb-0">{{ $counts['due_now'] ?? 0 }}</div></div></div></div>
    <div class="col-6 col-lg-2"><div class="card bg-danger-subtle border-0"><div class="card-body py-3"><div class="small text-danger">Overdue</div><div class="h4 mb-0 text-danger">{{ $counts['overdue'] ?? 0 }}</div></div></div></div>
    <div class="col-6 col-lg-2"><div class="card bg-light border-0"><div class="card-body py-3"><div class="small text-muted">Administered Today</div><div class="h4 mb-0">{{ $administered_today->count() }}</div></div></div></div>
    <div class="col-6 col-lg-2"><div class="card bg-light border-0"><div class="card-body py-3"><div class="small text-muted">PRN / SOS</div><div class="h4 mb-0">{{ $prn->count() }}</div></div></div></div>
</div>

<div class="card">
    <div class="card-header"><h5 class="card-title mb-0">Emergency Medication Tasks</h5></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Patient</th>
                        <th>Medication</th>
                        <th>Dose / Route</th>
                        <th>Scheduled</th>
                        <th>Status</th>
                        <th>Prescribed By</th>
                        <th>Administered By</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schedules as $schedule)
                    @php $order = $schedule->medicationOrder; $modalId = 'emergency-dose-'.$schedule->id; @endphp
                    <tr class="{{ $schedule->medicationOrder->frequency?->is_stat ? 'table-danger' : '' }}">
                        <td>
                            <div class="fw-semibold">{{ $order->patient->full_name ?? $order->visit->patient->full_name ?? 'Patient' }}</div>
                            <small class="text-muted">{{ $order->visit->visit_number ?? 'Emergency visit' }}</small>
                        </td>
                        <td>{{ $order->display_name }} @if($order->frequency?->is_stat)<span class="badge bg-danger ms-1">STAT</span>@endif</td>
                        <td>{{ trim(($schedule->dose ?? $order->dose).' '.($schedule->dose_unit ?? $order->dose_unit)) }} {{ $schedule->route ? '· '.strtoupper($schedule->route) : '' }}</td>
                        <td>{{ $schedule->scheduled_at?->format('d M H:i') }}</td>
                        <td><span class="badge badge-soft-{{ match($schedule->clinicalTask?->status ?? $schedule->status){'OVERDUE'=>'danger','DUE'=>'info','COMPLETED'=>'success','HELD'=>'warning','REFUSED'=>'warning','MISSED'=>'danger',default=>'secondary'} }}">{{ str_replace('_',' ', $schedule->clinicalTask?->status ?? $schedule->status) }}</span></td>
                        <td>{{ $order->prescriber->name ?? '—' }}</td>
                        <td>{{ $schedule->administration->administeredBy->name ?? '—' }}</td>
                        <td class="text-end">
                            @if(!$schedule->administration && !in_array($schedule->status, ['GIVEN','CANCELLED','VOIDED'], true))
                                @can('medication_administration.administer')
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">Administer</button>
                                @endcan
                            @else
                                <span class="text-muted small">Closed</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No emergency medication tasks found.</td></tr>
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
    if (window.UhmsInertia) {
        window.UhmsInertia.reload({ preserveScroll: true, preserveState: true });
    }
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
