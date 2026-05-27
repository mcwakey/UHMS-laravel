@php
    $order = $order ?? $schedule->medicationOrder;
    $modalId = $modalId ?? 'administer-dose-' . $schedule->id;
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form method="POST" action="{{ route('admin.medication-administration.schedules.administer', $schedule) }}" class="modal-content js-med-admin-form">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Record Medication Administration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-light border mb-3">
                    <div class="d-flex flex-wrap gap-3">
                        <div><span class="text-muted small d-block">Patient</span><strong>{{ $schedule->patient->full_name ?? $order->patient->full_name ?? 'Patient' }}</strong></div>
                        <div><span class="text-muted small d-block">Medication</span><strong>{{ $order->display_name }}</strong></div>
                        <div><span class="text-muted small d-block">Scheduled</span><strong>{{ $schedule->scheduled_at?->format('d M Y H:i') }}</strong></div>
                        <div><span class="text-muted small d-block">Dose</span><strong>{{ trim(($schedule->dose ?? $order->dose).' '.($schedule->dose_unit ?? $order->dose_unit)) ?: 'Dose' }}</strong></div>
                        <div><span class="text-muted small d-block">Route</span><strong>{{ strtoupper($schedule->route ?? $order->route ?? '—') }}</strong></div>
                    </div>
                </div>

                <div class="js-med-admin-errors alert alert-danger d-none"></div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select med-status" required>
                            <option value="GIVEN">Given</option>
                            <option value="HELD">Held</option>
                            <option value="REFUSED">Refused</option>
                            <option value="MISSED">Missed</option>
                            <option value="SKIPPED">Skipped</option>
                            <option value="NOT_GIVEN">Not given</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Actual Time</label>
                        <input type="datetime-local" name="administered_at" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Dose Given</label>
                        <input type="text" name="dose_given" class="form-control" value="{{ trim(($schedule->dose ?? $order->dose).' '.($schedule->dose_unit ?? $order->dose_unit)) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Route</label>
                        <input type="text" name="route" class="form-control" value="{{ $schedule->route ?? $order->route }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Source Stock</label>
                        <select name="source_stock_type" class="form-select med-source-stock">
                            <option value="PATIENT_DISPENSED_STOCK">Patient dispensed stock</option>
                            <option value="WARD_STOCK">Ward stock</option>
                            <option value="EMERGENCY_STOCK">Emergency stock</option>
                            <option value="OTHER_DEPARTMENT_STOCK">Other department stock</option>
                        </select>
                    </div>
                    <div class="col-md-4 med-stock-location-wrap d-none">
                        <label class="form-label">Stock Location</label>
                        <select name="stock_location_id" class="form-select">
                            <option value="">Select location</option>
                            @foreach(($stockLocations ?? collect()) as $location)
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 med-reason-wrap d-none">
                        <label class="form-label">Reason Not Given</label>
                        <textarea name="reason_not_given" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Patient Reaction</label>
                        <textarea name="reaction" class="form-control" rows="2" placeholder="Optional reaction or tolerance note"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Nursing Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Optional clinical notes"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-device-floppy me-1"></i>Save Administration
                </button>
            </div>
        </form>
    </div>
</div>
