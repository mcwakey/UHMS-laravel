<?php

namespace App\Services;

use App\Models\MedicalRecord;
use App\Models\Visit;

class ConsultationSummaryService
{
    public function forRecord(?MedicalRecord $record): array
    {
        if (! $record) {
            return $this->emptySummary();
        }

        $record->loadMissing([
            'department',
            'consultationRoute.doctor',
            'consultationRoute.mainDoctor',
            'consultationRoute.contributors.user',
            'consultationRoute.routeServices.service',
            'consultationRoute.emergencyCase.triagedBy',
            'consultationRoute.emergencyCase.notes.creator',
            'consultationRoute.emergencyCase.vitals.recordedBy',
            'consultationRoute.emergencyCase.medicationOrders.prescriber',
            'consultationRoute.emergencyCase.medicationOrders.frequency',
            'consultationRoute.emergencyCase.labRequests.items',
            'consultationRoute.emergencyCase.labRequests.requestedBy',
            'consultationRoute.emergencyCase.procedureRequests.service',
            'consultationRoute.emergencyCase.procedureRequests.requestingDoctor',
            'consultationRoute.emergencyCase.consumableUsages.product',
            'consultationRoute.emergencyCase.consumableUsages.user',
            'complaints.creator',
            'complaints.updater',
            'complaints.sourcePattern',
            'historiesOfPresentingComplaint.creator',
            'historiesOfPresentingComplaint.updater',
            'historiesOfPresentingComplaint.sourcePattern',
            'physicalExaminations.creator',
            'physicalExaminations.updater',
            'physicalExaminations.sourcePattern',
            'diagnoses.creator',
            'diagnoses.updater',
            'diagnoses.sourcePattern',
            'investigations.creator',
            'investigations.updater',
            'investigations.sourcePattern',
            'treatments.creator',
            'treatments.updater',
            'treatments.sourcePattern',
            'prescriptions.creator',
            'prescriptions.updater',
            'prescriptions.doctor',
            'prescriptions.sourcePattern',
            'prescriptions.items',
            'tasks.creator',
            'tasks.assignedUser',
            'tasks.completedBy',
            'tasks.sourcePattern',
        ]);

        $summary = $this->emptySummary();
        $summary['main_doctor'] = $record->consultationRoute?->doctor?->full_name
            ?? $record->consultationRoute?->mainDoctor?->full_name;
        $summary['department'] = $record->department?->name ?: $record->consultationRoute?->department?->name;
        $summary['services'] = $record->consultationRoute?->routeServices
            ? $record->consultationRoute->routeServices->map(fn ($routeService) => $routeService->service?->name)->filter()->values()->all()
            : [];
        $summary['contributors'] = $record->consultationRoute?->contributors
            ? $record->consultationRoute->contributors->map(fn ($contributor) => $contributor->user?->full_name)->filter()->unique()->values()->all()
            : [];

        foreach ($record->complaints ?? [] as $entry) {
            $summary['sections']['complaints'][] = $this->entry('Complaint', $entry->description, $entry, [
                'Duration' => $entry->duration,
                'Severity' => $entry->severity,
            ]);
        }

        foreach ($record->historiesOfPresentingComplaint ?? [] as $entry) {
            $summary['sections']['history_of_presenting_complaint'][] = $this->entry('History of Presenting Complaint', $entry->content, $entry, [
                'Complaint' => $entry->complaint?->description,
                'Onset' => $entry->onset,
                'Duration' => $entry->duration,
                'Severity' => $entry->severity,
            ]);
        }

        foreach ($record->physicalExaminations ?? [] as $entry) {
            $summary['sections']['examination'][] = $this->entry('Examination', $entry->findings, $entry, [
                'General' => $entry->general_examination,
                'Systemic' => $entry->systemic_examination,
                'Specialty' => $entry->specialty_examination,
            ]);
        }

        foreach ($record->diagnoses ?? [] as $entry) {
            $summary['sections']['diagnoses'][] = $this->entry('Diagnosis', $entry->description, $entry, [
                'ICD-10' => $entry->icd_code ?: $entry->icdCodeEntry?->code,
                'Type' => $entry->type,
                'Primary' => $entry->is_primary ? 'Yes' : null,
                'Notes' => $entry->notes,
            ]);
        }

        foreach ($record->investigations ?? [] as $entry) {
            $summary['sections']['investigations'][] = $this->entry('Investigation', $entry->description ?: $entry->investigation_type, $entry, [
                'Type' => $entry->investigation_type,
                'Urgency' => $entry->urgency,
                'Status' => $entry->status,
            ]);
        }

        foreach ($record->treatments ?? [] as $entry) {
            $section = $entry->type === 'advice' ? 'notes' : 'treatments';
            $summary['sections'][$section][] = $this->entry('Treatment', $entry->description, $entry, [
                'Type' => $entry->type,
            ]);
        }

        foreach ($record->prescriptions ?? [] as $entry) {
            $drugs = $entry->items->map(fn ($item) => trim($item->drug_name.' '.$item->dosage.' '.$item->frequency))->filter()->implode('; ');
            $summary['sections']['prescriptions'][] = $this->entry('Prescription', $drugs ?: $entry->prescription_number, $entry, [
                'Prescription No.' => $entry->prescription_number,
                'Status' => $entry->status?->label() ?? $entry->status,
                'Notes' => $entry->notes,
            ]);
        }

        foreach ($record->tasks ?? [] as $entry) {
            $summary['sections']['tasks'][] = $this->entry('Task / Follow-up', $entry->title, $entry, [
                'Description' => $entry->description,
                'Assigned To' => $entry->assignedUser?->full_name,
                'Due' => $entry->due_date?->format('d M Y'),
                'Status' => $entry->status,
                'Completed By' => $entry->completedBy?->full_name,
            ]);
        }

        if ($record->consultationRoute?->isEmergencySession() && $record->consultationRoute->emergencyCase) {
            $this->appendEmergencySections($summary, $record->consultationRoute->emergencyCase);
        }

        return $summary;
    }

    private function appendEmergencySections(array &$summary, object $case): void
    {
        if ($case->triaged_at || $case->triage_notes || $case->current_triage_category) {
            $summary['sections']['examination'][] = $this->entry('Emergency Triage', $case->triage_notes ?: 'Emergency triage recorded.', $case, [
                'Category' => $case->current_triage_category,
                'Automated Category' => $case->auto_triage_category,
                'Score' => $case->triage_score,
                'Triaged By' => $case->triagedBy?->full_name,
            ]);
        }

        foreach ($case->vitals ?? [] as $vital) {
            $summary['sections']['examination'][] = $this->entry('Emergency Vitals', collect([
                'BP' => $vital->blood_pressure,
                'HR' => $vital->heart_rate,
                'RR' => $vital->respiratory_rate,
                'Temp' => $vital->temperature,
                'SpO2' => $vital->spo2 ? $vital->spo2.'%' : null,
            ])->filter()->map(fn ($value, $key) => "{$key}: {$value}")->implode(' · '), $vital, [
                'Context' => $vital->monitoring_context,
                'Recorded At' => $vital->recorded_at?->format('d M Y, h:i A'),
            ]);
        }

        foreach ($case->notes ?? [] as $note) {
            $summary['sections']['notes'][] = $this->entry('Emergency '.str_replace('_', ' ', $note->note_type), $note->content, $note);
        }

        foreach ($case->medicationOrders ?? [] as $order) {
            $summary['sections']['prescriptions'][] = $this->entry('Emergency Medication', $order->display_name, $order, [
                'Dose' => trim(($order->dose ?: '').' '.($order->dose_unit ?: '')),
                'Route' => $order->route,
                'Frequency' => $order->frequency?->name ?? $order->frequency_code,
                'Status' => $order->status,
            ]);
        }

        foreach ($case->labRequests ?? [] as $request) {
            $tests = $request->items->map(fn ($item) => $item->display_name ?? $item->name)->filter()->implode(', ');
            $summary['sections']['investigations'][] = $this->entry('Emergency Investigation', $tests ?: $request->request_number, $request, [
                'Urgency' => $request->urgency,
                'Status' => $request->status,
                'Clinical Info' => $request->clinical_info,
            ]);
        }

        foreach ($case->procedureRequests ?? [] as $procedure) {
            $summary['sections']['procedures'][] = $this->entry('Emergency Procedure', $procedure->service?->name ?? $procedure->procedure?->name ?? 'Procedure request', $procedure, [
                'Priority' => $procedure->priority,
                'Status' => $procedure->status?->label() ?? $procedure->status,
                'Indication' => $procedure->indication,
            ]);
        }

        foreach ($case->consumableUsages ?? [] as $usage) {
            $summary['sections']['treatments'][] = $this->entry('Emergency Consumable', trim(($usage->product?->name ?: 'Consumable').' x '.(float) $usage->quantity_used), $usage, [
                'Billable' => $usage->is_billable ? 'Yes' : 'No',
                'Notes' => $usage->notes,
            ]);
        }
    }

    public function forVisit(Visit $visit): array
    {
        $visit->loadMissing('medicalRecords');

        return [
            'records' => $visit->medicalRecords
                ->map(fn (MedicalRecord $record) => $this->forRecord($record))
                ->values()
                ->all(),
        ];
    }

    private function emptySummary(): array
    {
        return [
            'main_doctor' => null,
            'department' => null,
            'services' => [],
            'contributors' => [],
            'sections' => [
                'complaints' => [],
                'history_of_presenting_complaint' => [],
                'examination' => [],
                'diagnoses' => [],
                'investigations' => [],
                'treatments' => [],
                'prescriptions' => [],
                'procedures' => [],
                'tasks' => [],
                'notes' => [],
            ],
        ];
    }

    private function entry(string $type, ?string $content, object $entry, array $details = []): array
    {
        $creator = $entry->creator
            ?? $entry->createdBy
            ?? $entry->doctor
            ?? $entry->recordedBy
            ?? $entry->requestedBy
            ?? $entry->requestingDoctor
            ?? $entry->prescriber
            ?? $entry->user
            ?? null;
        $updater = $entry->updater ?? null;

        return [
            'type' => $type,
            'content' => $content ?: '-',
            'owner_id' => $creator?->id,
            'owner_key' => $creator?->id ? 'user-'.$creator->id : 'unknown',
            'owner_role' => $creator?->getRoleNames()?->first(),
            'entered_by' => $creator?->full_name ?? 'Unknown user',
            'created_at' => $entry->created_at,
            'updated_by' => $updater?->full_name,
            'updated_at' => $entry->updated_at,
            'department' => $entry->department?->name ?? $entry->medicalRecord?->department?->name ?? null,
            'source_pattern' => $entry->sourcePattern?->name,
            'details' => collect($details)->filter(fn ($value) => filled($value))->all(),
        ];
    }
}
