@extends('layouts.app')
@section('title', 'Medication Administration — ' . $admission->admission_number)

@section('content')
<div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
    <div>
        <h4 class="fw-bold mb-1">Medication Administration Record</h4>
        <p class="text-muted mb-0">
            {{ $admission->patient->full_name }} · {{ $admission->admission_number }} ·
            {{ $admission->bed->ward->name ?? 'Ward' }} Bed {{ $admission->bed->bed_number ?? '—' }}
        </p>
    </div>
    <div class="d-flex gap-2">
        @can('admission.mar_chart.view')
        <a href="{{ route('admin.admissions.mar-chart', $admission) }}" class="btn btn-primary btn-sm">MAR Chart</a>
        @endcan
        <a href="{{ route('admin.admissions.medication-board') }}" class="btn btn-outline-secondary btn-sm">Board</a>
        <a href="{{ route('admin.admissions.show', $admission) }}" class="btn btn-outline-primary btn-sm">Admission</a>
    </div>
</div>

<div class="row g-3 mb-3">
    @foreach(['due_now' => 'Due Now', 'overdue' => 'Overdue', 'upcoming' => 'Upcoming', 'completed_today' => 'Completed Today', 'missed' => 'Missed/Held/Refused'] as $key => $label)
    <div class="col-6 col-lg">
        <div class="card border-0 bg-light h-100">
            <div class="card-body py-3">
                <div class="text-muted small">{{ $label }}</div>
                <div class="h4 mb-0 fw-bold {{ $key === 'overdue' ? 'text-danger' : ($key === 'due_now' ? 'text-info' : '') }}">{{ $counts[$key] ?? 0 }}</div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="card mb-3">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0"><i class="ti ti-pill me-1"></i>Active Medications</h5>
        <span class="badge bg-primary">{{ $orders->count() }}</span>
    </div>
    <div class="card-body">
        <div class="row g-3">
            @forelse($orders as $entry)
            @php $order = $entry['order']; $progress = $entry['progress']; @endphp
            <div class="col-md-6">
                <div class="border rounded p-3 h-100">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <div>
                            <h6 class="fw-bold mb-1">{{ $order->display_name }}</h6>
                            <div class="small text-muted">
                                {{ $order->dose }} {{ $order->route ? '· '.strtoupper($order->route) : '' }}
                                {{ $order->frequency_code ? '· '.$order->frequency_code : '' }}
                            </div>
                        </div>
                        <span class="badge badge-soft-{{ match($order->status){'ACTIVE_ADMINISTRATION'=>'success','HELD'=>'warning','STOPPED'=>'danger','COMPLETED'=>'success',default=>'secondary'} }}">{{ str_replace('_',' ', $order->status) }}</span>
                    </div>
                    <div class="progress my-3" style="height: 7px;">
                        <div class="progress-bar" style="width: {{ $progress['progress_percentage'] }}%"></div>
                    </div>
                    <div class="d-flex flex-wrap gap-3 small">
                        <span><strong>{{ $progress['given_doses'] }}</strong>/{{ $progress['total_doses'] }} given</span>
                        <span><strong>{{ $progress['available_patient_doses'] }}</strong> available</span>
                        <span>Next: <strong>{{ $progress['next_due_at'] ? $progress['next_due_at']->format('d M H:i') : '—' }}</strong></span>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        @can('medication_orders.hold')
                        <form method="POST" action="{{ route('admin.medication-administration.orders.hold', $order) }}" class="d-flex gap-1">
                            @csrf
                            <input type="hidden" name="reason" value="Held from MAR board">
                            <button class="btn btn-sm btn-outline-warning">Hold</button>
                        </form>
                        @endcan
                        @can('medication_orders.stop')
                        <form method="POST" action="{{ route('admin.medication-administration.orders.stop', $order) }}" onsubmit="return confirm('Stop this medication order and cancel future doses?')">
                            @csrf
                            <input type="hidden" name="reason" value="Stopped from MAR board">
                            <button class="btn btn-sm btn-outline-danger">Stop</button>
                        </form>
                        @endcan
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12 text-center text-muted py-4">No medication orders are linked to this admission yet.</div>
            @endforelse
        </div>
    </div>
</div>

@php
    $groups = [
        'Overdue' => $overdue,
        'Due Now' => $due_now,
        'Upcoming' => $upcoming,
        'Completed' => $completed,
    ];
@endphp

@foreach($groups as $label => $groupSchedules)
<div class="card mb-3">
    <div class="card-header">
        <h5 class="card-title mb-0">{{ $label }} Doses <span class="badge bg-secondary ms-1">{{ $groupSchedules->count() }}</span></h5>
    </div>
    <div class="card-body p-0">
        @if($groupSchedules->isNotEmpty())
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Medication</th>
                        <th>Dose</th>
                        <th>Scheduled</th>
                        <th>Status</th>
                        <th>Administered By</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($groupSchedules as $schedule)
                    @php $order = $schedule->medicationOrder; $modalId = 'dose-modal-'.$schedule->id; @endphp
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $order->display_name }}</div>
                            <small class="text-muted">{{ $order->frequency_code }} · {{ $order->prescriber->name ?? 'Prescriber unknown' }}</small>
                        </td>
                        <td>{{ trim(($schedule->dose ?? $order->dose).' '.($schedule->dose_unit ?? $order->dose_unit)) }} {{ $schedule->route ? '· '.strtoupper($schedule->route) : '' }}</td>
                        <td>{{ $schedule->scheduled_at?->format('d M Y H:i') }}</td>
                        <td><span class="badge badge-soft-{{ match($schedule->clinicalTask?->status ?? $schedule->status){'OVERDUE'=>'danger','DUE'=>'info','COMPLETED'=>'success','HELD'=>'warning','REFUSED'=>'warning','MISSED'=>'danger',default=>'secondary'} }}">{{ str_replace('_',' ', $schedule->clinicalTask?->status ?? $schedule->status) }}</span></td>
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
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-4 text-muted">No {{ strtolower($label) }} medication doses.</div>
        @endif
    </div>
</div>
@endforeach

@foreach($schedules as $schedule)
    @if(!$schedule->administration)
        @include('medication-administration.partials.administer-modal', ['schedule' => $schedule, 'order' => $schedule->medicationOrder, 'stockLocations' => $stockLocations, 'modalId' => 'dose-modal-'.$schedule->id])
    @endif
@endforeach

@endsection

@push('scripts')
<script>
(function () {
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
})();
</script>
@endpush
