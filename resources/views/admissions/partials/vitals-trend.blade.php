@php
    $vitals = $admission->visit->vitals->sortBy('recorded_at')->values();
    $latestVitals = $vitals->last();
    $historyVitals = $vitals->sortByDesc('recorded_at');
@endphp

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between gap-2">
        <div class="d-flex align-items-center gap-2">
            <h5 class="card-title mb-0"><i class="ti ti-activity-heartbeat me-1"></i>{{ __('admissions.vitals_trend') }}</h5>
            <span class="badge bg-light text-dark">{{ trans_choice('admissions.readings_count', $vitals->count(), ['count' => $vitals->count()]) }}</span>
        </div>
        @if($admission->status->value === 'admitted')
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#recordVitalsModal">
                <i class="ti ti-plus me-1"></i>{{ __('admissions.record_vitals') }}
            </button>
        @endif
    </div>
    <div class="card-body">
        @if($vitals->isNotEmpty())
            <div class="row g-2 mb-3">
                <div class="col-6 col-md-4 col-xl">
                    <div class="vitals-trend-card">
                        <div class="d-flex align-items-baseline justify-content-between">
                            <span class="vitals-val">{{ $latestVitals?->blood_pressure ?? '-' }}</span>
                            <span class="vitals-label">BP</span>
                        </div>
                        <div class="vitals-spark-wrap"><canvas id="vitalsSparkBp"></canvas></div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <div class="vitals-trend-card">
                        <div class="d-flex align-items-baseline justify-content-between">
                            <span class="vitals-val">{{ $latestVitals?->heart_rate ?? '-' }}</span>
                            <span class="vitals-label">HR</span>
                        </div>
                        <div class="vitals-spark-wrap"><canvas id="vitalsSparkHr"></canvas></div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <div class="vitals-trend-card">
                        <div class="d-flex align-items-baseline justify-content-between">
                            <span class="vitals-val">{{ $latestVitals?->respiratory_rate ?? '-' }}</span>
                            <span class="vitals-label">RR</span>
                        </div>
                        <div class="vitals-spark-wrap"><canvas id="vitalsSparkRr"></canvas></div>
                    </div>
                </div>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-6 col-md-4 col-xl">
                    <div class="vitals-trend-card">
                        <div class="d-flex align-items-baseline justify-content-between">
                            <span class="vitals-val">{{ $latestVitals?->spo2 ?? '-' }}</span>
                            <span class="vitals-label">SpO2</span>
                        </div>
                        <div class="vitals-spark-wrap"><canvas id="vitalsSparkSpo2"></canvas></div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <div class="vitals-trend-card">
                        <div class="d-flex align-items-baseline justify-content-between">
                            <span class="vitals-val">{{ $latestVitals?->temperature ?? '-' }}</span>
                            <span class="vitals-label">{{ __('admissions.temp_col') }}</span>
                        </div>
                        <div class="vitals-spark-wrap"><canvas id="vitalsSparkTemp"></canvas></div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>{{ __('admissions.date_time_col') }}</th>
                            <th>{{ __('admissions.bp_col') }}</th>
                            <th>{{ __('admissions.hr_col') }}</th>
                            <th>{{ __('admissions.rr_col') }}</th>
                            <th>{{ __('admissions.temp_col') }}</th>
                            <th>{{ __('admissions.spo2_col') }}</th>
                            <th>{{ __('admissions.sugar_col') }}</th>
                            <th>{{ __('admissions.by_col') }}</th>
                            <th class="text-end">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($historyVitals as $vital)
                            <tr>
                                <td><div>{{ $vital->recorded_at->format('d M H:i') }}</div></td>
                                <td>{{ $vital->blood_pressure ?? '-' }}</td>
                                <td>{{ $vital->heart_rate ?? '-' }}</td>
                                <td>{{ $vital->respiratory_rate ?? '-' }}</td>
                                <td>{{ $vital->temperature ?? '-' }}</td>
                                <td>{{ $vital->spo2 ?? '-' }}</td>
                                <td>{{ $vital->blood_sugar ?? '-' }}</td>
                                <td>{{ $vital->recordedBy->name ?? '-' }}</td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editVitalsModal{{ $vital->id }}" title="{{ __('common.edit') }}">
                                        <i class="ti ti-edit"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @foreach($historyVitals as $vital)
                <div class="modal fade" id="editVitalsModal{{ $vital->id }}" tabindex="-1" aria-labelledby="editVitalsModalLabel{{ $vital->id }}" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-centered">
                        <form method="POST" action="{{ $workspaceRoutes->route('admin.admissions.vitals.update', [$admission, $vital]) }}" class="modal-content">
                            @csrf
                            @method('PATCH')
                            <div class="modal-header">
                                <h5 class="modal-title" id="editVitalsModalLabel{{ $vital->id }}"><i class="ti ti-edit me-1"></i>{{ __('common.edit') }} {{ __('admissions.vitals') }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-6 col-md-3">
                                        <label class="form-label">{{ __('admissions.bp_systolic') }}</label>
                                        <div class="input-group"><input type="number" name="blood_pressure_systolic" class="form-control" value="{{ old('blood_pressure_systolic', $vital->blood_pressure_systolic) }}" min="0" max="300"><span class="input-group-text">mmHg</span></div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label">{{ __('admissions.bp_diastolic') }}</label>
                                        <div class="input-group"><input type="number" name="blood_pressure_diastolic" class="form-control" value="{{ old('blood_pressure_diastolic', $vital->blood_pressure_diastolic) }}" min="0" max="200"><span class="input-group-text">mmHg</span></div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label">{{ __('admissions.heart_rate') }}</label>
                                        <div class="input-group"><input type="number" name="heart_rate" class="form-control" value="{{ old('heart_rate', $vital->heart_rate) }}" min="0" max="300"><span class="input-group-text">bpm</span></div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label">{{ __('admissions.temperature') }}</label>
                                        <div class="input-group"><input type="number" name="temperature" class="form-control" value="{{ old('temperature', $vital->temperature) }}" step="0.1" min="30" max="45"><span class="input-group-text">&deg;C</span></div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label">{{ __('admissions.resp_rate') }}</label>
                                        <div class="input-group"><input type="number" name="respiratory_rate" class="form-control" value="{{ old('respiratory_rate', $vital->respiratory_rate) }}" min="0" max="60"><span class="input-group-text">/min</span></div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label">{{ __('admissions.spo2_col') }}</label>
                                        <div class="input-group"><input type="number" name="spo2" class="form-control" value="{{ old('spo2', $vital->spo2) }}" step="0.1" min="0" max="100"><span class="input-group-text">%</span></div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label">{{ __('admissions.weight') }}</label>
                                        <div class="input-group"><input type="number" name="weight" class="form-control" value="{{ old('weight', $vital->weight) }}" step="0.1" min="0" max="500"><span class="input-group-text">kg</span></div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label">{{ __('admissions.blood_sugar') }}</label>
                                        <div class="input-group"><input type="number" name="blood_sugar" class="form-control" value="{{ old('blood_sugar', $vital->blood_sugar) }}" step="0.1" min="0"><span class="input-group-text">mmol/L</span></div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">{{ __('admissions.recorded_at') }}</label>
                                        <input type="datetime-local" name="recorded_at" class="form-control" value="{{ old('recorded_at', $vital->recorded_at?->format('Y-m-d\TH:i')) }}">
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label">{{ __('admissions.notes') }}</label>
                                        <input type="text" name="notes" class="form-control" maxlength="1000" placeholder="{{ __('admissions.observations_ph') }}" value="{{ old('notes', $vital->notes) }}">
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-device-floppy me-1"></i>{{ __('common.save_changes') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endforeach
        @else
            <div class="text-center py-4 text-muted"><i class="ti ti-activity-off fs-1 d-block mb-2"></i>{{ __('admissions.no_vitals_yet') }}</div>
        @endif
    </div>
</div>

@include('admissions.partials.vitals-record-modal')
