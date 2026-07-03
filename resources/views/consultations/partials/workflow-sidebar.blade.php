{{-- =================== LEFT SIDEBAR =================== --}}
            <div class="col-lg-12 consultation-side-column consultation-actions-column">
                <div class="card mb-3">
                    <div class="card-body p-2">
                        <nav class="consultation-sidebar">
                            <ul class="nav flex-column gap-1" id="consultationTabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="tab-complaints" href="#complaints-section" data-bs-toggle="pill" role="tab">
                                        <i class="ti ti-message-report me-1"></i>{{ __('consultations.workspace.presenting_complaints') }}
                                        <span class="badge bg-secondary-subtle text-secondary ms-auto" id="badge-complaints">{{ $record?->complaints?->count() ?? 0 }}</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="tab-hopc" href="#hopc-section" data-bs-toggle="pill" role="tab" aria-label="HOPC">
                                        <i class="ti ti-file-description me-1"></i>{{ __('consultations.workspace.hopc') }}
                                        <span class="badge bg-secondary-subtle text-secondary ms-auto" id="badge-hopc">{{ $record?->historiesOfPresentingComplaint?->count() ?? 0 }}</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="tab-examination" href="#examination-section" data-bs-toggle="pill" role="tab">
                                        <i class="ti ti-zoom-check me-1"></i>{{ __('consultations.workspace.examination') }}
                                        <span class="badge bg-secondary-subtle text-secondary ms-auto" id="badge-examination">{{ $record?->physicalExaminations?->count() ?? 0 }}</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="tab-diagnoses" href="#diagnoses-section" data-bs-toggle="pill" role="tab">
                                        <i class="ti ti-report-medical me-1"></i>{{ __('consultations.workspace.diagnoses') }}
                                        <span class="badge bg-secondary-subtle text-secondary ms-auto" id="badge-diagnoses">{{ $record?->diagnoses?->count() ?? 0 }}</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="tab-investigations" href="#investigations-section" data-bs-toggle="pill" role="tab">
                                        <i class="ti ti-test-pipe me-1"></i>{{ __('consultations.workspace.investigations') }}
                                        <span class="badge bg-secondary-subtle text-secondary ms-auto" id="badge-investigations">{{ $record?->investigations?->count() ?? 0 }}</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="tab-treatments" href="#treatments-section" data-bs-toggle="pill" role="tab">
                                        <i class="ti ti-vaccine me-1"></i>{{ __('consultations.workspace.treatments') }}
                                        <span class="badge bg-secondary-subtle text-secondary ms-auto" id="badge-treatments">{{ $record?->treatments?->count() ?? 0 }}</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="tab-prescriptions" href="#prescriptions-section" data-bs-toggle="pill" role="tab">
                                        <i class="ti ti-prescription me-1"></i>{{ __('consultations.workspace.prescriptions') }}
                                        <span class="badge bg-secondary-subtle text-secondary ms-auto" id="badge-prescriptions">{{ $record?->prescriptions?->count() ?? 0 }}</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="tab-procedures" href="#procedures-section" data-bs-toggle="pill" role="tab">
                                        <i class="ti ti-activity-heartbeat me-1"></i>{{ __('consultations.workspace.procedures') }}
                                        <span class="badge bg-secondary-subtle text-secondary ms-auto" id="badge-procedures">{{ $procedureRequests->count() }}</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="tab-tasks" href="#tasks-section" data-bs-toggle="pill" role="tab">
                                        <i class="ti ti-checklist me-1"></i>{{ __('consultations.workspace.tasks') }}
                                        <span class="badge bg-secondary-subtle text-secondary ms-auto" id="badge-tasks">{{ $record?->tasks?->count() ?? 0 }}</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="tab-summary" href="#summary-section" data-bs-toggle="pill" role="tab">
                                        <i class="ti ti-notes me-1"></i>{{ __('consultations.workspace.notes_summary') }}
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="tab-patterns" href="#patterns-section" data-bs-toggle="pill" role="tab">
                                        <i class="ti ti-template me-1"></i>{{ __('consultations.workspace.patterns') }}
                                        <span class="badge bg-secondary-subtle text-secondary ms-auto">{{ $patterns->count() }}</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>

                <div class="card consultation-quick-actions">
                    <div class="card-header py-2">
                        <h6 class="fw-bold mb-0 small">{{ __('consultations.workspace.quick_actions') }}</h6>
                    </div>
                    <div class="card-body p-2">
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="offcanvas" data-bs-target="#consultationPreviewOffcanvas" aria-controls="consultationPreviewOffcanvas">
                                <i class="ti ti-history me-1"></i>{{ __('consultations.workspace.preview') }}
                            </button>
                            <a href="{{ route('admin.visits.show', $visit) }}" class="btn btn-outline-secondary btn-sm">
                                <i class="ti ti-eye me-1"></i>{{ __('consultations.workspace.view_visit') }}
                            </a>
                            <!-- <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#followUpAppointmentModal" @disabled(! $selectedRoute) title="{{ $selectedRoute ? __('consultations.workspace.set_next_appointment') : __('consultations.workspace.select_session_first') }}">
                                <i class="ti ti-calendar-plus me-1"></i>{{ $followUpAppointment ? __('consultations.workspace.update_next_appointment') : __('consultations.workspace.next_appointment') }}
                                @if($followUpAppointment)
                                    <span class="badge bg-primary-subtle text-primary ms-1">{{ __('consultations.workspace.set') }}</span>
                                @endif
                            </button> -->
                            @can('consultations.create')
                            <button type="button" class="btn btn-outline-purple btn-sm" data-bs-toggle="modal" data-bs-target="#savePatternModal">
                                <i class="ti ti-template me-1"></i>{{ __('consultations.workspace.save_pattern') }}
                            </button>
                            @endcan
                            @if($visit->status->allowedTransitions())
                            <hr class="my-1">
                            <small class="text-muted fw-bold px-1">{{ __('consultations.workspace.transition_visit') }}</small>
                            @foreach($visit->status->allowedTransitions() as $nextStatus)
                                @if($nextStatus === \App\Enums\VisitStatus::ADMITTING)
                                {{-- Special admit button → go straight to admission form --}}
                                <form method="POST" action="{{ route('admin.consultations.transition', $visit) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $nextStatus->value }}">
                                    <button type="submit" class="btn btn-warning btn-sm w-100"
                                            data-confirm="{{ __('consultations.confirm_admit_patient') }}">
                                        <i class="ti ti-bed me-1"></i>{{ __('consultations.workspace.admit_patient') }}
                                    </button>
                                </form>
                                @elseif($nextStatus === \App\Enums\VisitStatus::COMPLETED)

                                <form method="POST" action="{{ route('admin.consultations.transition', $visit) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $nextStatus->value }}">
                                    <button type="submit" class="btn btn-success btn-sm w-100"
                                            data-confirm="{{ __('consultations.confirm_complete') }}">
                                        <i class="ti ti-check me-1"></i>{{ __('consultations.workspace.complete_consultation') }}
                                    </button>
                                </form>
                                @elseif($nextStatus === \App\Enums\VisitStatus::CANCELLED)

                                <form method="POST" action="{{ route('admin.consultations.transition', $visit) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $nextStatus->value }}">
                                    <button type="submit" class="btn btn-danger btn-sm w-100"
                                            data-confirm="{{ __('consultations.confirm_cancel') }}">
                                        <i class="ti ti-trash me-1"></i>{{ __('consultations.workspace.cancel_consultation') }}
                                    </button>
                                </form>

                                {{-- @else
                                <form method="POST" action="{{ route('admin.consultations.transition', $visit) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $nextStatus->value }}">
                                    <button type="submit" class="btn btn-{{ $nextStatus->color() }} btn-sm w-100"
                                            data-confirm="Move to {{ $nextStatus->translatedLabel() }}?">
                                        <i class="ti ti-arrow-right me-1"></i>{{ $nextStatus->translatedLabel() }}
                                    </button>
                                </form> --}}
                                @endif
                            @endforeach
                            @endif
                            @if($visit->status === \App\Enums\VisitStatus::CONSULTING)
                            <hr class="my-1">
                            <small class="text-muted fw-bold px-1">{{ __('consultations.workspace.session_routing') }}</small>
                            <button type="button" class="btn btn-outline-indigo btn-sm w-100 mb-1" data-bs-toggle="modal" data-bs-target="#sendSessionModal">
                                <i class="ti ti-transfer me-1"></i>{{ __('consultations.workspace.transfer_session') }}
                            </button>
                            {{-- <button type="button" class="btn btn-outline-purple btn-sm w-100" data-bs-toggle="modal" data-bs-target="#investigationModal">
                                <i class="ti ti-test-pipe me-1"></i>Send to Invest.
                            </button> --}}
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            
