{{-- ============================================================ --}}
{{-- VISIT HISTORY JSON + JS CONFIG --}}
{{-- ============================================================ --}}
@php
$visitHistoryJson = $history['records']->map(function($r) {
    return [
        'visit_number'   => $r->visit?->visit_number ?? 'N/A',
        'date'           => $r->created_at->format('d M Y'),
        'doctor'         => $r->visit?->currentConsultationDoctor()?->full_name ?? null,
        'complaints'     => $r->complaints->map(function($c) { return $c->description; })->values()->all(),
        'diagnoses'      => $r->diagnoses->map(function($d) {
            return [
                'description' => $d->description,
                'type'        => $d->type,
                'is_primary'  => $d->is_primary ?? false,
                'icd_code'    => $d->icd_code,
            ];
        })->values()->all(),
        'investigations' => $r->investigations->map(function($i) {
            return [
                'type'        => $i->investigation_type,
                'description' => $i->description,
                'urgency'     => $i->urgency,
            ];
        })->values()->all(),
        'treatments'     => $r->treatments->map(function($t) {
            return [
                'type'        => $t->type,
                'description' => $t->description,
            ];
        })->values()->all(),
    ];
})->values();
@endphp

@push('scripts')
@php
    $consultationPageConfig = [
        'csrfToken' => csrf_token(),
        'visitId' => $visit->id,
        'tabStorageKey' => 'consult_tab_'.$visit->id,
        'currentRouteId' => $selectedRoute?->id,
        'canEdit' => $canEdit,
        'currentUser' => auth()->user() ? [
            'id' => auth()->id(),
            'full_name' => auth()->user()->full_name,
            'roles' => auth()->user()->getRoleNames()->values(),
        ] : null,
        'taskAssignableUsers' => $doctors->map(fn($doctor) => ['id' => $doctor->id, 'name' => 'Dr. '.$doctor->full_name])->values(),
        'frequencyDoseMap' => $frequencyDoseMap ?? [],
        'specialtyFavorites' => $specialtyFavorites ?? [],
        'specialtyOrderSets' => $specialtyOrderSets ?? [],
        'specialtyReadiness' => isset($specialtyReadiness) && $specialtyReadiness ? $specialtyReadiness->toArray() : null,
        'specialtySummaryBuilder' => $specialtySummaryBuilder ?? ['available' => false],
        'doctorSpecialtyWorkspace' => isset($doctorSpecialtyWorkspace) && $doctorSpecialtyWorkspace ? $doctorSpecialtyWorkspace->toArray() : null,
        'sendSessionServicesByDept' => $referralServicesPayloadByDept ?? [],
        'visitHistoryData' => $visitHistoryJson,
        'openFollowUpSectionOnLoad' => $errors->has('appointment_date') || $errors->has('start_time') || $errors->has('end_time') || $errors->has('department_id') || $errors->has('service_id') || $errors->has('doctor_id') || $errors->has('reason') || $errors->has('notes') || $errors->has('priority'),
        'routes' => [
            'summaryFragment' => route('admin.consultations.summary-fragment', $visit),
            'readinessFragment' => route('admin.consultations.readiness-fragment', $visit),
            'deptServicesBase' => url('admin/departments'),
            'procedureDeptServicesBase' => url('admin/theatre/departments'),
            'departmentVisitOptions' => route('admin.departments.visit-options', ['department' => '__ID__']),
            'patternsBase' => url('admin/patterns'),
            'patternSuggest' => route('admin.patterns.suggest'),
            'icdSearch' => route('admin.icd-search'),
            'suggest' => [
                'complaint' => route('admin.consultations.suggest.complaints'),
                'diagnosis' => route('admin.consultations.suggest.diagnoses'),
            ],
        ],
        'translations' => [
            'selectDepartmentFirst' => __('consultations.select_department_first'),
            'loadingServices' => __('consultations.loading_services'),
            'loadingDoctors' => __('consultations.loading_doctors'),
            'unableLoadServices' => __('consultations.unable_load_services'),
            'unableLoadDoctors' => __('consultations.unable_load_doctors'),
            'deleteFailed' => __('consultations.delete_failed'),
            'updateTypeFailed' => __('consultations.update_type_failed'),
            'setPrimaryFailed' => __('consultations.set_primary_failed'),
            'loading' => __('consultations.loading'),
            'noProcedureServices' => __('consultations.no_procedure_services'),
            'searchServices' => __('consultations.search_services'),
            'noResultsFound' => __('consultations.no_results_found'),
            'searchProcedureService' => __('consultations.search_procedure_service'),
            'searchIcd10' => __('consultations.search_icd10'),
            'markDiagnosisFinal' => __('consultations.mark_this_diagnosis_as_final'),
            'ajax' => [
                'validation_failed' => __('consultations.ajax.validation_failed'),
                'session_expired' => __('consultations.ajax.session_expired'),
                'forbidden' => __('consultations.ajax.forbidden'),
                'network_error' => __('consultations.ajax.network_error'),
                'server_error' => __('consultations.ajax.server_error'),
                'section_refresh_failed' => __('consultations.ajax.section_refresh_failed'),
                'duplicate_replayed' => __('consultations.ajax.duplicate_replayed'),
                'route_context_missing' => __('consultations.ajax.route_context_missing'),
                'submit_in_progress' => __('consultations.ajax.submit_in_progress'),
                'saved_successfully' => __('consultations.ajax.saved_successfully'),
            ],
            'modal' => [
                'loading' => __('consultations.modal.loading'),
                'close_confirm' => __('consultations.modal.close_confirm'),
            ],
        ],
    ];
@endphp
<script type="application/json" id="consultation-page-config">
{!! json_encode($consultationPageConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
</script>
@vite('resources/js/Pages/consultation-show.js')
@endpush
