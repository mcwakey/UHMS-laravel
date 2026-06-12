@php
    $isReschedule = ($mode ?? 'schedule') === 'reschedule';
    $route = $isReschedule ? 'admin.theatre.reschedule' : 'admin.theatre.schedule';
    $title = $isReschedule ? 'Reschedule Procedure' : 'Schedule Procedure';
    $existing = $procedure->schedule;
    $durationMinutes = old('expected_duration_minutes', $existing?->expected_duration_minutes ?? (($existing?->scheduled_start && $existing?->scheduled_end) ? $existing->scheduled_start->diffInMinutes($existing->scheduled_end) : 60));
@endphp
<div class="card shadow-sm mb-3">
    <div class="card-header"><strong>{{ $title }}</strong></div>
    <div class="card-body">
        <form method="POST" action="{{ route($route, $procedure) }}">
            @csrf
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="form-label small">Theatre Room <span class="text-danger">*</span></label>
                    <select name="theatre_room_id" class="form-select form-select-sm" required>
                        <option value="">Select…</option>
                        @foreach ($theatreRooms as $room)
                            <option value="{{ $room->id }}" @selected((string) old('theatre_room_id', $existing?->theatre_room_id)===(string) $room->id)>
                                {{ $room->code }} - {{ $room->name }} ({{ $room->status?->translatedLabel() }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Start <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="scheduled_start" class="form-control form-control-sm" required
                        value="{{ old('scheduled_start', optional($existing?->scheduled_start)->format('Y-m-d\TH:i')) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">End</label>
                    <input type="datetime-local" name="scheduled_end" class="form-control form-control-sm"
                        value="{{ old('scheduled_end', optional($existing?->scheduled_end)->format('Y-m-d\TH:i')) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Duration (minutes)</label>
                    <input type="number" min="1" max="1440" name="expected_duration_minutes" class="form-control form-control-sm" value="{{ $durationMinutes }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Surgeon</label>
                    <select name="surgeon_id" class="form-select form-select-sm">
                        <option value="">Select…</option>
                        @foreach ($clinicians as $u)
                            <option value="{{ $u->id }}" @selected($existing?->surgeon_id==$u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Anaesthetist</label>
                    <select name="anaesthetist_id" class="form-select form-select-sm">
                        <option value="">Select…</option>
                        @foreach ($clinicians as $u)
                            <option value="{{ $u->id }}" @selected($existing?->anaesthetist_id==$u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Assistant Surgeon</label>
                    <select name="assistant_surgeon_id" class="form-select form-select-sm">
                        <option value="">Select…</option>
                        @foreach ($clinicians as $u)
                            <option value="{{ $u->id }}" @selected($existing?->assistant_surgeon_id==$u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label small">Required Equipment</label>
                    <input name="required_equipment" class="form-control form-control-sm" value="{{ $existing?->required_equipment }}">
                </div>
                <div class="col-md-12">
                    <label class="form-label small">Notes</label>
                    <textarea name="notes" class="form-control form-control-sm" rows="2">{{ $existing?->notes }}</textarea>
                </div>
                @if ($isReschedule)
                    <div class="col-md-12">
                        <label class="form-label small">Reschedule reason <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control form-control-sm" rows="2" required></textarea>
                    </div>
                @endif
                @can('theatre.schedule.override')
                    <div class="col-md-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="override_room_conflict" id="override_room_conflict_{{ $mode }}" value="1" @checked(old('override_room_conflict'))>
                            <label class="form-check-label" for="override_room_conflict_{{ $mode }}">Override room conflict or block</label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small">Override reason</label>
                        <textarea name="override_reason" class="form-control form-control-sm" rows="2">{{ old('override_reason') }}</textarea>
                    </div>
                @endcan
            </div>
            <button class="btn btn-primary mt-3">{{ $isReschedule ? 'Reschedule' : 'Schedule' }}</button>
        </form>
    </div>
</div>
