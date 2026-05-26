@if(!empty($clinicalMirror['records']))
<div class="card mb-3">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="ti ti-stethoscope me-1"></i>Claim Preparation Clinical Mirror</h5>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3">Read-only consultation record snapshot for claim preparation. Clinical authorship is preserved.</p>
        @foreach($clinicalMirror['records'] as $recordMirror)
            <div class="border rounded p-3 mb-3">
                <div class="small mb-2">
                    <strong>Department:</strong> {{ $recordMirror['department'] ?? '-' }}
                    <span class="ms-2"><strong>Main Doctor:</strong> {{ $recordMirror['main_doctor'] ?? 'Unassigned' }}</span>
                    <span class="ms-2"><strong>Contributors:</strong> {{ collect($recordMirror['contributors'] ?? [])->implode(', ') ?: '-' }}</span>
                </div>
                @foreach([
                    'complaints' => 'Complaints',
                    'history_of_presenting_complaint' => 'History of Presenting Complaint',
                    'examination' => 'Examination',
                    'diagnoses' => 'Diagnosis',
                    'investigations' => 'Investigations',
                    'treatments' => 'Treatments',
                    'prescriptions' => 'Prescriptions',
                    'procedures' => 'Procedures',
                    'tasks' => 'Tasks / Follow-up / Instructions',
                    'notes' => 'Notes / Summary',
                ] as $key => $label)
                    @if(!empty($recordMirror['sections'][$key]))
                        <h6 class="small fw-bold text-muted border-bottom pb-1 mt-3">{{ $label }}</h6>
                        @foreach($recordMirror['sections'][$key] as $entry)
                            <div class="ps-2 mb-2 border-start border-3">
                                <div>{{ $entry['content'] }}</div>
                                <small class="text-muted">
                                    Original author: {{ $entry['entered_by'] }}
                                    @if($entry['created_at']) · {{ $entry['created_at']->format('d M Y, h:i A') }} @endif
                                    @if($entry['source_pattern']) · Source Pattern: {{ $entry['source_pattern'] }} @endif
                                </small>
                            </div>
                        @endforeach
                    @endif
                @endforeach
            </div>
        @endforeach
    </div>
</div>
@endif
