@props([
    'visit',
    'title' => null,
])

{{--
    Visit Information Card
    ----------------------
    Same design family as <x-patient-card> (rounded, soft shadow, soft badges,
    scoped CSS) but its own identity: a gradient top-accent strip, a status-led
    header, and definition-style rows (label left / value right) rather than the
    patient card's avatar + icon-chip rows.
--}}
@php
    $title ??= __('triage.visit_status');
    $currentDepartment = $visit?->currentDepartment;
    $doctor = $visit?->currentConsultationDoctor();
    $visitType = $visit?->visit_type;
    $priority = $visit?->priority;


@endphp
 <!-- $rows = [
        ['icon' => 'ti-calendar-event',    'label' => __('common.date'),       'value' => $visit->visit_date?->translatedFormat('d M Y') ?? '—'],
        ['icon' => 'ti-stethoscope',       'label' => __('common.type'),       'value' => $visitType?->translatedLabel() ?? '—'],
        ['icon' => 'ti-building-hospital', 'label' => __('common.department'), 'value' => $currentDepartment?->name ?? '—'],
        ['icon' => 'ti-user',              'label' => __('common.doctor'),     'value' => $doctor?->full_name ?? '—'],
    ]; -->
@if($visit !== null)
<div {{ $attributes->merge(['class' => 'card visit-card shadow-sm border-0 mb-3']) }}>
    <div class="visit-card__accent"></div>

    <div class="visit-card__header">
        <span class="visit-card__label">
            <i class="ti ti-clipboard-text"></i> {{ $visit->visit_number }}
        </span>
        @if($priority)
            <x-status-badge :status="$priority" />
        @else
            <span class="visit-card__value">—</span>
        @endif
        <span class="badge badge-soft-primary fs-11 fw-medium">{{ $visitType?->translatedLabel() ?? '—' }}</span>

        <div class="d-flex align-items-center gap-2 flex-wrap mt-2">
            <x-status-badge :status="$visit->status" class="fs-12 px-3 py-2" />
            <!-- <span class="badge badge-soft-secondary fs-11 fw-medium">{{ $visit->visit_number }}</span> -->
            @if($visit->visit_source)
                <span class="visit-card__chip">
                    <i class="ti ti-arrow-guide"></i>{{ __('visit_flow.source.'.$visit->visit_source) }}
                </span>
            @endif
            @if($visit->attendance_class)
                <span class="visit-card__chip">
                    <i class="ti ti-user-check"></i>{{ __('visit_flow.attendance_class.'.$visit->attendance_class) }}
                </span>
            @endif
        </div>

        @if($visit->visit_source || $visit->attendance_class)
        <div class="d-flex flex-wrap gap-1 mt-2">
            <span class="visit-card__label">
                <i class="ti ti-calendar-event"></i>{{ __('common.date') }}: {{ $visit->visit_date?->translatedFormat('d M Y') ?? '—' }}
            </span>
        </div>
        @endif
    </div>
</div>
@endif

@once
@push('styles')
<style>
    .visit-card {
        border-radius: 14px;
        overflow: hidden;
        background: var(--white, #fff);
    }
    .visit-card__accent {
        height: 3px;
        background: linear-gradient(90deg, #2E37A4 0%, #4C56C5 55%, #7d84e0 100%);
    }
    .visit-card__header {
        padding: 16px;
        border-bottom: 1px solid var(--border-color, #E7E8EB);
    }
    .visit-card__eyebrow {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: .7rem;
        font-weight: 600;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--body-color, #6C7688);
    }
    .visit-card__chip {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 3px 9px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 500;
        color: var(--body-color, #6C7688);
        background: rgba(46,55,164,.05);
        border: 1px solid var(--border-color, #E7E8EB);
    }
    .visit-card__rows { padding: 6px 16px 14px; }
    .visit-card__row {
        display: flex; align-items: center; justify-content: space-between; gap: 12px;
        padding: 11px 0;
        border-bottom: 1px solid var(--border-color, #EEF0F3);
    }
    .visit-card__row:last-child { border-bottom: 0; }
    .visit-card__label {
        display: inline-flex; align-items: center; gap: 8px;
        font-size: 13px;
        color: var(--body-color, #6C7688);
    }
    .visit-card__label i { font-size: 15px; opacity: .65; }
    .visit-card__value {
        font-weight: 600;
        font-size: 13px;
        text-align: right;
        color: var(--bs-body-color, #212529);
        min-width: 0;
    }
    .min-w-0 { min-width: 0; }
</style>
@endpush
@endonce
