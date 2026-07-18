@extends('layouts.app')
@section('title', __('medication_administration.admission_title', ['number' => $admission->admission_number]))

@section('content')
<div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
    <div>
        <h4 class="fw-bold mb-1">{{ __('medication_administration.medication_administration_record') }}</h4>
        <p class="text-muted mb-0">
            {{ $admission->patient->full_name }} · {{ $admission->admission_number }} ·
            {{ $admission->bed->ward->name ?? __('medication_administration.ward') }} {{ __('medication_administration.bed_label', ['bed' => $admission->bed->bed_number ?? '—']) }}
        </p>
    </div>
    <div class="d-flex gap-2">
        @can('admission.mar_chart.view')
        <a href="{{ $workspaceRoutes->route('admin.admissions.mar-chart', $admission) }}" class="btn btn-primary btn-sm">{{ __('medication_administration.mar_chart') }}</a>
        @endcan
        <a href="{{ $workspaceRoutes->route('admin.admissions.medication-board') }}" class="btn btn-outline-secondary btn-sm">{{ __('medication_administration.board') }}</a>
        <a href="{{ $workspaceRoutes->route('admin.admissions.show', $admission) }}" class="btn btn-outline-primary btn-sm">{{ __('medication_administration.admission') }}</a>
    </div>
</div>

<div class="row g-3 mb-3">
    @foreach(['due_now' => __('medication_administration.due_now'), 'overdue' => __('medication_administration.overdue'), 'upcoming' => __('medication_administration.upcoming'), 'completed_today' => __('medication_administration.completed_today'), 'missed' => __('medication_administration.held_refused')] as $key => $label)
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
        <h5 class="card-title mb-0"><i class="ti ti-pill me-1"></i>{{ __('medication_administration.active_medications') }}</h5>
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
                        <x-status-badge :status="$order->status" domain="med_order" soft />
                    </div>
                    <div class="progress my-3" style="height: 7px;">
                        <div class="progress-bar" style="width: {{ $progress['progress_percentage'] }}%"></div>
                    </div>
                    <div class="d-flex flex-wrap gap-3 small">
                        <span><strong>{{ $progress['given_doses'] }}</strong>/{{ $progress['total_doses'] }} {{ __('medication_administration.given') }}</span>
                        <span><strong>{{ $progress['available_patient_doses'] }}</strong> {{ __('medication_administration.available') }}</span>
                        <span>{{ __('medication_administration.next') }}: <strong>{{ $progress['next_due_at'] ? $progress['next_due_at']->format('d M H:i') : '—' }}</strong></span>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        @can('medication_orders.hold')
                        <form method="POST" action="{{ $workspaceRoutes->route('admin.medication-administration.orders.hold', $order) }}" class="d-flex gap-1">
                            @csrf
                            <input type="hidden" name="reason" value="{{ __('medication_administration.held_from_mar_board') }}">
                            <button class="btn btn-sm btn-outline-warning">{{ __('medication_administration.hold') }}</button>
                        </form>
                        @endcan
                        @can('medication_orders.stop')
                        <x-confirm-form :action="$workspaceRoutes->route('admin.medication-administration.orders.stop', $order)" method="POST"
                            :button-label="__('medication_administration.stop')" button-class="btn btn-sm btn-outline-danger" icon="ti-player-stop"
                            :confirm-title="__('medication_administration.stop_order_confirm_title')" :confirm-text="__('medication_administration.stop_order_confirm_text')"
                            :confirm-button="__('medication_administration.stop_order_confirm_button')" require-reason :reason-placeholder="__('medication_administration.stop_order_reason_placeholder')" />
                        @endcan
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12 text-center text-muted py-4">{{ __('medication_administration.no_admission_orders') }}</div>
            @endforelse
        </div>
    </div>
</div>

@php
    $groups = [
        __('medication_administration.overdue') => $overdue,
        __('medication_administration.due_now') => $due_now,
        __('medication_administration.upcoming') => $upcoming,
        __('medication_administration.completed') => $completed,
    ];
@endphp

@foreach($groups as $label => $groupSchedules)
<div class="card mb-3">
    <div class="card-header">
        <h5 class="card-title mb-0">{{ __('medication_administration.doses_group', ['label' => $label]) }} <span class="badge bg-secondary ms-1">{{ $groupSchedules->count() }}</span></h5>
    </div>
    <div class="card-body p-0">
        @if($groupSchedules->isNotEmpty())
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>{{ __('medication_administration.medication') }}</th>
                        <th>{{ __('medication_administration.dose') }}</th>
                        <th>{{ __('medication_administration.scheduled') }}</th>
                        <th>{{ __('medication_administration.status') }}</th>
                        <th>{{ __('medication_administration.administered_by') }}</th>
                        <th class="text-end">{{ __('medication_administration.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($groupSchedules as $schedule)
                    @php $order = $schedule->medicationOrder; $modalId = 'dose-modal-'.$schedule->id; @endphp
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $order->display_name }}</div>
                            <small class="text-muted">{{ $order->frequency_code }} · {{ $order->prescriber->name ?? __('medication_administration.prescriber_unknown') }}</small>
                        </td>
                        <td>{{ trim(($schedule->dose ?? $order->dose).' '.($schedule->dose_unit ?? $order->dose_unit)) }} {{ $schedule->route ? '· '.strtoupper($schedule->route) : '' }}</td>
                        <td>{{ $schedule->scheduled_at?->format('d M Y H:i') }}</td>
                        <td><x-status-badge :status="$schedule->clinicalTask?->status ?? $schedule->status" domain="mar" soft /></td>
                        <td>{{ $schedule->administration->administeredBy->name ?? '—' }}</td>
                        <td class="text-end">
                            @if(!$schedule->administration && !in_array($schedule->status, ['GIVEN','CANCELLED','VOIDED'], true))
                                @can('medication_administration.administer')
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">{{ __('medication_administration.administer') }}</button>
                                @endcan
                            @else
                                <span class="text-muted small">{{ __('medication_administration.closed') }}</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-4 text-muted">{{ __('medication_administration.no_doses_in_group', ['label' => strtolower($label)]) }}</div>
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
