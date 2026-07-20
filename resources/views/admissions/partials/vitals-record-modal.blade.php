<div class="modal fade" id="recordVitalsModal" tabindex="-1" aria-labelledby="recordVitalsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <form method="POST" action="{{ $workspaceRoutes->route('admin.admissions.vitals.store', $admission) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="recordVitalsModalLabel"><i class="ti ti-activity-heartbeat me-1"></i>{{ __('admissions.record_vitals') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
            </div>
            <div class="modal-body">
                @if($admission->status->value === 'admitted')
                    <div class="row g-3">
                        <div class="col-6 col-md-3">
                            <label class="form-label">{{ __('admissions.bp_systolic') }}</label>
                            <div class="input-group"><input type="number" name="blood_pressure_systolic" class="form-control" placeholder="120" min="0" max="300"><span class="input-group-text">mmHg</span></div>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label">{{ __('admissions.bp_diastolic') }}</label>
                            <div class="input-group"><input type="number" name="blood_pressure_diastolic" class="form-control" placeholder="80" min="0" max="200"><span class="input-group-text">mmHg</span></div>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label">{{ __('admissions.heart_rate') }}</label>
                            <div class="input-group"><input type="number" name="heart_rate" class="form-control" placeholder="72" min="0" max="300"><span class="input-group-text">bpm</span></div>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label">{{ __('admissions.temperature') }}</label>
                            <div class="input-group"><input type="number" name="temperature" class="form-control" placeholder="36.6" step="0.1" min="30" max="45"><span class="input-group-text">&deg;C</span></div>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label">{{ __('admissions.resp_rate') }}</label>
                            <div class="input-group"><input type="number" name="respiratory_rate" class="form-control" placeholder="16" min="0" max="60"><span class="input-group-text">/min</span></div>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label">{{ __('admissions.spo2_col') }}</label>
                            <div class="input-group"><input type="number" name="spo2" class="form-control" placeholder="98" step="0.1" min="0" max="100"><span class="input-group-text">%</span></div>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label">{{ __('admissions.weight') }}</label>
                            <div class="input-group"><input type="number" name="weight" class="form-control" placeholder="70" step="0.1" min="0" max="500"><span class="input-group-text">kg</span></div>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label">{{ __('admissions.blood_sugar') }}</label>
                            <div class="input-group"><input type="number" name="blood_sugar" class="form-control" placeholder="5.0" step="0.1" min="0"><span class="input-group-text">mmol/L</span></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('admissions.recorded_at') }}</label>
                            <input type="datetime-local" name="recorded_at" class="form-control" value="{{ old('recorded_at', now()->format('Y-m-d\TH:i')) }}">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">{{ __('admissions.notes') }}</label>
                            <input type="text" name="notes" class="form-control" maxlength="1000" placeholder="{{ __('admissions.observations_ph') }}" value="{{ old('notes') }}">
                        </div>
                    </div>
                @else
                    <p class="text-muted mb-0">{{ __('admissions.vitals_disabled') }}</p>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                <button type="submit" class="btn btn-primary" @disabled($admission->status->value !== 'admitted')>
                    <i class="ti ti-device-floppy me-1"></i>{{ __('admissions.save_vitals') }}
                </button>
            </div>
        </form>
    </div>
</div>
