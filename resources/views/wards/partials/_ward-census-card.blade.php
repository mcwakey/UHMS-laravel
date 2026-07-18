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
