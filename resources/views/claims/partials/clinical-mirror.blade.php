@if(!empty($clinicalMirror['records']))
<div class="card mb-3">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="ti ti-stethoscope me-1"></i>{{ __('claims.clinical_mirror_title') }}</h5>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3">{{ __('claims.clinical_mirror_desc') }}</p>
        @foreach($clinicalMirror['records'] as $recordMirror)
            <div class="border rounded p-3 mb-3">
                <div class="small mb-2">
                    <strong>{{ __('common.department') }}:</strong> {{ $recordMirror['department'] ?? '-' }}
                    <span class="ms-2"><strong>{{ __('consultations.main_doctor_label') }}:</strong> {{ $recordMirror['main_doctor'] ?? __('consultations.unassigned') }}</span>
                    <span class="ms-2"><strong>{{ __('claims.contributors_label') }}:</strong> {{ collect($recordMirror['contributors'] ?? [])->implode(', ') ?: '-' }}</span>
                </div>
                @foreach([
                    'complaints' => __('claims.sec_complaints'),
                    'history_of_presenting_complaint' => __('claims.sec_hopc'),
                    'examination' => __('claims.sec_examination'),
                    'diagnoses' => __('claims.sec_diagnosis'),
                    'investigations' => __('claims.sec_investigations'),
                    'treatments' => __('claims.sec_treatments'),
                    'prescriptions' => __('claims.sec_prescriptions'),
                    'procedures' => __('claims.sec_procedures'),
                    'tasks' => __('claims.sec_tasks'),
                    'notes' => __('claims.sec_notes'),
                ] as $key => $label)
                    @if(!empty($recordMirror['sections'][$key]))
                        <h6 class="small fw-bold text-muted border-bottom pb-1 mt-3">{{ $label }}</h6>
                        @foreach($recordMirror['sections'][$key] as $entry)
                            <div class="ps-2 mb-2 border-start border-3">
                                <div>{{ $entry['content'] }}</div>
                                <small class="text-muted">
                                    {{ __('claims.original_author') }}: {{ $entry['entered_by'] }}
                                    @if($entry['created_at']) · {{ $entry['created_at']->format('d M Y, h:i A') }} @endif
                                    @if($entry['source_pattern']) · {{ __('claims.source_pattern') }}: {{ $entry['source_pattern'] }} @endif
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
