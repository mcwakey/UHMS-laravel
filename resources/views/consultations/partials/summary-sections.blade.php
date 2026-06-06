<div class="border rounded p-3 mb-3 bg-light">
    <div class="row g-2 small">
        <div class="col-md-4"><strong>Department Session:</strong> {{ $consultationSummary['department'] ?? '-' }}</div>
        <div class="col-md-4"><strong>Main Doctor:</strong> {{ $consultationSummary['main_doctor'] ? 'Dr. '.$consultationSummary['main_doctor'] : 'Unassigned' }}</div>
        <div class="col-md-4"><strong>Contributors:</strong> {{ collect($consultationSummary['contributors'] ?? [])->implode(', ') ?: '-' }}</div>
        <div class="col-12"><strong>Services:</strong> {{ collect($consultationSummary['services'] ?? [])->implode(', ') ?: '-' }}</div>
    </div>
</div>

@php
    $summaryLabels = [
        'complaints' => 'Complaints',
        'history_of_presenting_complaint' => 'History of Presenting Complaint',
        'examination' => 'Examination',
        'diagnoses' => 'Diagnosis',
        'investigations' => 'Investigations',
        'treatments' => 'Treatments',
        'prescriptions' => 'Prescriptions',
        'procedures' => 'Procedures',
        'tasks' => 'Tasks / Follow-up / Instructions',
        'follow_up_appointments' => 'Next Appointment / Follow-up',
        'notes' => 'Notes',
    ];
    $visibleSummarySections = collect($summaryLabels)
        ->filter(fn ($label, $key) => collect($consultationSummary['sections'][$key] ?? [])->isNotEmpty());
@endphp

@forelse($visibleSummarySections as $key => $label)
    <div class="mb-3">
        <h6 class="small fw-bold text-muted border-bottom pb-1">{{ $label }}</h6>
        @php
            $entries = collect($consultationSummary['sections'][$key] ?? []);
            $groups = $entries->groupBy(fn ($entry) => $entry['owner_key'] ?? (($entry['entered_by'] ?? null) ? 'name-'.$entry['entered_by'] : 'unknown'));
        @endphp
        @foreach($groups as $groupEntries)
            @php
                $first = $groupEntries->first();
                $ownerName = $first['entered_by'] ?? 'Unknown user';
                $isMainDoctor = $ownerName !== 'Unknown user' && $ownerName === ($consultationSummary['main_doctor'] ?? null);
                $roleLabel = $isMainDoctor ? 'Main Doctor' : 'Contributor';
                $ownerRole = $first['owner_role'] ?? null;
            @endphp
            <div class="owner-group mb-2" data-owner-key="{{ $first['owner_key'] ?? 'unknown' }}">
                <div class="d-flex align-items-center justify-content-between bg-light border rounded px-2 py-1 mb-2">
                    <div>
                        <span class="fw-semibold">{{ $ownerName === 'Unknown user' ? $ownerName : 'Dr. '.$ownerName }}</span>
                        <span class="badge bg-{{ $isMainDoctor ? 'primary' : 'secondary' }}-subtle text-{{ $isMainDoctor ? 'primary' : 'secondary' }} ms-1">{{ $roleLabel }}</span>
                        @if($ownerRole)
                            <small class="text-muted ms-1">{{ $ownerRole }}</small>
                        @endif
                    </div>
                    <small class="text-muted">{{ $groupEntries->count() }} {{ Str::plural('entry', $groupEntries->count()) }}</small>
                </div>
                @foreach($groupEntries as $entry)
                    <div class="ehr-item">
                        <div class="fw-medium">{{ $entry['content'] }}</div>
                        <small class="text-muted">
                            @if($entry['created_at']) Created: {{ $entry['created_at']->format('d M Y, h:i A') }} @endif
                            @if(!empty($entry['updated_by']) && $entry['updated_at'] && $entry['updated_at']->gt($entry['created_at']))
                                · Edited by: {{ $entry['updated_by'] }} {{ $entry['updated_at']->format('d M Y, h:i A') }}
                            @endif
                            @if($entry['source_pattern']) · Source Pattern: {{ $entry['source_pattern'] }} @endif
                        </small>
                        @if(!empty($entry['details']))
                            <div class="small mt-1">
                                @foreach($entry['details'] as $name => $value)
                                    <span class="badge bg-light text-dark me-1">{{ $name }}: {{ $value }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
@empty
    <div class="text-muted small">No consultation summary records yet.</div>
@endforelse
