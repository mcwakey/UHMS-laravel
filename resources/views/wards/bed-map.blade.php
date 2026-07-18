@extends('layouts.app')
@section('title', __('wards.bed_map'))

@push('styles')
<style>
.bed-available {
    cursor: pointer;
    transition: transform .15s ease, box-shadow .15s ease;
}
.bed-available:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(39,174,96,.25);
}
.bed-tile {
    min-height: 10.5rem;
}
</style>
@endpush

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ __('admissions.capacity_board') }}</h4>
    </div>
    <div class="text-end d-flex gap-2">
        <a href="{{ $workspaceRoutes->route('admin.wards.index') }}" class="btn btn-outline-secondary btn-md fs-13"><i class="ti ti-building-hospital me-1"></i>{{ __('wards.wards') }}</a>
        <a href="{{ $workspaceRoutes->route('admin.wards.beds') }}" class="btn btn-outline-info btn-md fs-13"><i class="ti ti-bed me-1"></i>{{ __('wards.manage_beds') }}</a>
    </div>
</div>

<div class="row g-2 mb-3">
    @foreach([
        'total' => ['label' => __('wards.total'), 'class' => 'secondary'],
        'available' => ['label' => __('wards.available'), 'class' => 'success'],
        'reserved' => ['label' => __('wards.reserved'), 'class' => 'info'],
        'occupied' => ['label' => __('wards.occupied'), 'class' => 'danger'],
        'cleaning' => ['label' => __('statuses.default.cleaning'), 'class' => 'cyan'],
        'maintenance' => ['label' => __('wards.maintenance'), 'class' => 'warning'],
        'blocked' => ['label' => __('statuses.default.blocked'), 'class' => 'dark'],
        'isolation' => ['label' => __('statuses.default.isolation'), 'class' => 'purple'],
    ] as $key => $meta)
        <div class="col-6 col-md-3 col-xl-2">
            <div class="border rounded bg-white p-2 h-100">
                <div class="text-muted small">{{ $meta['label'] }}</div>
                <div class="fs-4 fw-bold text-{{ $meta['class'] }}">{{ $capacity[$key] ?? 0 }}</div>
            </div>
        </div>
    @endforeach
    <div class="col-12 col-md-6 col-xl-3">
        <div class="border rounded bg-white p-2 h-100">
            <div class="d-flex justify-content-between">
                <span class="text-muted small">{{ __('admissions.occupancy') }}</span>
                <span class="fw-semibold">{{ $capacity['occupancy_percentage'] }}%</span>
            </div>
            <div class="progress mt-2" style="height: 8px;">
                <div class="progress-bar bg-danger" style="width: {{ min(100, $capacity['occupancy_percentage']) }}%"></div>
            </div>
            <div class="small text-muted mt-2">
                {{ __('admissions.active_reservations') }}: {{ $capacity['active_reservations'] }}
                · {{ __('admissions.expiring_reservations') }}: {{ $capacity['expiring_reservations'] }}
                · {{ __('admissions.transfers_today') }}: {{ $capacity['transfers_today'] }}
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ $workspaceRoutes->route('admin.wards.bed-map') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">{{ __('wards.ward') }}</label>
                <select name="ward_id" class="form-select">
                    <option value="">{{ __('wards.all_wards') }}</option>
                    @foreach($allWards as $ward)
                        <option value="{{ $ward->id }}" @selected(($filters['ward_id'] ?? '') == $ward->id)>{{ $ward->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">{{ __('common.status') }}</label>
                <select name="status" class="form-select">
                    <option value="">{{ __('common.all_statuses') }}</option>
                    @foreach(\App\Enums\BedStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->translatedLabel() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">{{ __('wards.bed_type') }}</label>
                <select name="bed_type" class="form-select">
                    <option value="">{{ __('wards.all_types') }}</option>
                    @foreach(\App\Enums\BedType::cases() as $type)
                        <option value="{{ $type->value }}" @selected(($filters['bed_type'] ?? '') === $type->value)>{{ $type->translatedLabel() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">{{ __('common.search') }}</label>
                <input name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('admissions.search_beds_ph') }}">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary flex-fill"><i class="ti ti-filter"></i></button>
                <a href="{{ $workspaceRoutes->route('admin.wards.bed-map') }}" class="btn btn-outline-secondary"><i class="ti ti-x"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="d-flex flex-wrap gap-3 mb-3 align-items-center">
    <span><i class="ti ti-square-filled text-success"></i> {{ __('wards.available') }}</span>
    <span><i class="ti ti-square-filled text-danger"></i> {{ __('wards.occupied') }}</span>
    <span><i class="ti ti-square-filled text-info"></i> {{ __('wards.reserved') }}</span>
    <span><i class="ti ti-square-filled text-cyan"></i> {{ __('statuses.default.cleaning') }}</span>
    <span><i class="ti ti-square-filled text-warning"></i> {{ __('wards.maintenance') }}</span>
    <span><i class="ti ti-square-filled text-dark"></i> {{ __('statuses.default.blocked') }}</span>
    <span><i class="ti ti-square-filled text-purple"></i> {{ __('statuses.default.isolation') }}</span>
</div>

@forelse($wards as $ward)
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0">{{ $ward->name }} <span class="text-muted fs-13">({{ $ward->code }})</span></h5>
            @if($ward->floor)
                <small class="text-muted">{{ __('wards.floor') }}: {{ $ward->floor }}</small>
            @endif
        </div>
        <div class="text-end">
            <span class="badge badge-soft-success me-1">{{ $ward->available_beds_count }} {{ __('wards.available') }}</span>
            <span class="badge badge-soft-danger me-1">{{ $ward->occupied_beds_count }} {{ __('wards.occupied') }}</span>
            <span class="badge badge-soft-secondary">{{ $ward->beds_count }} {{ __('wards.total') }}</span>
        </div>
    </div>
    <div class="card-body">
        @if($ward->beds->count() > 0)
        <div class="row g-2">
            @foreach($ward->beds as $bed)
            <div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl-2">
                @php
                    $isAvailable = $bed->status === \App\Enums\BedStatus::AVAILABLE;
                    $colorClass = match($bed->status) {
                        \App\Enums\BedStatus::OCCUPIED => 'border-danger bg-danger bg-opacity-10',
                        \App\Enums\BedStatus::AVAILABLE => 'border-success bg-success bg-opacity-10',
                        \App\Enums\BedStatus::RESERVED => 'border-info bg-info bg-opacity-10',
                        \App\Enums\BedStatus::CLEANING => 'border-info bg-info bg-opacity-10',
                        \App\Enums\BedStatus::MAINTENANCE => 'border-warning bg-warning bg-opacity-10',
                        \App\Enums\BedStatus::BLOCKED => 'border-dark bg-dark bg-opacity-10',
                        \App\Enums\BedStatus::ISOLATION => 'border-purple bg-purple bg-opacity-10',
                    };
                @endphp
                @if($isAvailable && auth()->user()?->can('ward.admit'))
                <a href="{{ $workspaceRoutes->route('admin.admissions.create', ['bed_id' => $bed->id]) }}" class="d-block text-decoration-none text-reset border rounded p-2 {{ $colorClass }} bed-tile bed-available">
                @else
                <div class="border rounded p-2 {{ $colorClass }} bed-tile">
                @endif
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div>
                            <div class="fw-bold">{{ $bed->bed_number }}</div>
                            <small class="text-muted">{{ $bed->bed_type->translatedLabel() }}</small>
                        </div>
                        <span class="badge badge-soft-{{ $bed->status->color() }}">{{ $bed->status->translatedLabel() }}</span>
                    </div>

                    <div class="mt-2 small">
                        @if($bed->currentAdmission)
                            @php
                                $bedAdmission = $bed->currentAdmission;
                                $latestVitals = $bedAdmission->visit?->vitals?->sortByDesc('recorded_at')->first();
                                $vitalsOverdue = ! $latestVitals || $latestVitals->recorded_at?->lt(now()->subHours((int) config('admissions.vitals_overdue_hours', 8)));
                                $openNursingTasks = $bedAdmission->nursingTasks->filter(fn ($task) => $task->status?->isOpen());
                                $overdueNursingTasks = $openNursingTasks->filter(fn ($task) => $task->due_at && $task->due_at->isPast());
                                $blockedClearances = $bedAdmission->dischargeClearances->filter(fn ($clearance) => $clearance->status === \App\Enums\AdmissionDischargeClearanceStatus::BLOCKED);
                                $pendingClearances = $bedAdmission->dischargeClearances->filter(fn ($clearance) => ! $clearance->status?->isReady());
                                $summaryMissing = ! $bedAdmission->dischargeSummaryRecord;
                                $billingWarning = $bedAdmission->visit?->latestInvoice && (float) $bedAdmission->visit->latestInvoice->balance > 0;
                                $expectedToday = $bedAdmission->expected_discharge_at?->isToday();
                            @endphp
                            <a href="{{ $workspaceRoutes->route('admin.admissions.show', $bed->currentAdmission) }}" class="text-decoration-none fw-semibold">
                                {{ Str::limit($bed->currentAdmission->patient->full_name, 24) }}
                            </a>
                            <div class="text-muted">{{ __('admissions.length_of_stay_label') }}: {{ $bed->currentAdmission->length_of_stay }}d</div>
                            <div class="d-flex flex-wrap gap-1 mt-2">
                                @if($openNursingTasks->isNotEmpty())
                                    <span class="badge badge-soft-warning">{{ __('admissions.open_nursing_tasks') }}: {{ $openNursingTasks->count() }}</span>
                                @endif
                                @if($overdueNursingTasks->isNotEmpty())
                                    <span class="badge bg-danger">{{ __('admissions.overdue_label') }}: {{ $overdueNursingTasks->count() }}</span>
                                @endif
                                @if($vitalsOverdue)
                                    <span class="badge bg-danger">{{ __('admissions.vitals_due') }}</span>
                                @elseif($latestVitals)
                                    <span class="badge badge-soft-success">{{ __('admissions.vitals') }} {{ $latestVitals->recorded_at?->diffForHumans() }}</span>
                                @endif
                                @if($bedAdmission->discharge_planning_started_at)
                                    <span class="badge badge-soft-info">{{ __('admissions.discharge_planning') }}</span>
                                @endif
                                @if($expectedToday)
                                    <span class="badge bg-info">{{ __('admissions.expected_today') }}</span>
                                @endif
                                @if($blockedClearances->isNotEmpty())
                                    <span class="badge bg-danger">{{ __('admissions.clearance_blocked') }}</span>
                                @elseif($pendingClearances->isNotEmpty())
                                    <span class="badge badge-soft-warning">{{ __('admissions.clearance_pending') }}</span>
                                @elseif($bedAdmission->dischargeClearances->isNotEmpty() && ! $summaryMissing && ! $billingWarning)
                                    <span class="badge bg-success">{{ __('admissions.ready_for_discharge') }}</span>
                                @endif
                                @if($summaryMissing)
                                    <span class="badge badge-soft-warning">{{ __('admissions.summary_missing_short') }}</span>
                                @endif
                                @if($billingWarning)
                                    <span class="badge badge-soft-warning">{{ __('admissions.billing_warning_short') }}</span>
                                @endif
                            </div>
                        @elseif($bed->activeReservation)
                            <a href="{{ $workspaceRoutes->route('admin.admissions.requests.show', $bed->activeReservation->admissionRequest) }}" class="text-decoration-none fw-semibold">
                                {{ Str::limit($bed->activeReservation->admissionRequest?->patient?->full_name, 24) }}
                            </a>
                            <div class="{{ $bed->activeReservation->expires_at && $bed->activeReservation->expires_at->lte(now()->addHour()) ? 'text-danger fw-semibold' : 'text-muted' }}">
                                {{ __('admissions.reserved_until') }}: {{ $bed->activeReservation->expires_at?->format('d M H:i') ?? '—' }}
                            </div>
                        @else
                            <span class="text-muted">{{ __('admissions.no_current_bed') }}</span>
                        @endif
                    </div>

                    @if($bed->status_reason)
                        <div class="alert alert-light border py-1 px-2 mt-2 mb-0 small">{{ Str::limit($bed->status_reason, 70) }}</div>
                    @endif
                    @if($bed->statusChangedBy || $bed->status_changed_at)
                        <div class="small text-muted mt-2">
                            {{ __('admissions.last_status_change') }}:
                            {{ $bed->status_changed_at?->diffForHumans() ?? '—' }}
                            @if($bed->statusChangedBy) · {{ $bed->statusChangedBy->name }} @endif
                        </div>
                    @endif
                @if($isAvailable && auth()->user()?->can('ward.admit'))
                </a>
                @else
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @else
        <p class="text-muted text-center mb-0">{{ __('wards.no_beds_configured') }}</p>
        @endif
    </div>
</div>
@empty
<div class="card">
    <div class="card-body text-center py-5 text-muted">
        <i class="ti ti-building-hospital fs-1 d-block mb-2"></i>
        {{ __('wards.no_active_wards_map') }}
    </div>
</div>
@endforelse
@endsection
