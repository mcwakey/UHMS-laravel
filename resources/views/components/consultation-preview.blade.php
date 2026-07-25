@props([
    'visit',
    'generatedAt',
    'sessions' => collect(),
    'contributors' => [],
    'sessionSummaries' => collect(),
    'labRequests' => collect(),
    'procedureRequests' => collect(),
])

@php
    $sessions = collect($sessions);
    $sessionSummaries = collect($sessionSummaries);
    $labRequests = collect($labRequests);
    $procedureRequests = collect($procedureRequests);
@endphp

@once
<style>
    .consult-doc {
        max-width: 1000px;
        margin: 0 auto;
        background: #fff;
        padding: 28px 36px;
        box-shadow: 0 2px 12px rgba(0,0,0,.05);
        border: 1px solid #e9ecef;
        border-radius: 8px;
        font-size: .92rem;
        color: #1f2937;
        line-height: 1.55;
    }
    .consult-doc h1.doc-title {
        font-size: 1.45rem;
        font-weight: 700;
        letter-spacing: .04em;
        margin: 0;
        color: #0f172a;
    }
    .consult-doc .doc-meta { font-size: .78rem; color: #6b7280; }
    .consult-doc .doc-section { margin-bottom: 18px; }
    .consult-doc .doc-section h2 {
        font-size: 1rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .07em;
        color: #0f172a;
        border-bottom: 2px solid #0d6efd;
        padding-bottom: 4px;
        margin-bottom: 10px;
    }
    .consult-doc .doc-section h3 {
        font-size: .88rem;
        font-weight: 600;
        color: #334155;
        margin: 7px 0 3px;
    }
    .consult-doc .info-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0,1fr));
        gap: 6px 18px;
        font-size: .85rem;
    }
    .consult-doc .info-grid .lbl { color: #6b7280; font-weight: 500; }
    .consult-doc .owner-block {
        border-left: 3px solid #0d6efd;
        padding: 3px 10px;
        margin-bottom: 6px;
        background: #f8fafc;
        border-radius: 0 4px 4px 0;
    }
    .consult-doc .owner-block.owner-contrib { border-left-color: #6366f1; }
    .consult-doc .owner-head {
        display: flex; justify-content: space-between; align-items: center;
        font-size: .78rem; margin-bottom: 2px;
    }
    .consult-doc .entry {
        padding: 1px 0 2px;
        border-top: 1px dashed #e5e7eb;
    }
    .consult-doc .entry:first-of-type { border-top: 0; }
    .consult-doc .entry .entry-text { font-weight: 500; line-height: 1.3; margin-bottom: 0; }
    .consult-doc .entry .meta { font-size: .7rem; color: #6b7280; line-height: 1.25; }
    .consult-doc .entry .details { font-size: .75rem; margin-top: 1px; line-height: 1.25; }
    .consult-doc .entry .details .b {
        background: #eef2ff; color: #3730a3;
        padding: 1px 6px; border-radius: 4px; margin-right: 4px;
        font-size: .72rem;
    }
    .consult-doc .empty-state { color: #9ca3af; font-style: italic; font-size: .82rem; }
    /* Per-section accent colours */
    .section-complaints .owner-block              { border-left-color: #ef4444; }
    .section-complaints h3                        { color: #6b7280; }
    .section-history_of_presenting_complaint .owner-block { border-left-color: #f97316; }
    .section-history_of_presenting_complaint h3   { color: #6b7280; }
    .section-examination .owner-block             { border-left-color: #14b8a6; }
    .section-examination h3                       { color: #6b7280; }
    .section-diagnoses .owner-block               { border-left-color: #8b5cf6; }
    .section-diagnoses h3                         { color: #6b7280; }
    .section-treatments .owner-block              { border-left-color: #22c55e; }
    .section-treatments h3                        { color: #6b7280; }
    .section-prescriptions .owner-block           { border-left-color: #0ea5e9; }
    .section-prescriptions h3                     { color: #6b7280; }
    .section-tasks .owner-block                   { border-left-color: #f59e0b; }
    .section-tasks h3                             { color: #6b7280; }
    .section-notes .owner-block                   { border-left-color: #6b7280; }
    .section-notes h3                             { color: #6b7280; }
    .section-specialty .owner-block               { border-left-color: #6366f1; }
    .section-specialty h3                         { color: #4338ca; }
    .consult-doc .contributor-pill {
        display: inline-flex; align-items: center; gap: 4px;
        background: #f1f5f9; padding: 4px 10px; border-radius: 999px;
        font-size: .78rem; margin: 2px 4px 2px 0;
    }
    .consult-doc .contributor-pill.main { background: #dbeafe; color: #1d4ed8; font-weight: 600; }
    .consult-doc .session-card {
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        padding: 10px 14px;
        margin-bottom: 12px;
    }
    .consult-doc .session-card .session-head {
        display: flex; justify-content: space-between; align-items: center;
        border-bottom: 1px solid #e5e7eb;
        padding-bottom: 5px; margin-bottom: 8px;
    }
    .consult-doc .session-head h3 { margin: 0; font-size: .95rem; }
    .consult-doc .dept-group {
        margin-bottom: 12px;
        padding-left: 12px;
        border-left: 3px solid #f59e0b;
    }
    .consult-doc .dept-group .dept-name {
        font-weight: 600; color: #92400e; font-size: .82rem;
        text-transform: uppercase; letter-spacing: .05em; margin-bottom: 4px;
    }
    @media print {
        body { background: #fff !important; }
        .app-header, .app-sidebar, .topbar, .header, .sidebar, .footer, .navbar, .breadcrumb,
        .no-print, .btn, .accordion-button, .alert, .page-header-actions { display: none !important; }
        .app-content, .page-content, .container, .container-fluid, main { padding: 0 !important; margin: 0 !important; }
        .consult-doc {
            box-shadow: none !important; border: 0 !important; padding: 0 !important; max-width: 100% !important;
        }
        .consult-doc .doc-section { page-break-inside: avoid; }
        .consult-doc .session-card { page-break-inside: avoid; }
        a { color: #000 !important; text-decoration: none !important; }
    }
</style>
@endonce

@php
    $patient = $visit->patient;
    $insurance = $visit->relationLoaded('visitInsurance') ? $visit->visitInsurance : null;
    $latestVitals = $visit->vitals->first();
    $sectionLabels = [
        'complaints' => __('consultations.history.section.complaints'),
        'history_of_presenting_complaint' => __('consultations.history.section.history_of_presenting_complaint'),
        'examination' => __('consultations.history.section.examination'),
        'diagnoses' => __('consultations.history.section.diagnoses'),
        'investigations' => __('consultations.history.section.investigations'),
        'treatments' => __('consultations.history.section.treatments'),
        'prescriptions' => __('consultations.history.section.prescriptions'),
        'procedures' => __('consultations.history.section.procedures'),
        'tasks' => __('consultations.history.section.tasks'),
        'notes' => __('consultations.history.section.notes'),
    ];
    $sessionSectionLabels = collect($sectionLabels)
        ->except(['investigations', 'prescriptions'])
        ->all();
    $prescriptionEntries = collect($sessionSummaries)
        ->flatMap(fn ($bundle) => collect($bundle['summary']['sections']['prescriptions'] ?? []))
        ->values();
    // Personalise the visit-level headings (Investigations / Prescriptions /
    // Procedures) when the visit belongs to a single specialty profile and no
    // other session recorded clinical entries.
    $specialtyBundles = collect($sessionSummaries)->pluck('specialty')->filter()->values();
    $nonSpecialtyHasEntries = collect($sessionSummaries)
        ->filter(fn ($bundle) => empty($bundle['specialty']))
        ->contains(fn ($bundle) => collect($bundle['summary']['sections'] ?? [])->contains(fn ($entries) => ! empty($entries)));
    $visitSectionLabels = ($specialtyBundles->pluck('profile.code')->unique()->count() === 1 && ! $nonSpecialtyHasEntries)
        ? ($specialtyBundles->first()['visitSectionLabels'] ?? [])
        : [];
    $detailKeyMap = [
        'Catalogue' => 'catalogue', 'Category' => 'category', 'Duration' => 'duration',
        'Severity' => 'severity', 'Complaint' => 'complaint', 'Onset' => 'onset',
        'General' => 'general', 'Systemic' => 'systemic', 'Specialty' => 'specialty',
        'Type' => 'type', 'Primary' => 'primary', 'Urgency' => 'urgency',
        'Status' => 'status', 'Prescription No.' => 'prescription_no',
        'Description' => 'description', 'Assigned To' => 'assigned_to', 'Due' => 'due',
        'Completed By' => 'completed_by', 'Date' => 'date', 'Time' => 'time',
        'Department' => 'department', 'Service' => 'service', 'Doctor' => 'doctor',
        'Priority' => 'priority', 'Reason' => 'reason', 'Instruction' => 'instruction',
        'Notes' => 'notes',
    ];
    $translateDetailName = fn ($name) => isset($detailKeyMap[$name])
        ? __('consultations.history.detail.'.$detailKeyMap[$name])
        : $name;
    $translateDetailValue = function ($value) {
        $raw = trim((string) $value);
        $key = strtolower($raw);
        if (\Illuminate\Support\Facades\Lang::has('consultations.history.value.'.$key)) {
            return __('consultations.history.value.'.$key);
        }
        if (\Illuminate\Support\Facades\Lang::has('statuses.default.'.$key)) {
            return __('statuses.default.'.$key);
        }
        if (\Illuminate\Support\Facades\Lang::has('statuses.default.'.$raw)) {
            return __('statuses.default.'.$raw);
        }

        return str_replace('_', ' ', $raw);
    };
@endphp

<div class="consult-doc">

    {{-- Document header --}}
    <div class="d-flex justify-content-between align-items-start mb-3 pb-2 border-bottom">
        <div>
            <h1 class="doc-title">{{ mb_strtoupper(__('consultations.history.consultation_summary')) }}</h1>
            <div class="doc-meta">
                {{ __('consultations.history.header_meta', ['visit' => $visit->visit_number, 'date' => $visit->visit_date?->format('d M Y h:i A') ?? $visit->created_at?->format('d M Y h:i A')]) }}
                @if($visit->visit_type) &middot; {{ is_object($visit->visit_type) && method_exists($visit->visit_type, 'translatedLabel') ? $visit->visit_type->translatedLabel() : $visit->visit_type }} @endif
            </div>
        </div>
        <div class="text-end doc-meta">
            @if(config('app.name'))<div><strong>{{ config('app.name') }}</strong></div>@endif
            <div>{{ $visit->department?->name ?? '-' }}</div>
        </div>
    </div>

    {{-- Patient information --}}
    <div class="doc-section">
        <h2>{{ __('consultations.patient_information') }}</h2>
        <div class="info-grid">
            <div><span class="lbl">{{ __('common.name') }}:</span> <strong>{{ $patient->full_name }}</strong></div>
            <div><span class="lbl">{{ __('consultations.label_patient_no') }}:</span> {{ $patient->patient_number }}</div>
            <div><span class="lbl">{{ __('consultations.label_age_gender') }}:</span> {{ __('consultations.history.age_gender', ['age' => $patient->age, 'gender' => $patient->gender?->translatedLabel() ?? '-']) }}</div>
            <div><span class="lbl">{{ __('common.phone') }}:</span> <x-patient-protected-field field="phone" :value="$patient->phone" mode="export" /></div>
            <div><span class="lbl">{{ __('common.blood_group') }}:</span> {{ $patient->blood_group?->translatedLabel() ?? 'N/A' }}</div>
            <div><span class="lbl">{{ __('consultations.label_ghana_card') }}:</span> <x-patient-protected-field field="ghana_card_number" :value="$patient->ghana_card_number" mode="export" /></div>
            @if($patient->occupation)<div><span class="lbl">{{ __('consultations.label_occupation') }}:</span> {{ $patient->occupation }}</div>@endif
            @if($patient->marital_status)<div><span class="lbl">{{ __('consultations.label_marital_status') }}:</span> {{ is_object($patient->marital_status) && method_exists($patient->marital_status, 'translatedLabel') ? $patient->marital_status->translatedLabel() : $patient->marital_status }}</div>@endif
            @if($patient->religion)<div><span class="lbl">{{ __('consultations.label_religion') }}:</span> {{ $patient->religion }}</div>@endif
            </div>
    </div>

    {{-- Visit information --}}
    <div class="doc-section">
        <h2>{{ __('consultations.visit_information') }}</h2>
        <div class="info-grid">
            <div><span class="lbl">{{ __('consultations.label_visit_no') }}:</span> {{ $visit->visit_number }}</div>
            <div><span class="lbl">{{ __('consultations.visit_type') }}:</span> {{ is_object($visit->visit_type) && method_exists($visit->visit_type, 'translatedLabel') ? $visit->visit_type->translatedLabel() : ($visit->visit_type ?? '-') }}</div>
            <!-- <div><span class="lbl">{{ __('common.status') }}:</span> {{ is_object($visit->status) && method_exists($visit->status, 'translatedLabel') ? $visit->status->translatedLabel() : ($visit->status ?? '-') }}</div> -->
            <div><span class="lbl">{{ __('common.visit_date') }}:</span> {{ $visit->visit_date?->format('d M Y H:i') ?? $visit->created_at?->format('d M Y H:i') }}</div>
            <!-- <div><span class="lbl">{{ __('common.department') }}:</span> {{ $visit->department?->name ?? '-' }}</div> -->
            <!-- <div><span class="lbl">{{ __('consultations.label_sessions') }}:</span> {{ $sessions->count() ?: 1 }}</div> -->
            <div style="grid-column: 1 / -1;">
                <span class="lbl">{{ __('consultations.label_insurance') }}:</span>
                @if($insurance)
                    {{ $insurance->insuranceProvider?->name ?? __('consultations.label_insurance') }}
                    @if($insurance->member_number) &middot; {{ __('consultations.member_short') }} {{ $insurance->member_number }} @endif
                    @if($insurance->insuranceTier) &middot; {{ $insurance->insuranceTier->name }} @endif
                @else
                    {{ __('consultations.cash_self_pay') }}
                @endif
            </div>
        </div>

        @if($latestVitals)
        <h3 class="mt-3">{{ __('consultations.latest_vitals') }}</h3>
        <div class="info-grid">
            <div><span class="lbl">{{ __('consultations.history.blood_pressure_short') }}:</span> {{ $latestVitals->blood_pressure ?? '-' }} mmHg</div>
            <div><span class="lbl">{{ __('consultations.history.pulse') }}:</span> {{ $latestVitals->heart_rate ?? '-' }} bpm</div>
            <div><span class="lbl">{{ __('consultations.history.temperature_short') }}:</span> {{ $latestVitals->temperature ?? '-' }} C</div>
            <div><span class="lbl">SpO2:</span> {{ $latestVitals->spo2 ?? '-' }} %</div>
            <div><span class="lbl">{{ __('consultations.history.respiratory_rate_short') }}:</span> {{ $latestVitals->respiratory_rate ?? '-' }} /min</div>
            <div><span class="lbl">{{ __('consultations.history.bmi') }}:</span> {{ $latestVitals->bmi ?? '-' }} kg/m2</div>
        </div>
        <div class="doc-meta mt-1">
            {{ __('consultations.history.recorded_by', ['date' => $latestVitals->recorded_at?->translatedFormat('d M Y H:i'), 'name' => $latestVitals->recordedBy?->full_name ?? '-']) }}
        </div>
        @endif
    </div>

    {{-- Care team and contributors --}}
    <div class="doc-section">
        <h2>{{ __('consultations.care_team_contributors') }}</h2>
        @if(empty($contributors))
            <div class="empty-state">{{ __('consultations.no_clinicians_recorded') }}</div>
        @else
            @foreach($contributors as $c)
                <span class="contributor-pill {{ $c['role_label'] === 'Main Doctor' ? 'main' : '' }}">
                    <i class="ti {{ $c['role_label'] === 'Main Doctor' ? 'ti-stethoscope' : 'ti-user' }}"></i>
                    Dr. {{ $c['name'] }}
                    <span class="text-muted">&middot; {{ $c['role_label'] === 'Main Doctor' ? __('consultations.main_doctor_label') : __('consultations.history.contributor') }}</span>
                    <span class="badge bg-secondary-subtle text-secondary ms-1">{{ trans_choice('consultations.history.entry_count', $c['entries'], ['count' => $c['entries']]) }}</span>
                </span>
            @endforeach
        @endif
    </div>

    {{-- Clinical sessions --}}
    @foreach($sessionSummaries as $idx => $bundle)
        @php
            $session = $bundle['session'];
            $summary = $bundle['summary'];
            $isEmergencySession = $session?->isEmergencySession() ?? false;
            $sessionTitle = $isEmergencySession
                ? __('consultations.emergency_department_session')
                : ($session?->department?->name ?? ($summary['department'] ?? __('consultations.title')));
            $statusColor = $session ? match($session->status) {
                'ACTIVE' => 'success',
                'COMPLETED' => 'secondary',
                'PAUSED' => 'warning',
                'CANCELLED' => 'danger',
                default => 'info',
            } : 'secondary';
            $specialty = $bundle['specialty'] ?? null;
            // One uniform ordered section list per session: the profile layout's
            // order and labels when the session has a specialty, otherwise the
            // default generic order.
            $sectionsToRender = $specialty['orderedSections']
                ?? collect($sessionSectionLabels)->map(fn ($label, $key) => ['type' => 'generic', 'key' => $key, 'label' => $label])->values()->all();
        @endphp
        <div class="session-card">
            <div class="session-head">
                <h3>
                    <i class="ti {{ $isEmergencySession ? 'ti-urgent text-danger' : ($specialty['profile']['icon'] ?? 'ti-stethoscope').' text-primary' }} me-1"></i>
                    {{ $sessionTitle }}
                    @if($session)
                        <x-status-badge :status="$session->status" domain="consultation_session" class="ms-1" />
                    @endif
                    @if($specialty)
                        <span class="badge bg-light text-dark border ms-1"><i class="ti {{ $specialty['profile']['icon'] ?? 'ti-stethoscope' }} me-1"></i>{{ $specialty['profile']['name'] }}</span>
                    @endif
                </h3>
                <div class="doc-meta">
                    @if($session?->doctor || $session?->mainDoctor)
                        <strong>{{ __('consultations.main_doctor_label') }}:</strong> Dr. {{ $session->doctor?->full_name ?? $session->mainDoctor?->full_name }}
                    @elseif($summary['main_doctor'])
                        <strong>{{ __('consultations.main_doctor_label') }}:</strong> Dr. {{ $summary['main_doctor'] }}
                    @else
                        <span class="empty-state">{{ __('consultations.unassigned') }}</span>
                    @endif
                    @if($session?->started_at) &middot; {{ __('consultations.history.started', ['date' => $session->started_at->translatedFormat('d M Y, h:i A')]) }} @endif
                    @if($session?->completed_at) &middot; {{ __('consultations.history.completed', ['date' => $session->completed_at->translatedFormat('d M Y, h:i A')]) }} @endif
                </div>
            </div>

            @if(!empty($summary['services']))
                <div class="doc-meta mb-2"><span class="lbl">{{ __('consultations.history.services') }}:</span> {{ implode(', ', $summary['services']) }}</div>
            @endif
            @if(!empty($summary['contributors']))
                <div class="doc-meta mb-2"><span class="lbl">{{ __('consultations.history.session_contributors') }}:</span> {{ implode(', ', $summary['contributors']) }}</div>
            @endif

            @php
                $hasAnySectionEntry = collect($sectionsToRender)->some(fn ($s) => ($s['type'] ?? 'generic') === 'structured'
                    ? ! empty($s['entries'])
                    : ! empty($summary['sections'][$s['key']] ?? []));
            @endphp
            @if(!$hasAnySectionEntry)
                <div class="empty-state">{{ __('consultations.history.no_clinical_entries') }}</div>
            @else
            @foreach($sectionsToRender as $renderSection)
                @php
                    $key = $renderSection['key'];
                    $label = $renderSection['label'] ?? ($sessionSectionLabels[$key] ?? str($key)->replace('_', ' ')->title()->toString());
                    $isStructuredSection = ($renderSection['type'] ?? 'generic') === 'structured';
                    $entries = $isStructuredSection ? collect() : collect($summary['sections'][$key] ?? []);
                    $groups = $entries->groupBy(fn ($e) => $e['owner_key'] ?? 'unknown');
                @endphp
                @if($isStructuredSection)
                {{-- Structured specialty section (visual acuity, refraction, …)
                     rendered with the exact same markup as the generic sections. --}}
                <div class="doc-subsection mb-2 section-specialty">
                    <h3>{{ $label }} <span class="text-muted small">({{ count($renderSection['entries']) }})</span></h3>
                    @foreach($renderSection['entries'] as $spEntry)
                        @php
                            $spOwner = $spEntry['owner_name'] ?? __('consultations.history.unknown_user');
                            $spIsMain = $spEntry['owner_name'] && $spEntry['owner_name'] === ($summary['main_doctor'] ?? null);
                        @endphp
                        <div class="owner-block {{ $spIsMain ? '' : 'owner-contrib' }}">
                            <div class="owner-head">
                                <div>
                                    <strong>{{ $spEntry['owner_name'] ? 'Dr. '.$spEntry['owner_name'] : $spOwner }}</strong>
                                    <span class="badge bg-{{ $spIsMain ? 'primary' : 'secondary' }}-subtle text-{{ $spIsMain ? 'primary' : 'secondary' }} ms-1">{{ $spIsMain ? __('consultations.main_doctor_label') : __('consultations.history.contributor') }}</span>
                                </div>
                                <span class="text-muted small">{{ trans_choice('consultations.history.entry_count', 1, ['count' => 1]) }}</span>
                            </div>
                            <div class="entry">
                                <div class="details">
                                    @foreach($spEntry['fields'] as $f)
                                        <span class="b">{{ $f['label'] }}: {{ $f['value'] }}</span>
                                    @endforeach
                                </div>
                                <div class="meta">
                                    @if($spEntry['recorded_at']) {{ __('consultations.history.recorded', ['date' => $spEntry['recorded_at']->translatedFormat('d M Y, h:i A')]) }} @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @continue
                @endif
                @if($entries->isEmpty()) @continue @endif
                <div class="doc-subsection mb-2 section-{{ $key }}">
                    <h3>{{ $label }} <span class="text-muted small">({{ $entries->count() }})</span></h3>
                    @if($groups->isEmpty())
                        <div class="empty-state">{{ __('consultations.history.none_recorded') }}</div>
                    @else
                        @foreach($groups as $ownerKey => $groupEntries)
                            @php
                                $first = $groupEntries->first();
                                $ownerName = $first['entered_by'] ?? __('consultations.history.unknown_user');
                                $isMain = $ownerName !== __('consultations.history.unknown_user') && $ownerName === ($summary['main_doctor'] ?? null);
                                $roleLabel = $isMain ? __('consultations.main_doctor_label') : __('consultations.history.contributor');
                            @endphp
                            <div class="owner-block {{ $isMain ? '' : 'owner-contrib' }}">
                                <div class="owner-head">
                                    <div>
                                        <strong>{{ $ownerName === __('consultations.history.unknown_user') ? $ownerName : 'Dr. '.$ownerName }}</strong>
                                        <span class="badge bg-{{ $isMain ? 'primary' : 'secondary' }}-subtle text-{{ $isMain ? 'primary' : 'secondary' }} ms-1">{{ $roleLabel }}</span>
                                        @if($first['owner_role'])<span class="text-muted small ms-1">&middot; {{ $first['owner_role'] }}</span>@endif
                                    </div>
                                    <span class="text-muted small">{{ trans_choice('consultations.history.entry_count', $groupEntries->count(), ['count' => $groupEntries->count()]) }}</span>
                                </div>

                                @foreach($groupEntries as $entry)
                                    <div class="entry">
                                        <div class="entry-text">{{ $entry['content'] }}</div>
                                        @if(!empty($entry['details']))
                                            <div class="details">
                                                @foreach($entry['details'] as $name => $value)
                                                    <span class="b">{{ $translateDetailName($name) }}: {{ $translateDetailValue($value) }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                        <div class="meta">
                                            @if($entry['created_at']) {{ __('consultations.history.recorded', ['date' => $entry['created_at']->translatedFormat('d M Y, h:i A')]) }} @endif
                                            @if(!empty($entry['updated_by']) && $entry['updated_at'] && $entry['created_at'] && $entry['updated_at']->gt($entry['created_at']))
                                                &middot; {{ __('consultations.history.edited_by', ['name' => $entry['updated_by'], 'date' => $entry['updated_at']->translatedFormat('d M Y, h:i A')]) }}
                                            @endif
                                            @if(!empty($entry['source_pattern']))
                                                &middot; {{ __('consultations.history.source_pattern', ['pattern' => $entry['source_pattern']]) }}
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    @endif
                </div>
            @endforeach
            @endif
        </div>
    @endforeach

    {{-- Investigations (Department then Owner) --}}
    <div class="doc-section">
        <h2>{{ $visitSectionLabels['investigations'] ?? __('consultations.investigations_heading') }}</h2>
        @php
            $labGrouped = $labRequests->groupBy(function($r) {
            if ($r->targetDepartment?->name) return $r->targetDepartment->name;
            if ($r->department?->name) return $r->department->name;
            // Fall back to first item's service department
            $firstItem = $r->items->first();
            return $firstItem?->service?->department?->name ?? __('consultations.history.other');
        });
        @endphp
        @if($labGrouped->isEmpty())
            <div class="empty-state">{{ __('consultations.no_investigations_requested') }}</div>
        @else
            @foreach($labGrouped as $deptName => $reqs)
                <div class="dept-group">
                    <div class="dept-name"><i class="ti ti-building-hospital me-1"></i>{{ $deptName }}</div>
                    @php
                        $byOwner = collect($reqs)->groupBy(function($r) {
                            $u = $r->requestedBy;
                            return $u?->id ? 'u-'.$u->id : 'unknown';
                        });
                    @endphp
                    @foreach($byOwner as $ownerKey => $ownerReqs)
                        @php
                            $owner = $ownerReqs->first()->requestedBy ?? null;
                            $ownerName = $owner?->full_name ?? __('consultations.history.unknown_user');
                        @endphp
                        <div class="owner-block owner-contrib">
                            <div class="owner-head">
                                <div>
                                    <strong>{{ $ownerName === __('consultations.history.unknown_user') ? $ownerName : 'Dr. '.$ownerName }}</strong>
                                    <span class="badge bg-secondary-subtle text-secondary ms-1">{{ __('consultations.requesting_clinician') }}</span>
                                </div>
                                @php $testCount = $ownerReqs->sum(fn($r) => $r->items?->count() ?? 0); @endphp
                                <span class="text-muted small">{{ trans_choice('consultations.history.test_count', $testCount, ['count' => $testCount]) }}</span>
                            </div>
                            @foreach($ownerReqs as $req)
                                @foreach($req->items ?? [] as $item)
                                    <div class="entry">
                                        <div class="entry-text">
                                            {{ $item->display_name ?? $item->name ?? ($item->labTest?->name ?? __('consultations.history.test')) }}
                                            <span class="badge bg-{{ $item->status_color ?? 'secondary' }} ms-1">{{ $translateDetailValue($item->status ?? '') }}</span>
                                            @if($item->result?->is_verified)
                                                <span class="badge bg-success ms-1">{{ __('consultations.verified') }}</span>
                                            @elseif($item->result)
                                                <span class="badge bg-warning ms-1">{{ __('consultations.result_pending_verification') }}</span>
                                            @endif
                                        </div>
                                        <div class="meta">
                                            {{ __('consultations.history.request_number', ['number' => $req->request_number]) }} &middot; {{ __('consultations.history.requested', ['date' => $req->created_at?->translatedFormat('d M Y H:i')]) }}
                                            @if($item->result?->result_value)
                                                &middot; <strong>{{ __('consultations.result_label') }}:</strong> {{ \Illuminate\Support\Str::limit($item->result->result_value, 120) }}
                                            @endif
                                            @if($item->result?->verifiedBy)
                                                &middot; {{ __('consultations.history.verified_by', ['name' => $item->result->verifiedBy->full_name]) }}
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @endforeach
        @endif
    </div>

    {{-- Prescriptions (Owner) --}}
    <div class="doc-section section-prescriptions">
        <h2>{{ $visitSectionLabels['prescriptions'] ?? __('consultations.history.section.prescriptions') }}</h2>
        @if($prescriptionEntries->isEmpty())
            <div class="empty-state">{{ __('consultations.history.none_recorded') }}</div>
        @else
            @php
                $prescriptionGroups = $prescriptionEntries->groupBy(fn ($e) => $e['owner_key'] ?? 'unknown');
            @endphp
            @foreach($prescriptionGroups as $ownerKey => $groupEntries)
                @php
                    $first = $groupEntries->first();
                    $ownerName = $first['entered_by'] ?? __('consultations.history.unknown_user');
                @endphp
                <div class="owner-block owner-contrib">
                    <div class="owner-head">
                        <div>
                            <strong>{{ $ownerName === __('consultations.history.unknown_user') ? $ownerName : 'Dr. '.$ownerName }}</strong>
                            <span class="badge bg-secondary-subtle text-secondary ms-1">{{ __('consultations.requesting_clinician') }}</span>
                            @if(!empty($first['owner_role']))<span class="text-muted small ms-1">&middot; {{ $first['owner_role'] }}</span>@endif
                        </div>
                        <span class="text-muted small">{{ trans_choice('consultations.history.entry_count', $groupEntries->count(), ['count' => $groupEntries->count()]) }}</span>
                    </div>

                    @foreach($groupEntries as $entry)
                        <div class="entry">
                            <div class="entry-text">{{ $entry['content'] }}</div>
                            @if(!empty($entry['details']))
                                <div class="details">
                                    @foreach($entry['details'] as $name => $value)
                                        <span class="b">{{ $translateDetailName($name) }}: {{ $translateDetailValue($value) }}</span>
                                    @endforeach
                                </div>
                            @endif
                            <div class="meta">
                                @if($entry['created_at']) {{ __('consultations.history.recorded', ['date' => $entry['created_at']->translatedFormat('d M Y, h:i A')]) }} @endif
                                @if(!empty($entry['updated_by']) && $entry['updated_at'] && $entry['created_at'] && $entry['updated_at']->gt($entry['created_at']))
                                    &middot; {{ __('consultations.history.edited_by', ['name' => $entry['updated_by'], 'date' => $entry['updated_at']->translatedFormat('d M Y, h:i A')]) }}
                                @endif
                                @if(!empty($entry['source_pattern']))
                                    &middot; {{ __('consultations.history.source_pattern', ['pattern' => $entry['source_pattern']]) }}
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        @endif
    </div>

    {{-- Procedures (Department then Owner) --}}
    <div class="doc-section">
        <h2>{{ $visitSectionLabels['procedures'] ?? __('consultations.procedures_heading') }}</h2>
        @php
            $procGrouped = $procedureRequests->groupBy(fn($p) => $p->department?->name ?? __('consultations.history.other'));
        @endphp
        @if($procGrouped->isEmpty())
            <div class="empty-state">{{ __('consultations.no_procedures_requested') }}</div>
        @else
            @foreach($procGrouped as $deptName => $procs)
                <div class="dept-group">
                    <div class="dept-name"><i class="ti ti-activity-heartbeat me-1"></i>{{ $deptName }}</div>
                    @php
                        $byOwner = collect($procs)->groupBy(function($p) {
                            $u = $p->requestingDoctor;
                            return $u?->id ? 'u-'.$u->id : 'unknown';
                        });
                    @endphp
                    @foreach($byOwner as $ownerKey => $ownerProcs)
                        @php
                            $owner = $ownerProcs->first()->requestingDoctor ?? null;
                            $ownerName = $owner?->full_name ?? __('consultations.history.unknown_user');
                        @endphp
                        <div class="owner-block owner-contrib">
                            <div class="owner-head">
                                <div>
                                    <strong>{{ $ownerName === __('consultations.history.unknown_user') ? $ownerName : 'Dr. '.$ownerName }}</strong>
                                    <span class="badge bg-secondary-subtle text-secondary ms-1">{{ __('consultations.requesting_clinician') }}</span>
                                </div>
                                <span class="text-muted small">{{ trans_choice('consultations.history.procedure_count', $ownerProcs->count(), ['count' => $ownerProcs->count()]) }}</span>
                            </div>
                            @foreach($ownerProcs as $pr)
                                <div class="entry">
                                    <div class="entry-text">
                                        {{ $pr->service?->name ?? __('consultations.history.procedure') }}
                                        <span class="badge ms-1" style="background-color:{{ $pr->status->color() }};color:#fff">{{ $pr->status->translatedLabel() }}</span>
                                        @if($pr->priority)<span class="badge bg-light text-dark ms-1">{{ $pr->priority instanceof \App\Enums\Priority ? $pr->priority->translatedLabel() : $translateDetailValue($pr->priority) }}</span>@endif
                                    </div>
                                    <div class="meta">
                                        {{ __('consultations.history.request_number', ['number' => $pr->request_number]) }} &middot; {{ __('consultations.history.requested', ['date' => $pr->created_at?->translatedFormat('d M Y H:i')]) }}
                                        @if($pr->schedule) &middot; {{ __('consultations.history.scheduled', ['date' => $pr->schedule->scheduled_start?->translatedFormat('d M Y H:i')]) }} @endif
                                        @if($pr->schedule?->surgeon) &middot; {{ __('consultations.history.surgeon', ['name' => $pr->schedule->surgeon->full_name]) }} @endif
                                    </div>
                                    @if($pr->indication)
                                        <div class="details"><span class="b">{{ __('consultations.history.indication') }}: {{ $pr->indication }}</span></div>
                                    @endif
                                    @if($pr->notes)
                                        <div class="details small text-muted"><em>{{ __('consultations.history.notes') }}:</em> {{ $pr->notes }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @endforeach
        @endif
    </div>

    {{-- Document footer --}}
    <div class="doc-section pt-3 border-top doc-meta">
        <div class="d-flex justify-content-between">
            <span>{{ __('consultations.history.generated_by', ['name' => auth()->user()?->full_name ?? '-', 'date' => $generatedAt->translatedFormat('d M Y, h:i A')]) }}</span>
            <span>{{ config('app.name') }} &middot; {{ __('consultations.history.consultation_summary') }}</span>
        </div>
    </div>

</div>
