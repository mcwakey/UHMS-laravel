<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\ServiceRendering;
use App\Models\Visit;
use Illuminate\Support\Carbon;

/**
 * Builds a chronological clinical timeline for a given Visit.
 *
 * All timeline items are normalised into a common array shape so the Blade
 * partial can loop through them without any extra logic:
 *
 *   [
 *     'datetime'   => Carbon|string|null,
 *     'date_label' => '21 May 2026',
 *     'time_label' => '09:30 AM',
 *     'title'      => 'Triage completed',
 *     'description'=> 'Vitals recorded …',
 *     'entered_by' => 'Nurse Ama',
 *     'department' => 'Triage',
 *     'badge'      => 'TRIAGE',
 *     'badge_class'=> 'bg-info',
 *     'details'    => [],          // optional key-value pairs shown below desc
 *     'source_type'=> 'triage',
 *     'source_id'  => 1,
 *   ]
 */
class VisitPreviewService
{
    /**
     * Eager-load all relationships we need and return the enriched visit plus
     * a chronological timeline and a quick summary array.
     *
     * @return array{visit: Visit, timeline: array, summary: array}
     */
    public function build(Visit $visit): array
    {
        $this->eagerLoad($visit);

        $timeline = $this->buildTimeline($visit);
        $summary = $this->buildSummary($visit);

        return compact('visit', 'timeline', 'summary');
    }

    /* ──────────────────────────────────────────────────────────────
     |  Eager-loading
     | ────────────────────────────────────────────────────────────*/

    private function eagerLoad(Visit $visit): void
    {
        $visit->loadMissing([
            'patient',
            'activeConsultationRoute.doctor',
            'pendingConsultationRoutes.doctor',
            'createdBy',
            'currentDepartment',
            'pathwayEvents.department',
            'pathwayEvents.createdBy',
            'visitInsurance.insuranceProvider',
            'statusLogs.changedBy',
            'emergencyCase.patient',
            'emergencyCase.bay',
            'emergencyCase.assignedDoctor',
            'emergencyCase.assignedNurse',
            'emergencyCase.activeEmergencySession.mainDoctor',
            'emergencyCase.activeEmergencySession.primaryNurse',
            'emergencyCase.activeEmergencySession.department',
            'emergencyCase.activeEmergencySession.startedBy',
            'emergencyCase.activeEmergencySession.contributors.user',
            'emergencyCase.emergencySessions.mainDoctor',
            'emergencyCase.emergencySessions.primaryNurse',
            'emergencyCase.emergencySessions.department',
            'emergencyCase.emergencySessions.startedBy',
            'emergencyCase.emergencySessions.endedBy',
            'emergencyCase.emergencySessions.contributors.user',
            'emergencyCase.emergencySessions.consultationRoute.medicalRecord',
            'emergencyCase.bayAssignments.ward',
            'emergencyCase.bayAssignments.bed',
            'emergencyCase.bayAssignments.emergencyBay',
            'emergencyCase.bayAssignments.assignedBy',
            'emergencyCase.createdBy',
            'emergencyCase.triagedBy',
            'emergencyCase.disposedBy',
            'emergencyCase.logs.performedBy',
            'emergencyCase.notes.creator',
            'emergencyCase.consumableUsages.product',
            'emergencyCase.consumableUsages.user',
            'emergencyCase.clinicalTasks.assignedUser',
            'emergencyCase.clinicalTasks.completedBy',
            'triage.triagedBy',
            'triage.department',
            'vitals.recordedBy',
            'medicalRecord.doctor',
            'medicalRecord.complaints.creator',
            'medicalRecord.complaints.complaintCatalogue',
            'medicalRecord.historiesOfPresentingComplaint.creator',
            'medicalRecord.physicalExaminations.creator',
            'medicalRecord.diagnoses.creator',
            'medicalRecord.treatments.creator',
            'medicalRecord.prescriptions.creator',
            'medicalRecord.prescriptions.items',
            'medicalRecord.tasks.creator',
            'medicalRecords.doctor',
            'medicalRecords.department',
            'medicalRecords.service',
            'medicalRecords.consultationRoute.department',
            'medicalRecords.consultationRoute.service',
            'medicalRecords.consultationRoute.routeServices.service',
            'medicalRecords.complaints.creator',
            'medicalRecords.complaints.sourcePattern',
            'medicalRecords.complaints.complaintCatalogue',
            'medicalRecords.historiesOfPresentingComplaint.creator',
            'medicalRecords.historiesOfPresentingComplaint.sourcePattern',
            'medicalRecords.physicalExaminations.creator',
            'medicalRecords.physicalExaminations.sourcePattern',
            'medicalRecords.diagnoses.creator',
            'medicalRecords.diagnoses.sourcePattern',
            'medicalRecords.treatments.creator',
            'medicalRecords.treatments.sourcePattern',
            'medicalRecords.prescriptions.creator',
            'medicalRecords.prescriptions.items',
            'medicalRecords.prescriptions.sourcePattern',
            'medicalRecords.tasks.creator',
            'medicalRecords.tasks.assignedUser',
            'medicalRecords.tasks.sourcePattern',
            'consultationRoutes.department',
            'consultationRoutes.service',
            'consultationRoutes.routeServices.service',
            'consultationRoutes.doctor',
            'consultationRoutes.mainDoctor',
            'consultationRoutes.primaryNurse',
            'consultationRoutes.emergencyCase',
            'consultationRoutes.emergencySession',
            'consultationRoutes.routedBy',
            'consultationRoutes.logs.performedBy',
            'appointments.department',
            'appointments.doctor',
            'appointments.services',
            'appointments.createdBy',
            'appointments.consultationRoute.department',
            'labRequests.requestedBy',
            'labRequests.department',
            'labRequests.items',
            'labRequests.results',
            'prescriptions.doctor',
            'prescriptions.dispensingRecords.dispensedBy',
            'prescriptions.items',
            'medicationOrders.frequency',
            'medicationOrders.prescriber',
            'medicationOrders.schedules.clinicalTask',
            'medicationOrders.administrations.administeredBy',
            'serviceRenderings.service',
            'serviceRenderings.department',
            'serviceRenderings.invoiceItem.invoice',
            'serviceRenderings.startedBy',
            'serviceRenderings.renderedBy',
            'serviceRenderings.createdBy',
            'serviceRenderings.updatedBy',
            'invoices.items',
            'invoices.payments.receivedBy',
            'admission.admittedBy',
            'admission.dischargedBy',
            'admission.bed.ward',
            'procedureRequests.requestingDoctor',
            'procedureRequests.department',
            'procedureRequests.service',
            'procedureRequests.acceptedBy',
            'procedureRequests.completedBy',
            'procedureRequests.schedule',
            'procedureRequests.anaesthesiaNote.anaesthetist',
            'procedureRequests.operativeNote',
            'procedureRequests.postOpNote',
            'bloodRequests.requestedBy',
            'bloodRequests.approvedBy',
            'bloodRequests.department',
            'bloodRequests.recipient',
            'bloodRequests.crossmatches.unit',
            'bloodRequests.crossmatches.performedBy',
            'bloodRequests.issues.unit',
            'bloodRequests.issues.issuedBy',
            'bloodRequests.issues.receivedBy',
            'bloodRequests.issues.transfusedBy',
            'bloodIssues.unit',
            'bloodIssues.issuedBy',
            'bloodIssues.receivedBy',
            'bloodIssues.transfusedBy',
        ]);
    }

    /* ──────────────────────────────────────────────────────────────
     |  Timeline builder
     | ────────────────────────────────────────────────────────────*/

    private function buildTimeline(Visit $visit): array
    {
        $items = [];

        // 1. Visit checked in / created
        $items[] = $this->item(
            $visit->checked_in_at ?? $visit->created_at,
            'Visit Registered',
            'Patient registered and visit opened.',
            optional($visit->createdBy)->full_name,
            optional($visit->currentDepartment)->name,
            'CHECK-IN', 'bg-primary',
            'visit', $visit->id,
            [
                'Visit Number' => $visit->visit_number,
                'Visit Type' => $visit->visit_type?->label() ?? '—',
                'Chief Complaint' => $visit->chief_complaint ?: '—',
            ]
        );

        foreach ($visit->pathwayEvents ?? [] as $event) {
            $items[] = $this->item(
                $event->started_at ?? $event->created_at,
                $event->title,
                $event->description ?: $this->formatStatus($event->event_type),
                optional($event->createdBy)->full_name,
                optional($event->department)->name,
                'PATHWAY',
                'bg-info',
                $event->source_type ?: 'visit_pathway_event',
                $event->source_id ?: $event->id,
                [
                    'Event' => $this->formatStatus($event->event_type),
                    'Status' => $event->status ? $this->formatStatus($event->status) : '—',
                    'Completed At' => $event->completed_at ? $event->completed_at->format('d M Y, h:i A') : '—',
                ]
            );
        }

        // 2. Status logs
        foreach ($visit->statusLogs ?? [] as $log) {
            $items[] = $this->item(
                $log->timestamp,
                'Status changed to '.$this->formatStatus($log->to_status),
                $log->notes ?: ('Status changed from '.$this->formatStatus($log->from_status).' to '.$this->formatStatus($log->to_status)),
                optional($log->changedBy)->full_name,
                null,
                strtoupper($this->formatStatus($log->to_status)), 'bg-secondary',
                'status_log', $log->id
            );
        }

        if ($case = $visit->emergencyCase) {
            $items[] = $this->item(
                $case->arrival_time ?? $case->created_at,
                'Emergency Case Opened',
                trim(($case->chief_complaint ? "Chief complaint: {$case->chief_complaint}. " : '').($case->initial_condition ?: 'Emergency encounter started.')),
                optional($case->createdBy)->full_name,
                'Emergency',
                'EMERGENCY', 'bg-danger',
                'emergency_case', $case->id,
                array_filter([
                    'Emergency Number' => $case->emergency_number,
                    'Arrival Mode' => str_replace('_', ' ', (string) $case->arrival_mode),
                    'Bay' => $case->bay?->name,
                    'Doctor' => $case->assignedDoctor?->full_name,
                    'Nurse' => $case->assignedNurse?->full_name,
                ])
            );

            if ($case->triaged_at) {
                $items[] = $this->item(
                    $case->triaged_at,
                    'Emergency Triage Completed',
                    $case->triage_notes ?: 'Emergency triage recorded. Automated category: '.($case->auto_triage_category ?: '—').'.',
                    optional($case->triagedBy)->full_name,
                    'Emergency',
                    $case->current_triage_category ?: 'TRIAGE', 'bg-danger',
                    'emergency_case', $case->id,
                    array_filter([
                        'Automated Category' => $case->auto_triage_category,
                        'Final Category' => $case->current_triage_category,
                        'Score' => $case->triage_score,
                        'Override Reason' => $case->triage_override_reason,
                    ])
                );
            }

            $emergencySessions = $case->emergencySessions && $case->emergencySessions->isNotEmpty()
                ? $case->emergencySessions
                : collect($case->activeEmergencySession ? [$case->activeEmergencySession] : []);

            foreach ($emergencySessions as $session) {
                $contributors = $session->contributors
                    ->map(fn ($contributor) => $contributor->user?->full_name)
                    ->filter()
                    ->unique()
                    ->implode(', ');
                $sessionTitle = $session->status === \App\Models\EmergencySession::STATUS_COMPLETED
                    ? 'Emergency Session Completed'
                    : 'Emergency Session Active';

                $items[] = $this->item(
                    $session->started_at ?? $session->created_at,
                    $sessionTitle,
                    'Emergency clinical session opened.',
                    optional($session->startedBy)->full_name,
                    optional($session->department)->name ?: 'Emergency',
                    'ER SESSION', 'bg-danger',
                    'emergency_session', $session->id,
                    array_filter([
                        'Main Doctor' => $session->mainDoctor?->full_name,
                        'Primary Nurse' => $session->primaryNurse?->full_name,
                        'Contributors' => $contributors ?: null,
                        'Medical Record' => $session->medical_record_id ? 'MR-'.str_pad((string) $session->medical_record_id, 5, '0', STR_PAD_LEFT) : null,
                    ])
                );
            }

            foreach ($case->bayAssignments ?? [] as $assignment) {
                $location = collect([
                    $assignment->ward?->name,
                    $assignment->bed?->bed_number,
                    $assignment->emergencyBay?->name,
                ])->filter()->implode(' / ');

                $items[] = $this->item(
                    $assignment->assigned_at ?? $assignment->created_at,
                    'Emergency Bay / Bed Assigned',
                    $location ?: 'Emergency location assigned.',
                    optional($assignment->assignedBy)->full_name,
                    'Emergency',
                    'LOCATION', 'bg-secondary',
                    'emergency_bay_assignment', $assignment->id,
                    array_filter(['Status' => $assignment->status])
                );
            }

            foreach ($case->notes ?? [] as $note) {
                $items[] = $this->item(
                    $note->created_at,
                    'Emergency '.ucwords(strtolower(str_replace('_', ' ', $note->note_type))),
                    $note->content,
                    optional($note->creator)->full_name,
                    'Emergency',
                    'ER NOTE', 'bg-danger',
                    'emergency_note', $note->id
                );
            }

            foreach ($case->logs ?? [] as $log) {
                $items[] = $this->item(
                    $log->created_at,
                    $log->title,
                    $log->description ?: str_replace('_', ' ', $log->action),
                    optional($log->performedBy)->full_name,
                    'Emergency',
                    'ER LOG', 'bg-danger',
                    'emergency_case_log', $log->id
                );
            }

            foreach ($case->clinicalTasks ?? [] as $task) {
                $items[] = $this->item(
                    $task->scheduled_at ?? $task->created_at,
                    'Emergency Task: '.$task->title,
                    $task->description ?: 'Emergency clinical task.',
                    optional($task->assignedUser)->full_name ?: optional($task->completedBy)->full_name,
                    'Emergency',
                    $task->status, 'bg-warning text-dark',
                    'clinical_task', $task->id,
                    array_filter([
                        'Priority' => $task->priority,
                        'Assigned Role' => $task->assigned_role,
                    ])
                );
            }

            foreach ($case->consumableUsages ?? [] as $usage) {
                $items[] = $this->item(
                    $usage->used_at ?? $usage->created_at,
                    'Emergency Consumable Used',
                    trim(($usage->product?->name ?: 'Consumable').' x '.$usage->quantity_used),
                    optional($usage->user)->full_name,
                    'Emergency',
                    'CONSUMABLE', 'bg-info text-dark',
                    'consumable_usage', $usage->id,
                    array_filter([
                        'Billable' => $usage->is_billable ? 'Yes' : 'No',
                        'Notes' => $usage->notes,
                    ])
                );
            }

            if ($case->disposition) {
                $items[] = $this->item(
                    $case->disposition_time ?? $case->updated_at,
                    'Emergency Disposition: '.str_replace('_', ' ', $case->disposition),
                    $case->disposition_notes ?: 'Emergency disposition recorded.',
                    optional($case->disposedBy)->full_name,
                    'Emergency',
                    'DISPOSITION', 'bg-dark',
                    'emergency_case', $case->id
                );
            }
        }

        // 3. Triage
        if ($visit->triage) {
            $t = $visit->triage;
            $vitalsDesc = collect([
                'BP' => ($t->blood_pressure_systolic && $t->blood_pressure_diastolic)
                    ? "{$t->blood_pressure_systolic}/{$t->blood_pressure_diastolic} mmHg" : null,
                'Pulse' => $t->heart_rate ? "{$t->heart_rate} bpm" : null,
                'Temp' => $t->temperature ? "{$t->temperature} °C" : null,
                'SPO₂' => $t->spo2 ? "{$t->spo2}%" : null,
                'RR' => $t->respiratory_rate ? "{$t->respiratory_rate} /min" : null,
                'Weight' => $t->weight ? "{$t->weight} kg" : null,
            ])->filter()->map(fn ($v, $k) => "$k: $v")->values()->implode(' · ');

            $items[] = $this->item(
                $t->triaged_at ?? $t->created_at,
                'Triage Completed',
                $vitalsDesc ?: 'Vitals recorded.',
                optional($t->triagedBy)->full_name,
                optional($t->department)->name,
                'TRIAGE', 'bg-info',
                'triage', $t->id,
                array_filter([
                    'Score' => $t->triage_score?->label() ?? null,
                    'Notes' => $t->notes ?: null,
                ])
            );
        }

        // 4. Vitals (additional readings beyond triage)
        foreach ($visit->vitals ?? [] as $vital) {
            $vDesc = collect([
                'BP' => ($vital->blood_pressure_systolic && $vital->blood_pressure_diastolic)
                    ? "{$vital->blood_pressure_systolic}/{$vital->blood_pressure_diastolic} mmHg" : null,
                'Pulse' => $vital->heart_rate ? "{$vital->heart_rate} bpm" : null,
                'Temp' => $vital->temperature ? "{$vital->temperature} °C" : null,
                'SPO₂' => $vital->spo2 ? "{$vital->spo2}%" : null,
                'RR' => $vital->respiratory_rate ? "{$vital->respiratory_rate} /min" : null,
            ])->filter()->map(fn ($v, $k) => "$k: $v")->values()->implode(' · ');

            $items[] = $this->item(
                $vital->created_at,
                'Vitals Recorded',
                $vDesc ?: 'Vital signs recorded.',
                optional($vital->recordedBy)->full_name,
                null,
                'VITALS', 'bg-info text-dark',
                'vital', $vital->id
            );
        }

        // 5. Consultation route status timeline
        foreach ($visit->consultationRoutes ?? [] as $route) {
            $deptName = optional($route->department)->name;
            $serviceNames = $route->routeServices
                ? $route->routeServices->map(fn ($routeService) => $routeService->service?->name)->filter()->values()
                : collect();
            if ($serviceNames->isEmpty() && $route->service) {
                $serviceNames = collect([$route->service->name]);
            }
            $serviceList = $serviceNames->implode(', ');
            $isEmergencyRoute = method_exists($route, 'isEmergencySession') && $route->isEmergencySession();
            $sessionName = $isEmergencyRoute
                ? 'Emergency Department Session'
                : ($deptName ? "{$deptName} Department Session" : 'Consultation Department Session');
            $sessionBadge = $isEmergencyRoute ? 'ER SESSION' : 'SESSION';
            $sessionBadgeClass = $isEmergencyRoute ? 'bg-danger' : 'bg-primary';

            $items[] = $this->item(
                $route->created_at,
                "{$sessionName} Routed",
                trim(($deptName ?: 'Department').' routed for consultation.'.($serviceList ? " Services: {$serviceList}." : '')),
                optional($route->routedBy)->full_name,
                $deptName,
                $sessionBadge, $sessionBadgeClass,
                'consultation_route', $route->id,
                array_filter([
                    'Status' => $route->status,
                    'Doctor' => optional($route->doctor ?? $route->mainDoctor)->full_name,
                    'Primary Nurse' => optional($route->primaryNurse)->full_name,
                    'Linked Services' => $serviceList,
                ])
            );

            foreach ($route->logs ?? [] as $log) {
                $items[] = $this->item(
                    $log->created_at,
                    "{$sessionName} ".ucfirst(str_replace('_', ' ', $log->action)),
                    $log->notes ?: "Consultation session moved to {$log->to_status}.",
                    optional($log->performedBy)->full_name,
                    $deptName,
                    $sessionBadge, $sessionBadgeClass,
                    'consultation_route_log', $log->id,
                    array_filter([
                        'From' => $log->from_status,
                        'To' => $log->to_status,
                    ])
                );
            }
        }

        foreach ($visit->appointments ?? [] as $appointment) {
            $appointmentDate = $appointment->appointment_date?->format('d M Y');
            $appointmentTime = $appointment->start_time ? Carbon::parse($appointment->start_time)->format('h:i A') : null;
            $departmentName = $appointment->department?->name ?? $appointment->consultationRoute?->department?->name;
            $serviceList = $appointment->services?->map(fn ($service) => $service->name)->filter()->implode(', ');
            $status = $appointment->status;
            $title = $status === AppointmentStatus::CANCELLED
                ? 'Next Appointment Cancelled'
                : 'Next Appointment Set';
            $badgeClass = match ($status) {
                AppointmentStatus::CANCELLED => 'bg-danger',
                AppointmentStatus::COMPLETED => 'bg-success',
                AppointmentStatus::NO_SHOW => 'bg-dark',
                default => 'bg-info',
            };

            $items[] = $this->item(
                $appointment->created_at,
                $title,
                'Next appointment set: '.collect([$appointmentDate, $appointmentTime, $departmentName])->filter()->implode(', '),
                optional($appointment->createdBy)->full_name,
                $departmentName,
                'FOLLOW-UP',
                $badgeClass,
                'appointment',
                $appointment->id,
                array_filter([
                    'Appointment No.' => $appointment->appointment_number,
                    'Date' => $appointmentDate,
                    'Time' => $appointmentTime,
                    'Department' => $departmentName,
                    'Service' => $serviceList,
                    'Doctor' => $appointment->doctor?->full_name ? 'Dr. '.$appointment->doctor->full_name : null,
                    'Priority' => $appointment->priority ? $this->formatStatus($appointment->priority) : null,
                    'Status' => $status?->label(),
                    'Reason' => $appointment->reason,
                ])
            );
        }

        // 6. Medical records / consultation sessions
        $records = $visit->medicalRecords && $visit->medicalRecords->isNotEmpty()
            ? $visit->medicalRecords
            : collect($visit->medicalRecord ? [$visit->medicalRecord] : []);

        foreach ($records as $mr) {
            $sessionDepartment = optional($mr->department ?? $mr->consultationRoute?->department)->name;
            $routeServices = $mr->consultationRoute?->routeServices
                ? $mr->consultationRoute->routeServices->map(fn ($routeService) => $routeService->service?->name)->filter()->values()
                : collect();
            $sessionService = optional($mr->service ?? $mr->consultationRoute?->service)->name;
            $serviceList = $routeServices->isNotEmpty() ? $routeServices->implode(', ') : $sessionService;
            $sessionLabel = $sessionDepartment ?: 'Consultation Department';
            $sessionTitle = $sessionDepartment ? "{$sessionLabel} Session Started" : 'Consultation Started';

            $items[] = $this->item(
                $mr->created_at,
                $sessionTitle,
                ($sessionLabel ? "{$sessionLabel}: " : '').'Medical record opened by '.(optional($mr->doctor)->full_name ?: 'physician').'.',
                optional($mr->doctor)->full_name,
                $sessionDepartment,
                'CONSULTATION', 'bg-success',
                'medical_record', $mr->id,
                array_filter([
                    'Department' => $sessionDepartment,
                    'Linked Services' => $serviceList,
                    'Medical Record' => 'MR-'.str_pad((string) $mr->id, 5, '0', STR_PAD_LEFT),
                ])
            );

            // Complaints
            foreach ($mr->complaints ?? [] as $complaint) {
                $duration = trim(($complaint->duration ?? '').' '.($complaint->duration_unit ?? ''));
                $items[] = $this->item(
                    $complaint->created_at,
                    'Complaint Recorded',
                    $complaint->complaint ?? $complaint->description ?? 'Complaint noted.',
                    optional($complaint->creator ?? $complaint->createdBy ?? null)->full_name ?? null,
                    $sessionDepartment,
                    'COMPLAINT', 'bg-warning text-dark',
                    'complaint', $complaint->id,
                    array_filter([
                        'Complaint' => $complaint->complaintCatalogue?->name,
                        'Category' => $complaint->complaintCatalogue?->category,
                        'Duration' => $duration,
                        'Severity' => $complaint->severity ? ucfirst($complaint->severity) : null,
                        'Notes' => $complaint->notes,
                        'Linked Services' => $serviceList,
                        'Source Pattern' => $complaint->sourcePattern?->name,
                    ])
                );
            }

            foreach ($mr->historiesOfPresentingComplaint ?? [] as $hopc) {
                $items[] = $this->item(
                    $hopc->created_at,
                    'History of Presenting Complaint Recorded',
                    $hopc->content ?: 'History of presenting complaint recorded.',
                    optional($hopc->creator ?? null)->full_name,
                    $sessionDepartment,
                    'HOPC', 'bg-warning text-dark',
                    'history_of_presenting_complaint', $hopc->id,
                    array_filter([
                        'Linked Services' => $serviceList,
                        'Source Pattern' => $hopc->sourcePattern?->name,
                        'Severity' => $hopc->severity,
                    ])
                );
            }

            foreach ($mr->physicalExaminations ?? [] as $exam) {
                $items[] = $this->item(
                    $exam->created_at,
                    'Examination Recorded',
                    $exam->findings ?: 'Physical examination findings recorded.',
                    optional($exam->creator ?? null)->full_name,
                    $sessionDepartment,
                    'EXAM', 'bg-secondary',
                    'physical_examination', $exam->id,
                    array_filter([
                        'Linked Services' => $serviceList,
                        'Source Pattern' => $exam->sourcePattern?->name,
                    ])
                );
            }

            // Diagnoses
            foreach ($mr->diagnoses ?? [] as $diag) {
                $badge = $diag->is_primary ? 'PRIMARY DX' : 'DIAGNOSIS';
                $badgeCls = $diag->is_primary ? 'bg-danger' : 'bg-secondary';
                $title = $diag->is_primary ? 'Primary Diagnosis Recorded' : 'Diagnosis Recorded';
                $desc = trim(($diag->icd_code ? "[{$diag->icd_code}] " : '').($diag->description ?? ''));
                $items[] = $this->item(
                    $diag->created_at,
                    $title,
                    $desc ?: 'Diagnosis noted.',
                    optional($diag->creator ?? null)->full_name,
                    $sessionDepartment,
                    $badge, $badgeCls,
                    'diagnosis', $diag->id,
                    array_filter(['Linked Services' => $serviceList, 'Type' => $diag->type ?: null, 'Notes' => $diag->notes ?: null, 'Source Pattern' => $diag->sourcePattern?->name])
                );
            }

            // Treatments / clinical notes
            foreach ($mr->treatments ?? [] as $treatment) {
                $items[] = $this->item(
                    $treatment->created_at,
                    'Clinical Note / Treatment',
                    $treatment->description ?? 'Treatment note recorded.',
                    optional($treatment->creator ?? null)->full_name,
                    $sessionDepartment,
                    'TREATMENT', 'bg-success',
                    'treatment', $treatment->id,
                    array_filter(['Linked Services' => $serviceList, 'Type' => $treatment->type ?: null, 'Source Pattern' => $treatment->sourcePattern?->name])
                );
            }

            foreach ($mr->tasks ?? [] as $task) {
                $items[] = $this->item(
                    $task->created_at,
                    'Task / Follow-up Added',
                    $task->title.($task->description ? ': '.$task->description : ''),
                    optional($task->creator ?? null)->full_name,
                    $sessionDepartment,
                    'TASK', 'bg-dark',
                    'consultation_task', $task->id,
                    array_filter([
                        'Assigned To' => $task->assignedUser?->full_name,
                        'Status' => $task->status,
                        'Source Pattern' => $task->sourcePattern?->name,
                    ])
                );
            }
        }

        // 7. Lab requests & results
        foreach ($visit->labRequests ?? [] as $lr) {
            $testNames = $lr->items
                ->map(fn ($i) => $i->display_name ?? $i->name)
                ->filter()
                ->implode(', ');

            $items[] = $this->item(
                $lr->created_at,
                'Investigation Requested',
                $testNames ? "Requested: {$testNames}" : 'Lab investigation requested.',
                optional($lr->requestedBy)->full_name,
                optional($lr->department)->name,
                'LAB', 'bg-info',
                'lab_request', $lr->id,
                array_filter([
                    'Urgency' => $lr->urgency ? strtoupper($lr->urgency) : null,
                    'Clinical Info' => $lr->clinical_info ?: null,
                ])
            );

            // Results
            foreach ($lr->results ?? [] as $result) {
                $items[] = $this->item(
                    $result->created_at,
                    'Lab Result Entered',
                    "Results entered for request {$lr->request_number}.",
                    optional($result->enteredBy ?? null)->full_name ?? null,
                    null,
                    'LAB RESULT', 'bg-success',
                    'lab_result', $result->id
                );
            }
        }

        // 7. Prescriptions
        foreach ($visit->prescriptions ?? [] as $rx) {
            $drugNames = $rx->items
                ->map(fn ($i) => $i->drug_name ?? ($i->drug->name ?? null))
                ->filter()
                ->implode(', ');

            $items[] = $this->item(
                $rx->created_at,
                'Prescription Created',
                $drugNames ? "Prescribed: {$drugNames}" : 'Prescription created.',
                optional($rx->doctor)->full_name,
                null,
                'PRESCRIPTION', 'bg-primary',
                'prescription', $rx->id,
                array_filter(['Prescription No.' => $rx->prescription_number ?: null])
            );

            // Dispensing records
            foreach ($rx->dispensingRecords ?? [] as $dr) {
                $items[] = $this->item(
                    $dr->dispensed_at ?? $dr->created_at,
                    'Drug Dispensed',
                    'Dispensed '.($dr->quantity_dispensed ?? '?').' unit(s).',
                    optional($dr->dispensedBy)->full_name,
                    null,
                    'PHARMACY', 'bg-success',
                    'dispensing_record', $dr->id
                );
            }
        }

        // 7b. Medication administration record
        foreach ($visit->medicationOrders ?? [] as $order) {
            $items[] = $this->item(
                $order->created_at,
                'Medication Order Created',
                trim(($order->display_name ?? 'Medication').' '.$order->dose.' '.$order->frequency_code),
                optional($order->prescriber)->full_name,
                null,
                'MED ORDER', 'bg-primary',
                'medication_order', $order->id,
                array_filter([
                    'Route' => $order->route ?: null,
                    'Status' => $order->status ?: null,
                    'Total Doses' => $order->total_doses ?: null,
                    'Dispensed' => $order->quantity_dispensed ?: null,
                ])
            );

            foreach ($order->schedules ?? [] as $schedule) {
                $items[] = $this->item(
                    $schedule->scheduled_at,
                    'Medication Dose Scheduled',
                    sprintf('Dose %d/%d scheduled for %s.', $schedule->sequence_number, max(1, (int) $order->total_doses), $order->display_name),
                    null,
                    null,
                    $schedule->clinicalTask?->status ?? $schedule->status,
                    'bg-secondary',
                    'medication_schedule', $schedule->id,
                    array_filter([
                        'Dose' => trim(($schedule->dose ?: $order->dose).' '.($schedule->dose_unit ?: $order->dose_unit)),
                        'Route' => $schedule->route ?: $order->route ?: null,
                    ])
                );
            }

            foreach ($order->administrations ?? [] as $administration) {
                $items[] = $this->item(
                    $administration->administered_at ?? $administration->created_at,
                    'Medication Dose '.$administration->status,
                    sprintf(
                        '%s dose recorded for %s%s',
                        ucfirst(strtolower(str_replace('_', ' ', $administration->status))),
                        $order->display_name,
                        $administration->reaction ? '. Reaction: '.$administration->reaction : '.'
                    ),
                    optional($administration->administeredBy)->full_name,
                    null,
                    'MAR', 'bg-success',
                    'medication_administration', $administration->id,
                    array_filter([
                        'Scheduled' => optional($administration->scheduled_at)->format('d M Y H:i'),
                        'Dose Given' => $administration->dose_given ?: null,
                        'Route' => $administration->route ?: null,
                        'Source Stock' => $administration->source_stock_type ?: null,
                        'Reason' => $administration->reason_not_given ?: null,
                    ])
                );
            }
        }

        // 8. Procedure requests
        foreach ($visit->procedureRequests ?? [] as $pr) {
            $svcName = optional($pr->service)->name ?: 'Procedure';

            $items[] = $this->item(
                $pr->requested_at ?? $pr->created_at,
                'Procedure Requested',
                "Procedure requested: {$svcName}",
                optional($pr->requestingDoctor)->full_name,
                optional($pr->department)->name,
                'PROCEDURE', 'bg-warning text-dark',
                'procedure_request', $pr->id,
                array_filter(['Priority' => $pr->priority ?: null, 'Indication' => $pr->indication ?: null])
            );

            if ($pr->accepted_at) {
                $items[] = $this->item(
                    $pr->accepted_at,
                    'Procedure Accepted',
                    "{$svcName} accepted.",
                    optional($pr->acceptedBy)->full_name,
                    null,
                    'ACCEPTED', 'bg-success',
                    'procedure_request', $pr->id
                );
            }

            if ($pr->anaesthesiaNote) {
                $an = $pr->anaesthesiaNote;
                $items[] = $this->item(
                    $an->start_time ?? $an->created_at,
                    'Anaesthesia Note',
                    'Anaesthesia note recorded for '.$svcName.'.',
                    optional($an->anaesthetist)->full_name,
                    null,
                    'ANAESTHESIA', 'bg-danger',
                    'anaesthesia_note', $an->id,
                    array_filter(['Type' => $an->anaesthesia_type ?: null])
                );
            }

            if ($pr->operativeNote) {
                $on = $pr->operativeNote;
                $items[] = $this->item(
                    $on->created_at,
                    'Operative Note',
                    'Operative note recorded for '.$svcName.'.',
                    null,
                    null,
                    'OPERATIVE', 'bg-dark',
                    'operative_note', $on->id
                );
            }

            if ($pr->postOpNote) {
                $pon = $pr->postOpNote;
                $items[] = $this->item(
                    $pon->created_at,
                    'Post-Op Note',
                    'Post-operative note recorded for '.$svcName.'.',
                    null,
                    null,
                    'POST-OP', 'bg-secondary',
                    'post_op_note', $pon->id
                );
            }

            if ($pr->completed_at) {
                $items[] = $this->item(
                    $pr->completed_at,
                    'Procedure Completed',
                    "{$svcName} completed.",
                    optional($pr->completedBy)->full_name,
                    null,
                    'COMPLETED', 'bg-success',
                    'procedure_request', $pr->id
                );
            }
        }

        // 9. Billing — invoices and payments
        foreach ($visit->serviceRenderings ?? [] as $rendering) {
            $serviceName = $rendering->service?->name ?? $rendering->invoiceItem?->description ?? 'Service';
            $details = array_filter([
                'Invoice' => $rendering->invoiceItem?->invoice?->invoice_number,
                'Billing Status' => $rendering->invoiceItem?->invoice?->status?->label() ?? null,
                'Payment Status' => $rendering->invoiceItem?->payment_status ? ucwords(str_replace('_', ' ', $rendering->invoiceItem->payment_status)) : null,
                'Patient Payable' => $rendering->invoiceItem ? 'GHS '.number_format((float) $rendering->invoiceItem->patient_payable, 2) : null,
                'Notes' => $rendering->notes,
                'Result' => $rendering->result_summary,
                'Reason' => $rendering->reason_not_rendered,
            ]);

            $items[] = $this->item(
                $rendering->created_at,
                "{$serviceName} Awaiting Rendering",
                'Billed service queued for department fulfilment.',
                optional($rendering->createdBy)->full_name,
                optional($rendering->department)->name,
                'SERVICE', 'bg-warning text-dark',
                'service_rendering', $rendering->id,
                $details
            );

            if ($rendering->started_at) {
                $items[] = $this->item(
                    $rendering->started_at,
                    "{$serviceName} Rendering Started",
                    $rendering->notes ?: 'Service rendering started.',
                    optional($rendering->startedBy)->full_name,
                    optional($rendering->department)->name,
                    'IN PROGRESS', 'bg-info',
                    'service_rendering', $rendering->id,
                    $details
                );
            }

            if ($rendering->status === ServiceRendering::STATUS_RENDERED) {
                $items[] = $this->item(
                    $rendering->rendered_at ?? $rendering->updated_at,
                    "{$serviceName} Rendered",
                    $rendering->result_summary ?: 'Service rendered.',
                    optional($rendering->renderedBy)->full_name,
                    optional($rendering->department)->name,
                    'RENDERED', 'bg-success',
                    'service_rendering', $rendering->id,
                    $details
                );
            } elseif (in_array($rendering->status, [ServiceRendering::STATUS_NOT_RENDERED, ServiceRendering::STATUS_CANCELLED], true)) {
                $items[] = $this->item(
                    $rendering->updated_at,
                    "{$serviceName} ".ucwords(strtolower(str_replace('_', ' ', $rendering->status))),
                    $rendering->reason_not_rendered ?: $rendering->notes ?: 'Service rendering closed.',
                    optional($rendering->updatedBy)->full_name,
                    optional($rendering->department)->name,
                    str_replace('_', ' ', $rendering->status), 'bg-secondary',
                    'service_rendering', $rendering->id,
                    $details
                );
            }
        }

        foreach ($visit->invoices ?? [] as $invoice) {
            $items[] = $this->item(
                $invoice->created_at,
                'Invoice Created',
                "Invoice {$invoice->invoice_number} created.",
                optional($invoice->createdBy ?? null)->full_name ?? null,
                null,
                'BILLING', 'bg-warning text-dark',
                'invoice', $invoice->id,
                array_filter([
                    'Total' => $invoice->total_amount ? '₵'.number_format((float) $invoice->total_amount, 2) : null,
                    'Status' => $invoice->status?->label() ?? null,
                ])
            );

            foreach ($invoice->payments ?? [] as $payment) {
                $method = $payment->payment_method?->label() ?? $payment->payment_method ?? '';
                $items[] = $this->item(
                    $payment->paid_at ?? $payment->created_at,
                    'Payment Received',
                    '₵'.number_format((float) $payment->amount, 2).($method ? " via {$method}" : '').'.',
                    optional($payment->receivedBy)->full_name,
                    null,
                    'PAYMENT', 'bg-success',
                    'payment', $payment->id,
                    array_filter(['Ref' => $payment->reference_number ?: null])
                );
            }
        }

        foreach ($visit->bloodRequests ?? [] as $bloodRequest) {
            $items[] = $this->item(
                $bloodRequest->requested_at ?? $bloodRequest->created_at,
                'Blood Requested',
                "{$bloodRequest->blood_group} {$bloodRequest->component_type} x{$bloodRequest->units_requested} requested.",
                optional($bloodRequest->requestedBy)->full_name,
                optional($bloodRequest->department)->name,
                'BLOOD', 'bg-danger',
                'blood_request', $bloodRequest->id,
                array_filter([
                    'Request Number' => $bloodRequest->request_number,
                    'Priority' => $bloodRequest->priority,
                    'Status' => $bloodRequest->status,
                    'Indication' => $bloodRequest->recipient?->clinical_indication ?: $bloodRequest->indication,
                    'Recipient Group' => $bloodRequest->recipient?->blood_group ?: $bloodRequest->blood_group,
                    'Diagnosis' => $bloodRequest->recipient?->diagnosis ?: $bloodRequest->diagnosis,
                    'Hb Level' => $bloodRequest->recipient?->hemoglobin_level ?: $bloodRequest->hb_level,
                    'Prior Reaction' => $bloodRequest->recipient?->previous_transfusion_reaction ? 'Yes' : null,
                    'Special Requirements' => $bloodRequest->recipient?->special_requirements,
                ])
            );

            if ($bloodRequest->approved_at) {
                $items[] = $this->item(
                    $bloodRequest->approved_at,
                    'Blood Request Approved',
                    "{$bloodRequest->request_number} approved for crossmatch and issue.",
                    optional($bloodRequest->approvedBy)->full_name,
                    optional($bloodRequest->department)->name,
                    'APPROVED', 'bg-success',
                    'blood_request', $bloodRequest->id
                );
            }

            foreach ($bloodRequest->crossmatches ?? [] as $crossmatch) {
                $items[] = $this->item(
                    $crossmatch->performed_at ?? $crossmatch->created_at,
                    'Blood Crossmatch '.$this->formatStatus($crossmatch->result),
                    'Unit '.($crossmatch->unit?->unit_number ?? $crossmatch->blood_unit_id).' crossmatched for request '.$bloodRequest->request_number.'.',
                    optional($crossmatch->performedBy)->full_name,
                    'Blood Bank',
                    $crossmatch->result, $crossmatch->result === 'COMPATIBLE' ? 'bg-success' : 'bg-danger',
                    'blood_crossmatch', $crossmatch->id,
                    array_filter([
                        'Unit' => $crossmatch->unit?->unit_number,
                        'Recipient Group' => $crossmatch->recipient_blood_group,
                        'Donor Group' => $crossmatch->donor_blood_group ?? $crossmatch->unit?->blood_group,
                        'Component' => $crossmatch->component_type,
                        'Compatibility' => $crossmatch->compatibility_status ? $this->formatStatus($crossmatch->compatibility_status) : null,
                        'Verified By' => $crossmatch->verifiedBy?->full_name,
                        'Method' => $crossmatch->method,
                        'Notes' => $crossmatch->notes,
                    ])
                );
            }

            foreach ($bloodRequest->issues ?? [] as $issue) {
                $items[] = $this->item(
                    $issue->issued_at,
                    'Blood Unit Issued',
                    'Unit '.($issue->unit?->unit_number ?? $issue->blood_unit_id).' issued for transfusion.',
                    optional($issue->issuedBy)->full_name,
                    'Blood Bank',
                    $issue->is_emergency_release ? 'EMERGENCY RELEASE' : 'ISSUED', $issue->is_emergency_release ? 'bg-dark' : 'bg-danger',
                    'blood_issue', $issue->id,
                    array_filter([
                        'Issue Number' => $issue->issue_number,
                        'Unit' => $issue->unit?->unit_number,
                        'Received By' => $issue->receivedBy?->full_name ?? $issue->received_by_name,
                        'Status' => $issue->transfusion_status,
                        'Compatibility' => $issue->compatibility_status ? $this->formatStatus($issue->compatibility_status) : null,
                        'Emergency Release' => $issue->is_emergency_release ? $this->formatStatus((string) $issue->emergency_release_type) : null,
                        'Release Reason' => $issue->emergency_release_reason,
                    ])
                );

                if ($issue->transfused_at) {
                    $items[] = $this->item(
                        $issue->transfused_at,
                        'Blood Transfusion Recorded',
                        $issue->reaction_notes ? 'Transfusion recorded with reaction notes.' : 'Blood transfusion recorded.',
                        optional($issue->transfusedBy)->full_name,
                        'Blood Bank',
                        $issue->transfusion_status, $issue->reaction_occurred ? 'bg-warning text-dark' : 'bg-success',
                        'blood_issue', $issue->id,
                        array_filter([
                            'Issue Number' => $issue->issue_number,
                            'Unit' => $issue->unit?->unit_number,
                            'Outcome' => $issue->outcome ? $this->formatStatus($issue->outcome) : null,
                            'Reaction' => $issue->reaction_occurred ? $this->formatStatus((string) ($issue->reaction_type ?: 'Reported')) : null,
                            'Reaction Notes' => $issue->reaction_notes,
                            'Witnessed By' => $issue->witnessedBy?->full_name,
                            'Notes' => $issue->notes,
                        ])
                    );
                }
            }
        }

        // 10. Admission
        if ($admission = $visit->admission) {
            $items[] = $this->item(
                $admission->admission_date ?? $admission->created_at,
                'Patient Admitted',
                'Patient admitted'.($admission->bed?->ward ? ' to ward '.$admission->bed->ward->name : '').'.',
                optional($admission->admittedByUser ?? null)->full_name ?? null,
                $admission->bed?->ward?->name,
                'ADMITTED', 'bg-primary',
                'admission', $admission->id,
                array_filter([
                    'Admission No.' => $admission->admission_number ?: null,
                    'Diagnosis' => $admission->admitting_diagnosis ?: null,
                    'Bed' => $admission->bed?->name ?? null,
                ])
            );

            if ($admission->actual_discharge_date) {
                $items[] = $this->item(
                    $admission->actual_discharge_date,
                    'Patient Discharged',
                    $admission->discharge_summary ?: 'Patient discharged.',
                    optional($admission->dischargedByUser ?? null)->full_name ?? null,
                    null,
                    'DISCHARGED', 'bg-secondary',
                    'admission', $admission->id
                );
            }
        }

        // 11. Visit completed / checked out
        if ($visit->checked_out_at) {
            $items[] = $this->item(
                $visit->checked_out_at,
                'Visit Completed',
                'Patient checked out and visit closed.',
                null,
                null,
                'COMPLETED', 'bg-success',
                'visit', $visit->id
            );
        }

        // Sort chronologically
        usort($items, function ($a, $b) {
            $ta = $a['datetime'] instanceof Carbon ? $a['datetime']->timestamp : (int) strtotime((string) $a['datetime']);
            $tb = $b['datetime'] instanceof Carbon ? $b['datetime']->timestamp : (int) strtotime((string) $b['datetime']);

            return $ta <=> $tb;
        });

        return $items;
    }

    /* ──────────────────────────────────────────────────────────────
     |  Summary builder
     | ────────────────────────────────────────────────────────────*/

    private function buildSummary(Visit $visit): array
    {
        $records = $visit->medicalRecords && $visit->medicalRecords->isNotEmpty()
            ? $visit->medicalRecords
            : collect($visit->medicalRecord ? [$visit->medicalRecord] : []);

        $diagnoses = $records->flatMap(fn ($record) => $record->diagnoses ?? collect());
        $complaints = $records->flatMap(fn ($record) => $record->complaints ?? collect());
        $primaryDx = $diagnoses->firstWhere('is_primary', true);
        $chiefComplaint = $visit->chief_complaint
            ?: $complaints->first()?->complaint
            ?: $complaints->first()?->description;
        $nextAppointment = $visit->appointments
            ->filter(fn ($appointment) => ! in_array($appointment->status, [
                AppointmentStatus::COMPLETED,
                AppointmentStatus::CANCELLED,
                AppointmentStatus::NO_SHOW,
            ], true))
            ->sortBy(fn ($appointment) => trim(($appointment->appointment_date?->format('Y-m-d') ?? '9999-12-31').' '.($appointment->start_time ?: '23:59')))
            ->first();
        $nextAppointmentSummary = null;

        if ($nextAppointment) {
            $nextAppointmentSummary = [
                'date' => $nextAppointment->appointment_date?->format('d M Y'),
                'time' => $nextAppointment->start_time ? Carbon::parse($nextAppointment->start_time)->format('h:i A') : null,
                'department' => $nextAppointment->department?->name,
                'service' => $nextAppointment->services?->map(fn ($service) => $service->name)->filter()->implode(', '),
                'doctor' => $nextAppointment->doctor?->full_name ? 'Dr. '.$nextAppointment->doctor->full_name : null,
                'reason' => $nextAppointment->reason,
                'priority' => $nextAppointment->priority ? $this->formatStatus($nextAppointment->priority) : null,
                'status' => $nextAppointment->status?->label(),
            ];
        }

        return [
            'chief_complaint' => $chiefComplaint ?: '—',
            'primary_diagnosis' => $primaryDx
                ? (($primaryDx->icd_code ? "[{$primaryDx->icd_code}] " : '').($primaryDx->description ?? ''))
                : '—',
            'diagnoses_count' => $diagnoses->count(),
            'prescriptions_count' => $visit->prescriptions->count(),
            'investigations_count' => $visit->labRequests->count(),
            'procedures_count' => $visit->procedureRequests->count(),
            'service_renderings_count' => $visit->serviceRenderings->count(),
            'pending_service_renderings_count' => $visit->serviceRenderings
                ->whereIn('status', ServiceRendering::ACTIVE_STATUSES)
                ->count(),
            'blood_requests_count' => $visit->bloodRequests->count(),
            'blood_issues_count' => $visit->bloodIssues->count(),
            'has_admission' => $visit->admission !== null,
            'has_emergency_case' => $visit->emergencyCase !== null,
            'emergency_number' => $visit->emergencyCase?->emergency_number,
            'emergency_session_status' => $visit->emergencyCase?->activeEmergencySession?->status,
            'emergency_disposition' => $visit->emergencyCase?->disposition,
            'billing_status' => $visit->invoices->isEmpty()
                ? 'No invoice'
                : ($visit->invoices->last()?->status?->label() ?? 'Unknown'),
            'total_billed' => $visit->invoices->sum('total_amount'),
            'total_paid' => $visit->invoices->sum('amount_paid'),
            'next_appointment' => $nextAppointmentSummary,
        ];
    }

    /* ──────────────────────────────────────────────────────────────
     |  Helpers
     | ────────────────────────────────────────────────────────────*/

    private function item(
        $datetime,
        string $title,
        string $description,
        ?string $enteredBy = null,
        ?string $department = null,
        ?string $badge = null,
        string $badgeClass = 'bg-secondary',
        ?string $sourceType = null,
        $sourceId = null,
        array $details = []
    ): array {
        $dt = null;
        if ($datetime instanceof Carbon) {
            $dt = $datetime;
        } elseif ($datetime) {
            try {
                $dt = Carbon::parse($datetime);
            } catch (\Throwable) {
            }
        }

        return [
            'datetime' => $dt,
            'date_label' => $dt?->format('d M Y') ?? '—',
            'time_label' => $dt?->format('h:i A') ?? '',
            'title' => $title,
            'description' => $description,
            'entered_by' => $enteredBy ?: 'System',
            'department' => $department,
            'badge' => $badge,
            'badge_class' => $badgeClass,
            'details' => $details,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
        ];
    }

    private function formatStatus($status): string
    {
        if ($status === null) {
            return '—';
        }
        if (is_object($status) && method_exists($status, 'label')) {
            return $status->label();
        }

        return ucwords(str_replace(['_', '-'], ' ', (string) $status));
    }
}
