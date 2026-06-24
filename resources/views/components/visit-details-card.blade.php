@props([
    'visit' => null,
    'title' => null,
    'visitDateId' => 'visitDate',
    'schedulingFieldsId' => 'schedulingFields',
    'schedulingHintId' => 'schedulingHint',
    'showSchedulingHint' => true,
    'showSchedulingFields' => false,
    'visitTypeValue' => null,
    'priorityValue' => null,
    'visitDateValue' => null,
    'startTimeValue' => null,
    'endTimeValue' => null,
    'consultationModeValue' => null,
    'chiefComplaintValue' => null,
    'notesValue' => null,
    'chiefComplaintPlaceholder' => null,
    'notesPlaceholder' => null,
    'visitTypeLabel' => null,
    'visitTypePlaceholder' => null,
    'priorityLabel' => null,
    'visitDateLabel' => null,
    'visitDateFieldName' => 'visit_date',
    'visitDateRequired' => false,
    'visitDateMin' => null,
    'startTimeLabel' => null,
    'startTimeRequired' => false,
    'endTimeLabel' => null,
    'endTimeHint' => null,
    'consultationModeLabel' => null,
    'chiefComplaintLabel' => null,
    'notesLabel' => null,
    'notesRows' => 3,
    'stackTextareas' => false,
])

@php
    $formatDateValue = function ($value) {
        if (! $value) return null;
        return $value instanceof \Carbon\CarbonInterface ? $value->format('Y-m-d') : (string) $value;
    };
    $formatTimeValue = function ($value) {
        if (! $value) return null;
        if ($value instanceof \Carbon\CarbonInterface) return $value->format('H:i');
        if (is_string($value) && preg_match('/^\d{2}:\d{2}/', $value)) return substr($value, 0, 5);
        try {
            return \Carbon\Carbon::parse($value)->format('H:i');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    };

    $title ??= __('visits.visit_details_heading');
    $visitTypeValue ??= $visit?->visit_type?->value ?? null;
    $priorityValue ??= $visit?->priority?->value ?? 'normal';
    $visitDateValue = $formatDateValue($visitDateValue ?? $visit?->visit_date ?? date('Y-m-d'));
    $startTimeValue = $formatTimeValue($startTimeValue ?? $visit?->start_time ?? null);
    $endTimeValue = $formatTimeValue($endTimeValue ?? $visit?->end_time ?? null);
    $consultationModeValue ??= $visit?->consultation_mode?->value ?? 'in_person';
    $chiefComplaintValue ??= $visit?->chief_complaint ?? null;
    $notesValue ??= $visit?->notes ?? null;
    $chiefComplaintPlaceholder ??= __('visits.complaint_placeholder');
    $notesPlaceholder ??= __('visits.notes_placeholder');
    $visitTypeLabel ??= __('visits.visit_type_label');
    $visitTypePlaceholder ??= __('visits.select_type_opt');
    $priorityLabel ??= __('visits.priority_label');
    $visitDateLabel ??= __('visits.visit_date_label');
    $startTimeLabel ??= __('visits.start_time_label');
    $endTimeLabel ??= __('visits.end_time_label');
    $consultationModeLabel ??= __('visits.consultation_mode_label');
    $chiefComplaintLabel ??= __('visits.chief_complaint_field');
    $notesLabel ??= __('visits.notes_field');
    $showSchedulingFields = $showSchedulingFields || (bool) $startTimeValue || (bool) $endTimeValue;
@endphp

<div {{ $attributes->merge(['class' => 'card']) }}>
    <div class="card-header">
        <h5 class="fw-bold mb-0"><i class="ti ti-clipboard-text me-1"></i>{{ $title }}</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">{{ $visitTypeLabel }} <span class="text-danger">*</span></label>
                <select name="visit_type" class="form-select @error('visit_type') is-invalid @enderror" required>
                    <option value="">{{ $visitTypePlaceholder }}</option>
                    @foreach(\App\Enums\VisitType::cases() as $type)
                        <option value="{{ $type->value }}" {{ old('visit_type', $visitTypeValue) == $type->value ? 'selected' : '' }}>{{ $type->translatedLabel() }}</option>
                    @endforeach
                </select>
                @error('visit_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">{{ $priorityLabel }} <span class="text-danger">*</span></label>
                <select name="priority" class="form-select @error('priority') is-invalid @enderror" required>
                    @foreach(\App\Enums\Priority::cases() as $priority)
                        <option value="{{ $priority->value }}" {{ old('priority', $priorityValue) == $priority->value ? 'selected' : '' }}>{{ $priority->translatedLabel() }}</option>
                    @endforeach
                </select>
                @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">{{ $visitDateLabel }} @if($visitDateRequired)<span class="text-danger">*</span>@endif</label>
                <input type="date"
                       name="{{ $visitDateFieldName }}"
                       class="form-control @error($visitDateFieldName) is-invalid @enderror"
                       value="{{ old($visitDateFieldName, $visitDateValue) }}"
                       id="{{ $visitDateId }}"
                       @if($visitDateMin) min="{{ $visitDateMin }}" @endif
                       @if($visitDateRequired) required @endif>
                @error($visitDateFieldName)<div class="invalid-feedback">{{ $message }}</div>@enderror
                @if($showSchedulingHint)
                    <small class="text-muted" id="{{ $schedulingHintId }}">{{ __('visits.today_scheduling_hint') }}</small>
                @endif
            </div>
        </div>

        <div class="row" id="{{ $schedulingFieldsId }}" style="{{ $showSchedulingFields ? '' : 'display: none;' }}">
            <div class="col-md-4 mb-3">
                <label class="form-label">{{ $startTimeLabel }} @if($startTimeRequired)<span class="text-danger">*</span>@endif</label>
                <input type="time" name="start_time" class="form-control @error('start_time') is-invalid @enderror" value="{{ old('start_time', $startTimeValue) }}" @if($startTimeRequired) required @endif>
                @error('start_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">{{ $endTimeLabel }}</label>
                <input type="time" name="end_time" class="form-control @error('end_time') is-invalid @enderror" value="{{ old('end_time', $endTimeValue) }}">
                @if($endTimeHint)<small class="text-muted">{{ $endTimeHint }}</small>@endif
                @error('end_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">{{ $consultationModeLabel }}</label>
                <select name="consultation_mode" class="form-select @error('consultation_mode') is-invalid @enderror">
                    @foreach(\App\Enums\ConsultationMode::cases() as $mode)
                        <option value="{{ $mode->value }}" {{ old('consultation_mode', $consultationModeValue) == $mode->value ? 'selected' : '' }}>{{ $mode->translatedLabel() }}</option>
                    @endforeach
                </select>
                @error('consultation_mode')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="row">
            <div class="{{ $stackTextareas ? 'col-12' : 'col-md-6' }} mb-3">
                <label class="form-label">{{ $chiefComplaintLabel }}</label>
                <textarea name="chief_complaint" class="form-control @error('chief_complaint') is-invalid @enderror" rows="3" placeholder="{{ $chiefComplaintPlaceholder }}">{{ old('chief_complaint', $chiefComplaintValue) }}</textarea>
                @error('chief_complaint')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="{{ $stackTextareas ? 'col-12' : 'col-md-6' }} mb-3">
                <label class="form-label">{{ $notesLabel }}</label>
                <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="{{ $notesRows }}" placeholder="{{ $notesPlaceholder }}">{{ old('notes', $notesValue) }}</textarea>
                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>
