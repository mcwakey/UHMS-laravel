@php
    $order = $order ?? $schedule->medicationOrder;
    $modalId = $modalId ?? 'administer-dose-' . $schedule->id;
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form method="POST" action="{{ $workspaceRoutes->route('admin.medication-administration.schedules.administer', $schedule) }}" class="modal-content js-med-admin-form">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">{{ __('medication_administration.record_medication_administration') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-light border mb-3">
                    <div class="d-flex flex-wrap gap-3">
                        <div><span class="text-muted small d-block">{{ __('medication_administration.patient') }}</span><strong>{{ $schedule->patient->full_name ?? $order->patient->full_name ?? __('medication_administration.patient') }}</strong></div>
                        <div><span class="text-muted small d-block">{{ __('medication_administration.medication') }}</span><strong>{{ $order->display_name }}</strong></div>
                        <div><span class="text-muted small d-block">{{ __('medication_administration.scheduled') }}</span><strong>{{ $schedule->scheduled_at?->format('d M Y H:i') }}</strong></div>
                        <div><span class="text-muted small d-block">{{ __('medication_administration.dose') }}</span><strong>{{ trim(($schedule->dose ?? $order->dose).' '.($schedule->dose_unit ?? $order->dose_unit)) ?: __('medication_administration.dose') }}</strong></div>
                        <div><span class="text-muted small d-block">{{ __('medication_administration.route') }}</span><strong>{{ strtoupper($schedule->route ?? $order->route ?? '—') }}</strong></div>
                    </div>
                </div>

                <div class="js-med-admin-errors alert alert-danger d-none"></div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('medication_administration.status') }} <span class="text-danger">*</span></label>
                        <select name="status" class="form-select med-status" required>
                            <option value="GIVEN">{{ __('statuses.mar.given') }}</option>
                            <option value="HELD">{{ __('statuses.mar.held') }}</option>
                            <option value="REFUSED">{{ __('statuses.mar.refused') }}</option>
                            <option value="MISSED">{{ __('statuses.mar.missed') }}</option>
                            <option value="SKIPPED">{{ __('statuses.mar.skipped') }}</option>
                            <option value="NOT_GIVEN">{{ __('statuses.mar.not_given') }}</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('medication_administration.actual_time') }}</label>
                        <input type="datetime-local" name="administered_at" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('medication_administration.dose_given') }}</label>
                        <input type="text" name="dose_given" class="form-control" value="{{ trim(($schedule->dose ?? $order->dose).' '.($schedule->dose_unit ?? $order->dose_unit)) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('medication_administration.route') }}</label>
                        <input type="text" name="route" class="form-control" value="{{ $schedule->route ?? $order->route }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('medication_administration.source_stock') }}</label>
                        <select name="source_stock_type" class="form-select med-source-stock">
                            <option value="PATIENT_DISPENSED_STOCK">{{ __('medication_administration.patient_dispensed_stock') }}</option>
                            <option value="WARD_STOCK">{{ __('medication_administration.ward_stock') }}</option>
                            <option value="EMERGENCY_STOCK">{{ __('medication_administration.emergency_stock') }}</option>
                            <option value="OTHER_DEPARTMENT_STOCK">{{ __('medication_administration.other_department_stock') }}</option>
                        </select>
                    </div>
                    <div class="col-md-4 med-stock-location-wrap d-none">
                        <label class="form-label">{{ __('medication_administration.stock_location') }}</label>
                        <select name="stock_location_id" class="form-select">
                            <option value="">{{ __('medication_administration.select_location') }}</option>
                            @foreach(($stockLocations ?? collect()) as $location)
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 med-reason-wrap d-none">
                        <label class="form-label">{{ __('medication_administration.reason_not_given') }}</label>
                        <textarea name="reason_not_given" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('medication_administration.patient_reaction') }}</label>
                        <textarea name="reaction" class="form-control" rows="2" placeholder="{{ __('medication_administration.reaction_placeholder') }}"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('medication_administration.nursing_notes') }}</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="{{ __('medication_administration.notes_placeholder') }}"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-device-floppy me-1"></i>{{ __('medication_administration.save_administration') }}
                </button>
            </div>
        </form>
    </div>
</div>
