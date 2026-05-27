@php
    $header = $chart['header'];
    $selectedDate = $chart['selected_date'];
    $contextRoute = function ($date) use ($chart) {
        $params = ['date' => $date instanceof \Illuminate\Support\Carbon ? $date->toDateString() : $date];

        if ($chart['context'] === 'admission' && $chart['admission']) {
            return route('admin.admissions.mar-chart', array_merge(['admission' => $chart['admission']], $params));
        }

        if ($chart['context'] === 'emergency') {
            return route('admin.emergency.mar-chart', array_merge(['visit' => $chart['visit']], $params));
        }

        return route('admin.visits.mar-chart', array_merge(['visit' => $chart['visit']], $params));
    };
@endphp

<div id="mar-chart-content">
    <div class="card mb-3">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h5 class="card-title mb-1">Medication Administration Record</h5>
                <small class="text-muted">Generated {{ $chart['generated_at']->format('d M Y, h:i A') }}</small>
            </div>
            <form id="mar-date-form" action="{{ $contextRoute($selectedDate) }}" method="GET" class="d-flex gap-2 flex-wrap no-print">
                <a href="{{ $contextRoute($chart['previous_date']) }}" class="btn btn-outline-secondary btn-sm js-mar-date-link"><i class="ti ti-chevron-left me-1"></i>Previous Day</a>
                <a href="{{ $contextRoute(today()) }}" class="btn btn-outline-primary btn-sm js-mar-date-link">Today</a>
                <input type="date" name="date" class="form-control form-control-sm" value="{{ $selectedDate->toDateString() }}" style="width: 155px;">
                <button class="btn btn-primary btn-sm"><i class="ti ti-calendar me-1"></i>Go</button>
                <a href="{{ $contextRoute($chart['next_date']) }}" class="btn btn-outline-secondary btn-sm js-mar-date-link">Next Day<i class="ti ti-chevron-right ms-1"></i></a>
            </form>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6 col-xl-3"><span class="text-muted small d-block">Patient</span><strong>{{ $header['patient_name'] }}</strong><div class="small text-muted">{{ $header['patient_number'] ?? '-' }} @if($header['age']) - {{ $header['age'] }} yrs @endif @if($header['gender']) - {{ $header['gender'] }} @endif</div></div>
                <div class="col-md-6 col-xl-3"><span class="text-muted small d-block">Encounter</span><strong>{{ $header['admission_number'] ?? $header['emergency_number'] ?? $header['visit_number'] }}</strong><div class="small text-muted">Visit {{ $header['visit_number'] ?? '-' }} - {{ $header['visit_type'] ?? '-' }}</div></div>
                <div class="col-md-6 col-xl-3"><span class="text-muted small d-block">Ward / Location</span><strong>{{ $header['ward_bed'] ?? $header['location'] ?? '-' }}</strong><div class="small text-muted">Status: {{ $header['current_status'] ?? '-' }}</div></div>
                <div class="col-md-6 col-xl-3"><span class="text-muted small d-block">Doctor / Cover</span><strong>{{ $header['primary_doctor'] ?? '-' }}</strong><div class="small text-muted">Insurance: {{ $header['insurance'] ?? '-' }}</div></div>
                <div class="col-12"><span class="text-muted small">Selected Date:</span> <strong>{{ $selectedDate->format('d M Y') }}</strong></div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3 no-print">
        <div class="col-6 col-lg-2"><div class="card bg-info text-white border-0 h-100"><div class="card-body py-3"><div class="small opacity-75">Due Now</div><div class="h4 mb-0">{{ $chart['summary']['due_now'] }}</div></div></div></div>
        <div class="col-6 col-lg-2"><div class="card bg-danger text-white border-0 h-100"><div class="card-body py-3"><div class="small opacity-75">Overdue</div><div class="h4 mb-0">{{ $chart['summary']['overdue'] }}</div></div></div></div>
        <div class="col-6 col-lg-2"><div class="card bg-success text-white border-0 h-100"><div class="card-body py-3"><div class="small opacity-75">Given Today</div><div class="h4 mb-0">{{ $chart['summary']['given_today'] }}</div></div></div></div>
        <div class="col-6 col-lg-2"><div class="card bg-warning-subtle border-0 h-100"><div class="card-body py-3"><div class="small text-warning">Held / Refused</div><div class="h4 mb-0">{{ $chart['summary']['held_today'] + $chart['summary']['refused_today'] }}</div></div></div></div>
        <div class="col-6 col-lg-2"><div class="card bg-danger-subtle border-0 h-100"><div class="card-body py-3"><div class="small text-danger">Missed</div><div class="h4 mb-0">{{ $chart['summary']['missed_today'] }}</div></div></div></div>
        <div class="col-6 col-lg-2"><div class="card bg-light border-0 h-100"><div class="card-body py-3"><div class="small text-muted">Upcoming</div><div class="h4 mb-0">{{ $chart['summary']['upcoming'] }}</div></div></div></div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0"><i class="ti ti-table-options me-1"></i>Daily Medication Grid</h5>
            <span class="badge bg-secondary">{{ $chart['medication_rows']->count() }} medication(s)</span>
        </div>
        <div class="card-body p-0">
            @if($chart['time_columns']->isEmpty())
                <div class="text-center text-muted py-5">No fixed medication doses are scheduled for {{ $selectedDate->format('d M Y') }}.</div>
            @else
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0 mar-chart-table">
                    <thead class="table-light">
                        <tr>
                            <th class="mar-medication-col mar-sticky-medication">Medication</th>
                            @foreach($chart['time_columns'] as $time)
                                <th class="text-center mar-time-col">{{ $time }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($chart['medication_rows'] as $row)
                        @php $order = $row['order']; $progress = $row['progress']; @endphp
                        <tr class="{{ $row['is_stat'] ? 'table-danger' : '' }}">
                            <td class="mar-sticky-medication">
                                <div class="fw-bold">{{ $row['product_name'] }} @if($row['is_stat'])<span class="badge bg-danger ms-1">STAT</span>@endif</div>
                                <div class="small text-muted">{{ trim(($row['dose'] ?? '').' '.($row['dose_unit'] ?? '')) }} {{ $row['route'] ? '- '.strtoupper($row['route']) : '' }} {{ $row['frequency'] ? '- '.$row['frequency'] : '' }}</div>
                                <div class="small mt-1"><span class="text-muted">Prescriber:</span> {{ $row['prescriber'] ?? '-' }}</div>
                                <div class="small"><span class="text-muted">Start:</span> {{ $row['start_at']?->format('d M H:i') ?? '-' }} @if($row['end_at']) <span class="text-muted">End:</span> {{ $row['end_at']->format('d M H:i') }} @endif</div>
                                <div class="small"><span class="text-muted">Progress:</span> {{ $progress['given_doses'] }}/{{ $progress['total_doses'] }} given - {{ $progress['remaining_doses'] }} remaining</div>
                                <div class="progress my-1" style="height: 6px;"><div class="progress-bar" style="width: {{ $progress['progress_percentage'] }}%"></div></div>
                                <div class="small"><span class="text-muted">Next:</span> {{ $progress['next_due_at'] ? $progress['next_due_at']->format('d M H:i') : '-' }}</div>
                                @if($row['instructions'])<div class="small text-muted mt-1">{{ $row['instructions'] }}</div>@endif
                                <span class="badge badge-soft-secondary mt-1">{{ str_replace('_', ' ', $row['status']) }}</span>
                            </td>
                            @foreach($chart['time_columns'] as $time)
                                @php $cell = $row['cells'][$time] ?? null; @endphp
                                <td class="text-center mar-dose-cell" data-time="{{ $time }}">
                                    @if($cell)
                                        @php
                                            $modalId = $cell['is_actionable'] ? 'mar-dose-'.$cell['schedule_id'] : 'mar-detail-'.$cell['schedule_id'];
                                        @endphp
                                        <button type="button" class="btn btn-sm btn-light border w-100 mar-cell-btn" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}" data-schedule-id="{{ $cell['schedule_id'] }}">
                                            <span class="badge badge-soft-{{ $cell['status_class'] }} mar-cell-status">{{ str_replace('_', ' ', $cell['status']) }}</span>
                                            <small class="d-block mt-1">{{ trim(($cell['dose'] ?? $row['dose']).' '.($cell['dose_unit'] ?? $row['dose_unit'])) ?: 'Dose' }}</small>
                                            @if($cell['administered_at'])<small class="d-block text-muted">{{ $cell['administered_at']->format('H:i') }}</small>@endif
                                            @if($cell['administered_by'])<small class="d-block text-muted">{{ $cell['administered_by'] }}</small>@endif
                                        </button>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                        @empty
                        <tr><td colspan="{{ $chart['time_columns']->count() + 1 }}" class="text-center text-muted py-4">No medication orders are linked to this encounter.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-info-circle me-1"></i>MAR Legend</h5></div>
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2">
                @foreach($chart['legend'] as $status => $description)
                    @php $class = match($status) {'DUE' => 'info', 'OVERDUE' => 'danger', 'GIVEN' => 'success', 'HELD' => 'warning', 'MISSED' => 'danger', 'REFUSED' => 'warning', 'SKIPPED' => 'secondary', 'CANCELLED' => 'dark', 'VOIDED' => 'dark', 'CORRECTED' => 'primary', default => 'secondary'}; @endphp
                    <span class="badge badge-soft-{{ $class }}">{{ $status }}</span><span class="small text-muted me-2">{{ $description }}</span>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0"><i class="ti ti-clock-plus me-1"></i>PRN / SOS Medications</h5>
            <span class="badge bg-secondary">{{ $chart['prn_medications']->count() }}</span>
        </div>
        <div class="card-body p-0">
            @if($chart['prn_medications']->isNotEmpty())
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Medication</th><th>Dose / Route</th><th>Instruction</th><th>Last Given</th><th class="text-center">Today</th><th class="text-end no-print">Action</th></tr></thead>
                    <tbody>
                        @foreach($chart['prn_medications'] as $prn)
                        @php $order = $prn['order']; $modalId = 'mar-prn-'.$order->id; @endphp
                        <tr>
                            <td><strong>{{ $prn['product_name'] }}</strong><br><small class="text-muted">{{ $prn['frequency'] }}</small></td>
                            <td>{{ trim(($prn['dose'] ?? '').' '.($prn['dose_unit'] ?? '')) }} {{ $prn['route'] ? '- '.strtoupper($prn['route']) : '' }}</td>
                            <td>{{ $prn['instructions'] ?? '-' }}</td>
                            <td>{{ $prn['last_administered_at']?->format('d M H:i') ?? '-' }}</td>
                            <td class="text-center"><span class="badge bg-primary">{{ $prn['total_administrations_today'] }}</span></td>
                            <td class="text-end no-print">
                                @can('medication_administration.administer')
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">Administer PRN</button>
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
                <div class="text-center text-muted py-4">No PRN/SOS medication orders are active for this encounter.</div>
            @endif
        </div>
    </div>

    @if($chart['daily_administrations']->isNotEmpty())
    <div class="card mb-3">
        <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-list-check me-1"></i>Daily Administration Details</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>Time</th><th>Medication</th><th>Status</th><th>Dose / Route</th><th>Administered By</th><th>Reason / Notes / Reaction</th><th>Stock Source</th></tr></thead>
                    <tbody>
                        @foreach($chart['daily_administrations'] as $administration)
                        <tr>
                            <td>{{ $administration['administered_at']?->format('H:i') ?? '-' }}</td>
                            <td>{{ $administration['medication'] ?? 'Medication' }}</td>
                            <td><span class="badge badge-soft-{{ $administration['status_class'] }}">{{ str_replace('_', ' ', $administration['status']) }}</span></td>
                            <td>{{ trim(($administration['dose_given'] ?? '').' '.($administration['route'] ? '- '.strtoupper($administration['route']) : '')) ?: '-' }}</td>
                            <td>{{ $administration['administered_by'] ?? '-' }}</td>
                            <td><small>{{ $administration['reason_not_given'] ?? $administration['notes'] ?? $administration['reaction'] ?? '-' }}</small></td>
                            <td><small>{{ str_replace('_', ' ', $administration['stock_source'] ?? '-') }} @if($administration['stock_location']) - {{ $administration['stock_location'] }} @endif</small></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    @foreach($chart['medication_rows'] as $row)
        @foreach($row['cells'] as $cell)
            @if($cell)
                @php $schedule = $cell['schedule']; $order = $row['order']; @endphp
                @if($cell['is_actionable'])
                    @include('medication-administration.partials.administer-modal', ['schedule' => $schedule, 'order' => $order, 'stockLocations' => $stockLocations, 'modalId' => 'mar-dose-'.$schedule->id])
                @endif

                <div class="modal fade" id="mar-detail-{{ $schedule->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Medication Administration Details</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-6"><span class="text-muted small d-block">Medication</span><strong>{{ $row['product_name'] }}</strong></div>
                                    <div class="col-md-3"><span class="text-muted small d-block">Scheduled</span><strong>{{ $cell['scheduled_at']?->format('d M Y H:i') }}</strong></div>
                                    <div class="col-md-3"><span class="text-muted small d-block">Status</span><span class="badge badge-soft-{{ $cell['status_class'] }}">{{ str_replace('_', ' ', $cell['status']) }}</span></div>
                                    <div class="col-md-4"><span class="text-muted small d-block">Dose Ordered</span><strong>{{ trim(($cell['dose'] ?? $row['dose']).' '.($cell['dose_unit'] ?? $row['dose_unit'])) ?: '-' }}</strong></div>
                                    <div class="col-md-4"><span class="text-muted small d-block">Route</span><strong>{{ strtoupper($cell['route'] ?? $row['route'] ?? '-') }}</strong></div>
                                    <div class="col-md-4"><span class="text-muted small d-block">Frequency</span><strong>{{ $row['frequency'] ?? '-' }}</strong></div>
                                </div>
                                @if($cell['administration'])
                                <hr>
                                <div class="row g-3">
                                    <div class="col-md-4"><span class="text-muted small d-block">Administered By</span><strong>{{ $cell['administered_by'] ?? '-' }}</strong></div>
                                    <div class="col-md-4"><span class="text-muted small d-block">Administered At</span><strong>{{ $cell['administered_at']?->format('d M Y H:i') ?? '-' }}</strong></div>
                                    <div class="col-md-4"><span class="text-muted small d-block">Dose Given</span><strong>{{ $cell['dose_given'] ?? '-' }}</strong></div>
                                    <div class="col-md-6"><span class="text-muted small d-block">Reason Not Given</span><div>{{ $cell['reason_not_given'] ?? '-' }}</div></div>
                                    <div class="col-md-6"><span class="text-muted small d-block">Witness</span><div>{{ $cell['witness'] ?? '-' }}</div></div>
                                    <div class="col-md-6"><span class="text-muted small d-block">Notes</span><div>{{ $cell['notes'] ?? '-' }}</div></div>
                                    <div class="col-md-6"><span class="text-muted small d-block">Reaction</span><div>{{ $cell['reaction'] ?? '-' }}</div></div>
                                    <div class="col-md-6"><span class="text-muted small d-block">Stock Source</span><div>{{ str_replace('_', ' ', $cell['stock_source'] ?? '-') }}</div></div>
                                    <div class="col-md-6"><span class="text-muted small d-block">Stock Movement</span><div>{{ $cell['stock_movement_id'] ? '#'.$cell['stock_movement_id'] : '-' }}</div></div>
                                    @if($cell['corrected_at'])
                                        <div class="col-12 alert alert-info mb-0"><strong>Corrected:</strong> {{ $cell['corrected_at']->format('d M Y H:i') }} - {{ $cell['correction_reason'] }}</div>
                                    @endif
                                </div>
                                @else
                                    <div class="alert alert-light border mt-3 mb-0">This dose is scheduled and has not been administered yet.</div>
                                @endif
                            </div>
                            @if($cell['administration'])
                            <div class="modal-footer d-block">
                                @can('medication_administration.correct')
                                <form method="POST" action="{{ route('admin.medication-administration.records.correct', $cell['administration']) }}" class="row g-2 align-items-end">
                                    @csrf
                                    @method('PATCH')
                                    <div class="col-md-3"><label class="form-label">Status</label><select name="status" class="form-select form-select-sm"><option value="{{ $cell['administration']->status }}">{{ str_replace('_', ' ', $cell['administration']->status) }}</option><option value="GIVEN">Given</option><option value="PARTIALLY_GIVEN">Partially given</option><option value="HELD">Held</option><option value="MISSED">Missed</option><option value="REFUSED">Refused</option><option value="SKIPPED">Skipped</option><option value="NOT_GIVEN">Not given</option></select></div>
                                    <div class="col-md-3"><label class="form-label">Dose Given</label><input name="dose_given" class="form-control form-control-sm" value="{{ $cell['administration']->dose_given }}"></div>
                                    <div class="col-md-3"><label class="form-label">Route</label><input name="route" class="form-control form-control-sm" value="{{ $cell['administration']->route }}"></div>
                                    <div class="col-md-12"><label class="form-label">Correction Reason <span class="text-danger">*</span></label><textarea name="correction_reason" rows="2" class="form-control form-control-sm" required></textarea></div>
                                    <div class="col-12 text-end"><button class="btn btn-sm btn-outline-primary">Save Correction</button></div>
                                </form>
                                @endcan
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    @endforeach

    @foreach($chart['prn_medications'] as $prn)
        @php $order = $prn['order']; @endphp
        <div class="modal fade" id="mar-prn-{{ $order->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <form method="POST" action="{{ route('admin.medication-administration.orders.prn', $order) }}" class="modal-content js-med-admin-form">
                    @csrf
                    <div class="modal-header"><h5 class="modal-title">Administer PRN / SOS Medication</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                    <div class="modal-body">
                        <div class="alert alert-light border mb-3"><strong>{{ $prn['product_name'] }}</strong> - {{ trim(($prn['dose'] ?? '').' '.($prn['dose_unit'] ?? '')) }} {{ $prn['route'] ? '- '.strtoupper($prn['route']) : '' }} - {{ $prn['frequency'] }}</div>
                        <div class="js-med-admin-errors alert alert-danger d-none"></div>
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label">Status <span class="text-danger">*</span></label><select name="status" class="form-select med-status" required><option value="GIVEN">Given</option><option value="HELD">Held</option><option value="REFUSED">Refused</option><option value="MISSED">Missed</option><option value="SKIPPED">Skipped</option><option value="NOT_GIVEN">Not given</option></select></div>
                            <div class="col-md-4"><label class="form-label">Actual Time</label><input type="datetime-local" name="administered_at" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}"></div>
                            <div class="col-md-4"><label class="form-label">Dose Given</label><input name="dose_given" class="form-control" value="{{ trim(($prn['dose'] ?? '').' '.($prn['dose_unit'] ?? '')) }}" required></div>
                            <div class="col-md-4"><label class="form-label">Route</label><input name="route" class="form-control" value="{{ $prn['route'] }}"></div>
                            <div class="col-md-4"><label class="form-label">Source Stock</label><select name="source_stock_type" class="form-select med-source-stock"><option value="PATIENT_DISPENSED_STOCK">Patient dispensed stock</option><option value="WARD_STOCK">Ward stock</option><option value="EMERGENCY_STOCK">Emergency stock</option><option value="OTHER_DEPARTMENT_STOCK">Other department stock</option></select></div>
                            <div class="col-md-4 med-stock-location-wrap d-none"><label class="form-label">Stock Location</label><select name="stock_location_id" class="form-select"><option value="">Select location</option>@foreach($stockLocations as $location)<option value="{{ $location->id }}">{{ $location->name }}</option>@endforeach</select></div>
                            <div class="col-md-6"><label class="form-label">Reason / Symptom <span class="text-danger">*</span></label><textarea name="reason" rows="2" class="form-control" required></textarea></div>
                            <div class="col-md-6 med-reason-wrap d-none"><label class="form-label">Reason Not Given</label><textarea name="reason_not_given" rows="2" class="form-control"></textarea></div>
                            <div class="col-md-6"><label class="form-label">Patient Response / Reaction</label><textarea name="reaction" rows="2" class="form-control"></textarea></div>
                            <div class="col-md-6"><label class="form-label">Notes</label><textarea name="notes" rows="2" class="form-control"></textarea></div>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Save PRN Administration</button></div>
                </form>
            </div>
        </div>
    @endforeach
</div>