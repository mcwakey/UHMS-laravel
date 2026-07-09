{{-- ============================================================ --}}
{{-- VISIT PREVIEW MODAL --}}
{{-- ============================================================ --}}
<div class="modal fade" id="visitPreviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-clock-history me-2"></i>Visit Summary</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="visitPreviewContent"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.close') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- EDIT CONSULTATION ENTRY MODAL --}}
{{-- ============================================================ --}}
@if($canCreateEntries)
<div class="modal fade" id="editEntryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editEntryForm" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ti ti-edit me-2"></i><span id="editEntryTitle">Edit Entry</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="editEntryErrors" class="alert alert-danger d-none small py-2"></div>
                    <div id="editEntryFields"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- ============================================================ --}}
{{-- SAVE AS PATTERN MODAL --}}
{{-- ============================================================ --}}
@if($canCreateEntries)
<div class="modal fade" id="savePatternModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.patterns.from-record', $visit) }}">
                @csrf
                @if($selectedRoute)
                    <input type="hidden" name="consultation_route_id" value="{{ $selectedRoute->id }}">
                @endif
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ti ti-template me-2"></i>Save as Pattern</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Pattern Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g., Common Cold">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Scope</label>
                        <select name="scope" class="form-select">
                            <option value="personal">{{ __('consultations.pattern_scope_personal') }}</option>
                            <option value="system">{{ __('consultations.pattern_scope_system') }}</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>Save Pattern</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- ============================================================ --}}
{{-- SEND TO ANOTHER CONSULTATION SESSION MODAL --}}
{{-- ============================================================ --}}
@if($visit->status === \App\Enums\VisitStatus::CONSULTING)
<div class="modal fade" id="sendSessionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.consultations.refer', $visit) }}">
                @csrf
                <x-consultation-idempotency-key action="consultation.refer" />
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ti ti-transfer me-2"></i>Send to Another Consultation Session</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @php
                        $historyDeptIds = $visit->departmentHistory->pluck('department_id')->toArray();
                        $currentDeptId  = $visit->current_department_id;
                        $referralDepts  = \App\Models\Department::active()
                            ->where('id', '!=', $currentDeptId)
                            ->where('type', \App\Enums\DepartmentType::CONSULTATION->value)
                            ->orderBy('name')->get();
                        // Pre-load consultation services per referral department so the
                        // service picker can react to the department dropdown without
                        // an extra HTTP call.
                        $referralServicesByDept = \App\Models\ServiceCatalog::where('is_active', true)
                            ->where('category', \App\Enums\ServiceType::CONSULTATION->value)
                            ->whereIn('department_id', $referralDepts->pluck('id'))
                            ->orderBy('name')
                            ->get()
                            ->groupBy('department_id');
                        $referralServicesPayloadByDept = $referralServicesByDept
                            ->map(fn ($services) => $services->map(fn ($service) => [
                                'id' => $service->id,
                                'name' => $service->name,
                                'category' => $service->category,
                            ])->values())
                            ->all();
                    @endphp
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Target Consultation Department <span class="text-danger">*</span></label>
                        <select name="department_id" class="form-select" required id="sendSessionDeptSelect">
                            <option value="">— Select department —</option>
                            @foreach($referralDepts as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Services to add / bill <small class="text-muted">(optional)</small></label>
                        <select name="service_ids[]" class="form-select" id="sendSessionServiceSelect" disabled multiple size="4">
                            <option value="" disabled>{{ __('consultations.select_department_first') }}</option>
                        </select>
                        <small class="text-muted">Services are linked under the target department session and billed once.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Doctor optional</label>
                        <select name="doctor_id" class="form-select" id="sendSessionDoctorSelect" disabled>
                            <option value="">{{ __('consultations.select_department_first') }}</option>
                        </select>
                        <small class="text-muted">Doctors are loaded from specialties linked to the selected department.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason / Notes</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Reason for this consultation session..."></textarea>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="activate_now" value="1" id="activateNewSessionNow">
                        <label class="form-check-label" for="activateNewSessionNow">Create and activate now</label>
                    </div>
                    @if($visit->departmentHistory->isNotEmpty())
                        <div class="alert alert-info py-2 small">
                            <strong>{{ __('consultations.department_history') }}:</strong><br>
                            @foreach($visit->departmentHistory as $hist)
                                <span class="badge bg-{{ $hist->typeColor() }}">{{ $hist->translatedTypeLabel() }}</span>
                                {{ $hist->department?->name }}
                                <span class="badge bg-{{ $hist->statusColor() }}">{{ $hist->translatedStatusLabel() }}</span><br>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" {{ $referralDepts->isEmpty() ? 'disabled' : '' }}>
                        <i class="ti ti-transfer me-1"></i>Create Session
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="investigationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-microscope me-2"></i>Send Investigation Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                {{-- Tab navigation --}}
                <ul class="nav nav-tabs mb-3" id="investModalTabs">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#investTabLabReq">
                            <i class="ti ti-flask me-1"></i>Lab / Imaging Request
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#investTabRoute">
                            <i class="ti ti-arrow-right me-1"></i>Route to Department
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    {{-- Tab 1: Lab Request --}}
                    <div class="tab-pane fade show active" id="investTabLabReq">
                        @can('lab.requests.create')
                        <form id="labRequestForm" data-consultation-form="lab-request" data-modal-form="true" data-refresh-section="investigations" data-route-context-required="true" data-error-target="#labReqErrors" method="POST" action="{{ route('admin.consultations.lab-request.store', $visit) }}">
                            @csrf
                            <x-consultation-idempotency-key action="lab_request.create" />
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Target Department <span class="text-danger">*</span></label>
                                    @if($investigationDepts->isNotEmpty())
                                    <select name="target_department_id" id="labReqDeptSelect" class="form-select" required
                                        data-consultation-action="load-lab-request-items">
                                        <option value="">— Select department —</option>
                                        @foreach($investigationDepts as $dept)
                                        <option value="{{ $dept->id }}"
                                            data-result-type="{{ $dept->result_type?->value }}"
                                            data-uses-catalog="{{ $dept->result_type?->usesTestCatalog() ? 'true' : 'false' }}">
                                            {{ $dept->name }}
                                            <small>({{ $dept->result_type?->translatedLabel() }})</small>
                                        </option>
                                        @endforeach
                                    </select>
                                    @else
                                    <div class="alert alert-warning py-2 mb-0">
                                        <small>{{ __('consultations.no_investigation_departments') }}</small>
                                    </div>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Urgency</label>
                                    <select name="urgency" class="form-select">
                                        <option value="routine">{{ __('consultations.urgency.routine') }}</option>
                                        <option value="urgent">{{ __('consultations.urgency.urgent') }}</option>
                                        <option value="emergency">{{ __('consultations.urgency.emergency') }}</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Clinical Notes</label>
                                    <input type="text" name="clinical_info" class="form-control" placeholder="Clinical indication / notes...">
                                </div>
                            </div>

                            {{-- Items container — shown after dept selected --}}
                            <div id="labReqItemsContainer" class="mt-3 d-none">
                                <label class="form-label fw-semibold" id="labReqItemsLabel">Items <span class="text-danger">*</span></label>
                                <div id="labReqItemsBody">
                                    <span class="text-muted small"><span class="spinner-border spinner-border-sm me-1"></span>Loading...</span>
                                </div>
                            </div>

                            <div id="labReqErrors" class="alert alert-danger py-2 d-none mt-3"></div>

                            <div class="mt-3 d-flex gap-2">
                                <button type="submit" class="btn btn-primary" id="labReqSubmitBtn">
                                    <i class="ti ti-send me-1"></i>Send Request
                                </button>
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            </div>
                        </form>
                        @else
                        <div class="alert alert-warning">You don't have permission to create lab requests.</div>
                        @endcan
                    </div>

                    {{-- Tab 2: Route to Department --}}
                    <div class="tab-pane fade" id="investTabRoute">
                        <form id="routeInvestigationForm" data-consultation-form="route-investigation" data-modal-form="true" data-refresh-section="investigations" data-route-context-required="true" data-error-target="#investRouteErrors" method="POST" action="{{ route('admin.consultations.investigation', $visit) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Investigation Department <span class="text-danger">*</span></label>
                                <select name="department_id" class="form-select" required>
                                    <option value="">— Select department —</option>
                                    @foreach($investigationDepts->isNotEmpty() ? $investigationDepts : \App\Models\Department::active()->orderBy('name')->get() as $dept)
                                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="3" placeholder="Investigation notes..."></textarea>
                            </div>
                            <div id="investRouteErrors" class="alert alert-danger py-2 d-none"></div>
                            <button type="submit" class="btn btn-primary" id="investRouteSubmitBtn">
                                <i class="ti ti-arrow-right me-1"></i>Route Patient to Department
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<div class="offcanvas offcanvas-end consultation-preview-offcanvas" tabindex="-1" id="consultationPreviewOffcanvas" aria-labelledby="consultationPreviewOffcanvasLabel">
    <div class="offcanvas-header border-bottom">
        <div>
            <h5 class="offcanvas-title fw-bold mb-0" id="consultationPreviewOffcanvasLabel">
                <i class="ti ti-history me-1"></i>{{ __('consultations.workspace.preview') }}
            </h5>
            <div class="text-muted small">{{ $visit->visit_number }} &middot; {{ $visit->patient?->full_name }}</div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-primary btn-sm" data-consultation-action="print">
                <i class="ti ti-printer me-1"></i>{{ __('consultations.history.print_summary') }}
            </button>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ __('common.close') }}"></button>
        </div>
    </div>
    <div class="offcanvas-body">
        <x-consultation-preview
            :visit="$consultationPreview['visit']"
            :generated-at="$consultationPreview['generatedAt']"
            :sessions="$consultationPreview['sessions']"
            :contributors="$consultationPreview['contributors']"
            :session-summaries="$consultationPreview['sessionSummaries']"
            :lab-requests="$consultationPreview['labRequests']"
            :procedure-requests="$consultationPreview['procedureRequests']"
        />
    </div>
</div>

{{-- View Result Modal (used by investigations tab) --}}
<div class="modal fade" id="viewResultModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-clipboard-data me-1"></i>Investigation Result</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewResultBody">
                <div class="text-center py-4 text-muted"><div class="spinner-border"></div></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.close') }}</button>
            </div>
        </div>
    </div>
</div>
