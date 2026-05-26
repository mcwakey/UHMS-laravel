<?php

namespace App\Services;

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
            'visitInsurance.insuranceProvider',
            'statusLogs.changedBy',
            'triage.triagedBy',
            'triage.department',
            'vitals.recordedBy',
            'medicalRecord.doctor',
            'medicalRecord.complaints.creator',
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
            'consultationRoutes.routedBy',
            'consultationRoutes.logs.performedBy',
            'labRequests.requestedBy',
            'labRequests.department',
            'labRequests.items',
            'labRequests.results',
            'prescriptions.doctor',
            'prescriptions.dispensingRecords.dispensedBy',
            'prescriptions.items',
            'invoices.items',
            'invoices.payments.receivedBy',
            'admission.admittedByUser',
            'admission.dischargedByUser',
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
            $sessionName = $deptName ? "{$deptName} Department Session" : 'Consultation Department Session';

            $items[] = $this->item(
                $route->created_at,
                "{$sessionName} Routed",
                trim(($deptName ?: 'Department').' routed for consultation.'.($serviceList ? " Services: {$serviceList}." : '')),
                optional($route->routedBy)->full_name,
                $deptName,
                'SESSION', 'bg-primary',
                'consultation_route', $route->id,
                array_filter([
                    'Status' => $route->status,
                    'Doctor' => optional($route->doctor)->full_name,
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
                    'SESSION', 'bg-primary',
                    'consultation_route_log', $log->id,
                    array_filter([
                        'From' => $log->from_status,
                        'To' => $log->to_status,
                    ])
                );
            }
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
                $items[] = $this->item(
                    $complaint->created_at,
                    'Complaint Recorded',
                    $complaint->complaint ?? $complaint->description ?? 'Complaint noted.',
                    optional($complaint->creator ?? $complaint->createdBy ?? null)->full_name ?? null,
                    $sessionDepartment,
                    'COMPLAINT', 'bg-warning text-dark',
                    'complaint', $complaint->id,
                    array_filter(['Linked Services' => $serviceList, 'Source Pattern' => $complaint->sourcePattern?->name])
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

        return [
            'chief_complaint' => $chiefComplaint ?: '—',
            'primary_diagnosis' => $primaryDx
                ? (($primaryDx->icd_code ? "[{$primaryDx->icd_code}] " : '').($primaryDx->description ?? ''))
                : '—',
            'diagnoses_count' => $diagnoses->count(),
            'prescriptions_count' => $visit->prescriptions->count(),
            'investigations_count' => $visit->labRequests->count(),
            'procedures_count' => $visit->procedureRequests->count(),
            'has_admission' => $visit->admission !== null,
            'billing_status' => $visit->invoices->isEmpty()
                ? 'No invoice'
                : ($visit->invoices->last()?->status?->label() ?? 'Unknown'),
            'total_billed' => $visit->invoices->sum('total_amount'),
            'total_paid' => $visit->invoices->sum('amount_paid'),
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
