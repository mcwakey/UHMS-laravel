{{-- =================== LEFT SIDEBAR =================== --}}
            <div class="col-lg-12 consultation-side-column consultation-actions-column">
                <div class="card mb-3">
                    <div class="card-body p-2">
                        <nav class="consultation-sidebar">
                            <ul class="nav flex-column gap-1" id="consultationTabs" role="tablist">
                                @php
                                    $badgeForSection = function (array $section) use ($record, $procedureRequests, $specialtyEntries, $specialtyEntryGroups) {
                                        $specialtyEntryCount = isset($specialtyEntryGroups)
                                            ? collect($specialtyEntryGroups[$section['key']] ?? [])->count()
                                            : (! empty($specialtyEntries[$section['key']] ?? []) ? 1 : 0);

                                        return match ($section['canonical_key'] ?? $section['key']) {
                                            'complaints' => ['id' => 'badge-complaints', 'count' => $record?->complaints?->count() ?? 0],
                                            'hopc' => ['id' => 'badge-hopc', 'count' => $record?->historiesOfPresentingComplaint?->count() ?? 0],
                                            'examination' => ['id' => 'badge-examination', 'count' => $record?->physicalExaminations?->count() ?? 0],
                                            'diagnosis' => ['id' => 'badge-diagnoses', 'count' => $record?->diagnoses?->count() ?? 0],
                                            'investigations' => ['id' => 'badge-investigations', 'count' => $record?->investigations?->count() ?? 0],
                                            'prescription' => ['id' => 'badge-prescriptions', 'count' => $record?->prescriptions?->count() ?? 0],
                                            'procedures' => ['id' => 'badge-procedures', 'count' => $procedureRequests->count()],
                                            'tasks' => ['id' => 'badge-tasks', 'count' => $record?->tasks?->count() ?? 0],
                                            default => ['id' => 'badge-specialty-'.$section['key'], 'count' => $specialtyEntryCount],
                                        };
                                    };
                                @endphp
                                @foreach($layoutTabSections as $section)
                                @php
                                    $sectionTitle = (($specialtyLayout['profile']['code'] ?? null) === 'general_medicine' && ($section['tab_target'] ?? null) === 'summary-section')
                                        ? __('consultations.workspace.notes_summary')
                                        : ($section['translated_label'] ?? $section['label']);
                                @endphp
                                <li class="nav-item">
                                    <a class="nav-link {{ $activeTabTarget === $section['tab_target'] ? 'active' : '' }}" id="tab-{{ $section['key'] }}" href="#{{ $section['tab_target'] }}" data-bs-toggle="pill" role="tab">
                                        <i class="ti {{ $section['icon'] ?? 'ti-layout-board' }} me-1"></i>{{ $sectionTitle }}
                                        @if($section['is_required'] ?? false)
                                            <span class="badge bg-warning-subtle text-warning ms-1">{{ __('consultation_specialties.workspace.required') }}</span>
                                        @endif
                                        @php($badge = $badgeForSection($section))
                                        @if($badge)
                                            <span class="badge bg-secondary-subtle text-secondary ms-auto" id="{{ $badge['id'] }}">{{ $badge['count'] }}</span>
                                        @endif
                                    </a>
                                </li>
                                @endforeach
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
            
