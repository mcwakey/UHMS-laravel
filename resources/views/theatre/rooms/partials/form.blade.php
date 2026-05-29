@php
    $room ??= null;
@endphp
<div class="row g-2">
    <div class="col-md-6">
        <label class="form-label small">Room name <span class="text-danger">*</span></label>
        <input name="name" class="form-control form-control-sm" value="{{ old('name', $room?->name) }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label small">Room code <span class="text-danger">*</span></label>
        <input name="code" class="form-control form-control-sm" value="{{ old('code', $room?->code) }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label small">Capacity</label>
        <input type="number" min="0" name="capacity" class="form-control form-control-sm" value="{{ old('capacity', $room?->capacity) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label small">Department</label>
        <select name="department_id" class="form-select form-select-sm">
            <option value="">None</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}" @selected((string) old('department_id', $room?->department_id) === (string) $department->id)>{{ $department->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label small">Room type <span class="text-danger">*</span></label>
        <select name="room_type" class="form-select form-select-sm" required>
            @foreach ($roomTypes as $type)
                <option value="{{ $type->value }}" @selected(old('room_type', $room?->room_type?->value ?? 'PROCEDURE_ROOM') === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label small">Status <span class="text-danger">*</span></label>
        <select name="status" class="form-select form-select-sm" required>
            @foreach ($roomStatuses as $status)
                <option value="{{ $status->value }}" @selected(old('status', $room?->status?->value ?? 'AVAILABLE') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-8">
        <label class="form-label small">Location</label>
        <input name="location" class="form-control form-control-sm" value="{{ old('location', $room?->location) }}">
    </div>
    <div class="col-md-4 d-flex align-items-end">
        <div class="form-check mb-1">
            <input type="hidden" name="is_active" value="0">
            <input class="form-check-input" type="checkbox" name="is_active" id="active_room_{{ $room?->id ?? 'new' }}" value="1" @checked(old('is_active', $room?->is_active ?? true))>
            <label class="form-check-label" for="active_room_{{ $room?->id ?? 'new' }}">Active</label>
        </div>
    </div>
    <div class="col-12">
        <label class="form-label small">Notes</label>
        <textarea name="notes" class="form-control form-control-sm" rows="3">{{ old('notes', $room?->notes) }}</textarea>
    </div>
</div>