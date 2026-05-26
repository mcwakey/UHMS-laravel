@extends('layouts.app')
@section('title', 'Consultation Summary - ' . $visit->visit_number)

@push('styles')
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
@endpush

@section('content')
@php
    $patient = $visit->patient;
    $insurance = $visit->relationLoaded('visitInsurance') ? $visit->visitInsurance : null;
    $latestVitals = $visit->vitals->first();
    $sectionLabels = [
        'complaints' => 'Complaints',
        'history_of_presenting_complaint' => 'History of Presenting Complaint',
        'examination' => 'Examination Findings',
        'diagnoses' => 'Diagnoses',
        'treatments' => 'Treatments',
        'prescriptions' => 'Prescriptions',
        'tasks' => 'Tasks / Follow-up / Instructions',
        'notes' => 'Clinical Notes',
    ];
@endphp

<div class="d-flex align-items-center gap-2 mb-3 no-print">
    <a href="{{ route('admin.consultations.show', $visit) }}" class="btn btn-outline-secondary btn-sm">
        <i class="ti ti-arrow-left me-1"></i>Back to Consultation
    </a>
    <button type="button" class="btn btn-primary btn-sm ms-auto" onclick="window.print()">
        <i class="ti ti-printer me-1"></i>Print Summary
    </button>
</div>

<div class="consult-doc">

    {{-- Document header --}}
    <div class="d-flex justify-content-between align-items-start mb-3 pb-2 border-bottom">
        <div>
            <h1 class="doc-title">CONSULTATION SUMMARY</h1>
            <div class="doc-meta">
                Visit <strong>{{ $visit->visit_number }}</strong>
                &middot; Generated {{ $generatedAt->format('d M Y, h:i A') }}
                @if($visit->visit_type) &middot; {{ is_object($visit->visit_type) ? $visit->visit_type->value : $visit->visit_type }} @endif
            </div>
        </div>
        <div class="text-end doc-meta">
            @if(config('app.name'))<div><strong>{{ config('app.name') }}</strong></div>@endif
            <div>{{ $visit->department?->name ?? '-' }}</div>
        </div>
    </div>

    {{-- Patient information --}}
    <div class="doc-section">
        <h2>Patient Information</h2>
        <div class="info-grid">
            <div><span class="lbl">Name:</span> <strong>{{ $patient->full_name }}</strong></div>
            <div><span class="lbl">Patient No.:</span> {{ $patient->patient_number }}</div>
            <div><span class="lbl">Age / Gender:</span> {{ $patient->age }}y &middot; {{ $patient->gender?->value ?? '-' }}</div>
            <div><span class="lbl">Phone:</span> {{ $patient->phone ?? '-' }}</div>
            <div><span class="lbl">Blood Group:</span> {{ $patient->blood_group?->value ?? 'N/A' }}</div>
            <div><span class="lbl">Ghana Card:</span> {{ $patient->ghana_card_number ?? '-' }}</div>
            @if($patient->occupation)<div><span class="lbl">Occupation:</span> {{ $patient->occupation }}</div>@endif
            @if($patient->marital_status)<div><span class="lbl">Marital Status:</span> {{ is_object($patient->marital_status) ? $patient->marital_status->value : $patient->marital_status }}</div>@endif
            @if($patient->religion)<div><span class="lbl">Religion:</span> {{ $patient->religion }}</div>@endif
            <div style="grid-column: 1 / -1;">
                <span class="lbl">Insurance:</span>
                @if($insurance)
                    {{ $insurance->insuranceProvider?->name ?? 'Insurance' }}
                    @if($insurance->member_number) &middot; Member {{ $insurance->member_number }} @endif
                    @if($insurance->insuranceTier) &middot; {{ $insurance->insuranceTier->name }} @endif
                @else
                    Cash / Self-Pay
                @endif
            </div>
        </div>
    </div>

    {{-- Visit information --}}
    <div class="doc-section">
        <h2>Visit Information</h2>
        <div class="info-grid">
            <div><span class="lbl">Visit No.:</span> {{ $visit->visit_number }}</div>
            <div><span class="lbl">Visit Type:</span> {{ is_object($visit->visit_type) ? $visit->visit_type->value : ($visit->visit_type ?? '-') }}</div>
            <div><span class="lbl">Status:</span> {{ is_object($visit->status) ? $visit->status->value : ($visit->status ?? '-') }}</div>
            <div><span class="lbl">Visit Date:</span> {{ $visit->visit_date?->format('d M Y H:i') ?? $visit->created_at?->format('d M Y H:i') }}</div>
            <div><span class="lbl">Department:</span> {{ $visit->department?->name ?? '-' }}</div>
            <div><span class="lbl">Sessions:</span> {{ $sessions->count() ?: 1 }}</div>
        </div>

        @if($latestVitals)
        <h3 class="mt-3">Latest Vitals</h3>
        <div class="info-grid">
            <div><span class="lbl">BP:</span> {{ $latestVitals->blood_pressure ?? '-' }} mmHg</div>
            <div><span class="lbl">Pulse:</span> {{ $latestVitals->heart_rate ?? '-' }} bpm</div>
            <div><span class="lbl">Temp:</span> {{ $latestVitals->temperature ?? '-' }} C</div>
            <div><span class="lbl">SpO2:</span> {{ $latestVitals->spo2 ?? '-' }} %</div>
            <div><span class="lbl">Resp:</span> {{ $latestVitals->respiratory_rate ?? '-' }} /min</div>
            <div><span class="lbl">BMI:</span> {{ $latestVitals->bmi ?? '-' }} kg/m2</div>
        </div>
        <div class="doc-meta mt-1">
            Recorded {{ $latestVitals->recorded_at?->format('d M Y H:i') }} by {{ $latestVitals->recordedBy?->full_name ?? '-' }}
        </div>
        @endif
    </div>

    {{-- Care team and contributors --}}
    <div class="doc-section">
        <h2>Care Team &amp; Contributors</h2>
        @if(empty($contributors))
            <div class="empty-state">No clinicians have recorded entries for this visit yet.</div>
        @else
            @foreach($contributors as $c)
                <span class="contributor-pill {{ $c['role_label'] === 'Main Doctor' ? 'main' : '' }}">
                    <i class="ti {{ $c['role_label'] === 'Main Doctor' ? 'ti-stethoscope' : 'ti-user' }}"></i>
                    Dr. {{ $c['name'] }}
                    <span class="text-muted">&middot; {{ $c['role_label'] }}</span>
                    <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $c['entries'] }} {{ \Illuminate\Support\Str::plural('entry', $c['entries']) }}</span>
                </span>
            @endforeach
        @endif
    </div>

    {{-- Clinical sessions --}}
    @foreach($sessionSummaries as $idx => $bundle)
        @php
            $session = $bundle['session'];
            $summary = $bundle['summary'];
            $statusColor = $session ? match($session->status) {
                'ACTIVE' => 'success',
                'COMPLETED' => 'secondary',
                'PAUSED' => 'warning',
                'CANCELLED' => 'danger',
                default => 'info',
            } : 'secondary';
        @endphp
        <div class="session-card">
            <div class="session-head">
                <h3>
                    <i class="ti ti-stethoscope me-1 text-primary"></i>
                    {{ $session?->department?->name ?? ($summary['department'] ?? 'Consultation') }}
                    @if($session)
                        <span class="badge bg-{{ $statusColor }} ms-1">{{ $session->status }}</span>
                    @endif
                </h3>
                <div class="doc-meta">
                    @if($session?->doctor)
                        <strong>Main Doctor:</strong> Dr. {{ $session->doctor->full_name }}
                    @elseif($summary['main_doctor'])
                        <strong>Main Doctor:</strong> Dr. {{ $summary['main_doctor'] }}
                    @else
                        <span class="empty-state">Unassigned</span>
                    @endif
                    @if($session?->started_at) &middot; Started {{ $session->started_at->format('d M Y, h:i A') }} @endif
                    @if($session?->completed_at) &middot; Completed {{ $session->completed_at->format('d M Y, h:i A') }} @endif
                </div>
            </div>

            @if(!empty($summary['services']))
                <div class="doc-meta mb-2"><span class="lbl">Services:</span> {{ implode(', ', $summary['services']) }}</div>
            @endif
            @if(!empty($summary['contributors']))
                <div class="doc-meta mb-2"><span class="lbl">Session Contributors:</span> {{ implode(', ', $summary['contributors']) }}</div>
            @endif

            @php
                $hasAnySectionEntry = collect($sectionLabels)->keys()->some(fn($k) => !empty($summary['sections'][$k]));
            @endphp
            @if(!$hasAnySectionEntry)
                <div class="empty-state">No clinical entries recorded for this session.</div>
            @else
            @foreach($sectionLabels as $key => $label)
                @php
                    $entries = collect($summary['sections'][$key] ?? []);
                    $groups = $entries->groupBy(fn ($e) => $e['owner_key'] ?? 'unknown');
                @endphp
                @if($entries->isEmpty()) @continue @endif
                <div class="doc-subsection mb-2 section-{{ $key }}">
                    <h3>{{ $label }} <span class="text-muted small">({{ $entries->count() }})</span></h3>
                    @if($groups->isEmpty())
                        <div class="empty-state">None recorded.</div>
                    @else
                        @foreach($groups as $ownerKey => $groupEntries)
                            @php
                                $first = $groupEntries->first();
                                $ownerName = $first['entered_by'] ?? 'Unknown user';
                                $isMain = $ownerName !== 'Unknown user' && $ownerName === ($summary['main_doctor'] ?? null);
                                $roleLabel = $isMain ? 'Main Doctor' : 'Contributor';
                            @endphp
                            <div class="owner-block {{ $isMain ? '' : 'owner-contrib' }}">
                                <div class="owner-head">
                                    <div>
                                        <strong>{{ $ownerName === 'Unknown user' ? $ownerName : 'Dr. '.$ownerName }}</strong>
                                        <span class="badge bg-{{ $isMain ? 'primary' : 'secondary' }}-subtle text-{{ $isMain ? 'primary' : 'secondary' }} ms-1">{{ $roleLabel }}</span>
                                        @if($first['owner_role'])<span class="text-muted small ms-1">&middot; {{ $first['owner_role'] }}</span>@endif
                                    </div>
                                    <span class="text-muted small">{{ $groupEntries->count() }} {{ \Illuminate\Support\Str::plural('entry', $groupEntries->count()) }}</span>
                                </div>

                                @foreach($groupEntries as $entry)
                                    <div class="entry">
                                        <div class="entry-text">{{ $entry['content'] }}</div>
                                        @if(!empty($entry['details']))
                                            <div class="details">
                                                @foreach($entry['details'] as $name => $value)
                                                    <span class="b">{{ $name }}: {{ $value }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                        <div class="meta">
                                            @if($entry['created_at']) Recorded: {{ $entry['created_at']->format('d M Y, h:i A') }} @endif
                                            @if(!empty($entry['updated_by']) && $entry['updated_at'] && $entry['created_at'] && $entry['updated_at']->gt($entry['created_at']))
                                                &middot; Edited by {{ $entry['updated_by'] }} on {{ $entry['updated_at']->format('d M Y, h:i A') }}
                                            @endif
                                            @if(!empty($entry['source_pattern']))
                                                &middot; Pattern: {{ $entry['source_pattern'] }}
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
        <h2>Investigations</h2>
        @php
            $labGrouped = $labRequests->groupBy(fn($r) => $r->targetDepartment?->name ?? ($r->department?->name ?? 'Other'));
        @endphp
        @if($labGrouped->isEmpty())
            <div class="empty-state">No investigations requested for this visit.</div>
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
                            $ownerName = $owner?->full_name ?? 'Unknown user';
                        @endphp
                        <div class="owner-block owner-contrib">
                            <div class="owner-head">
                                <div>
                                    <strong>{{ $ownerName === 'Unknown user' ? $ownerName : 'Dr. '.$ownerName }}</strong>
                                    <span class="badge bg-secondary-subtle text-secondary ms-1">Requesting Clinician</span>
                                </div>
                                <span class="text-muted small">{{ $ownerReqs->sum(fn($r) => $r->items?->count() ?? 0) }} test(s)</span>
                            </div>
                            @foreach($ownerReqs as $req)
                                @foreach($req->items ?? [] as $item)
                                    <div class="entry">
                                        <div class="entry-text">
                                            {{ $item->display_name ?? $item->name ?? ($item->labTest?->name ?? 'Test') }}
                                            <span class="badge bg-{{ $item->status_color ?? 'secondary' }} ms-1">{{ ucfirst($item->status ?? '') }}</span>
                                            @if($item->result?->is_verified)
                                                <span class="badge bg-success ms-1">Verified</span>
                                            @elseif($item->result)
                                                <span class="badge bg-warning ms-1">Result Pending Verification</span>
                                            @endif
                                        </div>
                                        <div class="meta">
                                            Req #{{ $req->request_number }} &middot; Requested {{ $req->created_at?->format('d M Y H:i') }}
                                            @if($item->result?->result_value)
                                                &middot; <strong>Result:</strong> {{ \Illuminate\Support\Str::limit($item->result->result_value, 120) }}
                                            @endif
                                            @if($item->result?->verifiedBy)
                                                &middot; Verified by Dr. {{ $item->result->verifiedBy->full_name }}
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

    {{-- Procedures (Department then Owner) --}}
    <div class="doc-section">
        <h2>Procedures</h2>
        @php
            $procGrouped = $procedureRequests->groupBy(fn($p) => $p->department?->name ?? 'Other');
        @endphp
        @if($procGrouped->isEmpty())
            <div class="empty-state">No procedures requested for this visit.</div>
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
                            $ownerName = $owner?->full_name ?? 'Unknown user';
                        @endphp
                        <div class="owner-block owner-contrib">
                            <div class="owner-head">
                                <div>
                                    <strong>{{ $ownerName === 'Unknown user' ? $ownerName : 'Dr. '.$ownerName }}</strong>
                                    <span class="badge bg-secondary-subtle text-secondary ms-1">Requesting Clinician</span>
                                </div>
                                <span class="text-muted small">{{ $ownerProcs->count() }} procedure(s)</span>
                            </div>
                            @foreach($ownerProcs as $pr)
                                <div class="entry">
                                    <div class="entry-text">
                                        {{ $pr->service?->name ?? 'Procedure' }}
                                        <span class="badge ms-1" style="background-color:{{ $pr->status->color() }};color:#fff">{{ $pr->status->label() }}</span>
                                        @if($pr->priority)<span class="badge bg-light text-dark ms-1">{{ ucfirst($pr->priority) }}</span>@endif
                                    </div>
                                    <div class="meta">
                                        Req #{{ $pr->request_number }} &middot; Requested {{ $pr->created_at?->format('d M Y H:i') }}
                                        @if($pr->schedule) &middot; Scheduled {{ optional($pr->schedule->scheduled_start)->format('d M Y H:i') }} @endif
                                        @if($pr->schedule?->surgeon) &middot; Surgeon: Dr. {{ $pr->schedule->surgeon->full_name }} @endif
                                    </div>
                                    @if($pr->indication)
                                        <div class="details"><span class="b">Indication: {{ $pr->indication }}</span></div>
                                    @endif
                                    @if($pr->notes)
                                        <div class="details small text-muted"><em>Notes:</em> {{ $pr->notes }}</div>
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
            <span>Generated by {{ auth()->user()?->full_name ?? '-' }} on {{ $generatedAt->format('d M Y, h:i A') }}</span>
            <span>{{ config('app.name') }} &middot; Consultation Summary</span>
        </div>
    </div>

</div>
@endsection
