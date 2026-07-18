{{-- =================== RIGHT PANEL — PREVIOUS VISITS =================== --}}
            <div class="col-lg-12 consultation-side-column">
                @include('consultations.partials.completion-readiness-card')

                <div class="card mb-3">
                    <div class="card-header py-2 d-flex align-items-center justify-content-between">
                        <h6 class="fw-bold mb-0 small"><i class="ti ti-user-forward me-1"></i>{{ __('consultations.workspace.next_patient_in_line') }}</h6>
                        @if($nextPatientInLine)
                            <span class="badge bg-{{ $nextPatientInLine['priority_color'] ?? 'secondary' }}">{{ $nextPatientInLine['priority'] ?? 'Normal' }}</span>
                        @endif
                    </div>
                    <div class="card-body p-3">
                        @if($nextPatientInLine)
                            <div class="d-flex align-items-start justify-content-between gap-2 mb-1">
                                <div class="fw-semibold">{{ $nextPatientInLine['patient_name'] }}</div>
                                <span class="badge bg-soft-primary text-primary flex-shrink-0">{{ __('consultations.workspace.queue_number', ['number' => $nextPatientInLine['queue_number']]) }}</span>
                            </div>
                            <div class="small text-muted mb-2">
                                {{ $nextPatientInLine['patient_number'] ?: __('consultations.workspace.no_patient_number') }}
                                @if($nextPatientInLine['visit_number'])
                                    &middot; {{ $nextPatientInLine['visit_number'] }}
                                @endif
                            </div>
                            <div class="small mb-2">
                                @if($nextPatientInLine['age'])
                                    <span class="badge bg-light text-dark border">{{ __('consultations.workspace.age_value', ['age' => $nextPatientInLine['age']]) }}</span>
                                @endif
                                @if($nextPatientInLine['gender'])
                                    <span class="badge bg-light text-dark border">{{ __('common.gender_'.strtolower($nextPatientInLine['gender'])) }}</span>
                                @endif
                            </div>
                            <div class="small text-muted">
                                <div><i class="ti ti-clock me-1"></i>{{ __('consultations.workspace.waiting_minutes', ['count' => $nextPatientInLine['waiting_minutes'] ?? 0]) }}</div>
                                <div><i class="ti ti-building-hospital me-1"></i>{{ $nextPatientInLine['department'] ?: __('consultations.workspace.consultation_department') }}</div>
                                @if(! empty($nextPatientInLine['services']))
                                    <div><i class="ti ti-stethoscope me-1"></i>{{ implode(', ', $nextPatientInLine['services']) }}</div>
                                @endif
                                @if($nextPatientInLine['doctor'])
                                    <div><i class="ti ti-user-heart me-1"></i>{{ __('consultations.workspace.assigned_doctor', ['name' => $nextPatientInLine['doctor']]) }}</div>
                                @endif
                            </div>
                            <div class="mt-2">
                                <span class="badge bg-{{ $nextPatientInLine['payment_allowed'] ? 'success' : 'warning text-dark' }}">
                                    <i class="ti ti-credit-card me-1"></i>{{ $nextPatientInLine['payment_message'] }}
                                </span>
                            </div>
                            @if($canCreateEntries)
                                <div class="d-grid gap-2 mt-3">
                                    <form method="POST" action="{{ $workspaceRoutes->route('admin.consultations.routes.next-patient.open', [$visit, $selectedRoute]) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-primary btn-sm w-100" @disabled(! $nextPatientInLine['payment_allowed']) title="{{ $nextPatientInLine['payment_allowed'] ? __('consultations.workspace.open_next_patient') : $nextPatientInLine['payment_message'] }}">
                                            <i class="ti ti-arrow-right me-1"></i>{{ __('consultations.workspace.open_next_patient') }}
                                        </button>
                                    </form>
                                    <!-- <x-confirm-form
                                        :action="$workspaceRoutes->route('admin.consultations.routes.next-patient.complete-open', [$visit, $selectedRoute])"
                                        method="POST"
                                        :button-label="__('consultations.workspace.complete_and_open_next')"
                                        button-class="btn btn-success btn-sm w-100"
                                        icon="ti-check"
                                        :confirm-title="__('consultations.complete_open_next_confirm')"
                                        :confirm-text="__('consultations.workspace.complete_and_open_help')"
                                        :confirm-button="__('consultations.workspace.complete_and_open')"
                                        :disabled="! $nextPatientInLine['payment_allowed']"
                                        :disabled-reason="$nextPatientInLine['payment_message']"
                                    /> -->
                                </div>
                            @endif
                        @else
                            <x-empty-state icon="ti-users-off" :title="__('consultations.no_patient_waiting')" :message="__('consultations.workspace.no_patient_waiting_help')" />
                        @endif
                    </div>
                </div>

                <!-- <div class="card">
                    @php($canUseFollowUpQuickAction = $canCreateEntries && $selectedRoute)
                    <button type="button"
                       class="btn btn-outline-primary btn-sm {{ $canUseFollowUpQuickAction ? '' : 'disabled' }}"
                       @if($canUseFollowUpQuickAction) data-consultation-action="open-follow-up-modal" @endif
                       @disabled(! $canUseFollowUpQuickAction)
                       aria-disabled="{{ $canUseFollowUpQuickAction ? 'false' : 'true' }}"
                       title="{{ $selectedRoute ? __('consultations.workspace.set_next_appointment') : __('consultations.workspace.select_session_first') }}">
                        <i class="ti ti-calendar-plus me-1"></i>{{ $followUpAppointment ? __('consultations.workspace.update_next_appointment') : __('consultations.workspace.next_appointment') }}
                        @if($followUpAppointment)
                            <span class="badge bg-primary-subtle text-primary ms-1">{{ __('consultations.workspace.set') }}</span>
                        @endif
                    </button>
                </div> -->

                <div class="card">
                    <div class="card-header py-2">
                        <h6 class="fw-bold mb-0 small"><i class="ti ti-clock-history me-1"></i>{{ __('consultations.workspace.previous_visits') }}
                            @if($history['total'] > 0) <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $history['total'] }}</span> @endif
                        </h6>
                    </div>
                    <div class="card-body p-2" style="max-height:600px;overflow-y:auto;">
                        @if(count($history['records']) > 0)
                            @foreach($history['records'] as $index => $pastRecord)
                            <div class="prev-visit-card border rounded p-2 mb-2">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-bold small">{{ $pastRecord->visit?->visit_number ?? 'N/A' }}</div>
                                        <small class="text-muted d-block">{{ $pastRecord->created_at->format('d M Y') }}</small>
                                        @if($pastRecord->visit?->currentConsultationDoctor())
                                            <small class="text-muted d-block">Dr. {{ Str::limit($pastRecord->visit->currentConsultationDoctor()->full_name, 18) }}</small>
                                        @endif
                                        <small class="text-muted d-block">
                                            {{ trans_choice('consultations.workspace.complaint_count', $pastRecord->complaints->count(), ['count' => $pastRecord->complaints->count()]) }} &middot; {{ $pastRecord->diagnoses->count() }} dx
                                        </small>
                                    </div>
                                    @can('consultation.preview')
                                        <button type="button" class="btn btn-xs btn-outline-primary flex-shrink-0"
                                                data-consultation-action="preview-history"
                                                data-url="{{ $workspaceRoutes->route('admin.consultations.history', $pastRecord->visit) }}"
                                                aria-label="{{ __('common.view') }}" title="{{ __('common.view') }}">
                                            <i class="ti ti-eye"></i>
                                        </button>
                                    @endcan
                                </div>
                            </div>
                            @endforeach
                            @if($history['total'] > 10)
                            @can('consultation.preview')
                            <div class="text-center mt-1">
                                <a href="{{ $workspaceRoutes->route('admin.consultations.history', $visit) }}" class="btn btn-sm btn-outline-secondary w-100">
                                    {{ trans_choice('consultations.workspace.more_visits', $history['total'] - 10, ['count' => $history['total'] - 10]) }}
                                </a>
                            </div>
                            @endcan
                            @endif
                        @else
                            <div class="text-center text-muted py-3">
                                <i class="ti ti-clock fs-3 d-block mb-1"></i>
                                <small>{{ __('consultations.no_previous_visits') }}</small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
