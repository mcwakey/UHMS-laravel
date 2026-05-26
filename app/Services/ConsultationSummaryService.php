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
            'consultationRoute.contributors.user',
            'consultationRoute.routeServices.service',
            'complaints.creator',
            'complaints.sourcePattern',
            'historiesOfPresentingComplaint.creator',
            'historiesOfPresentingComplaint.sourcePattern',
            'physicalExaminations.creator',
            'physicalExaminations.sourcePattern',
            'diagnoses.creator',
            'diagnoses.sourcePattern',
            'investigations.creator',
            'investigations.sourcePattern',
            'treatments.creator',
            'treatments.sourcePattern',
            'prescriptions.creator',
            'prescriptions.doctor',
            'prescriptions.sourcePattern',
            'prescriptions.items',
            'tasks.creator',
            'tasks.assignedUser',
            'tasks.completedBy',
            'tasks.sourcePattern',
        ]);

        $summary = $this->emptySummary();
        $summary['main_doctor'] = $record->consultationRoute?->doctor?->full_name;
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

        return $summary;
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
        $creator = $entry->creator ?? $entry->createdBy ?? $entry->doctor ?? null;

        return [
            'type' => $type,
            'content' => $content ?: '-',
            'entered_by' => $creator?->full_name ?? 'Unknown user',
            'created_at' => $entry->created_at,
            'department' => $entry->department?->name ?? $entry->medicalRecord?->department?->name ?? null,
            'source_pattern' => $entry->sourcePattern?->name,
            'details' => collect($details)->filter(fn ($value) => filled($value))->all(),
        ];
    }
}
