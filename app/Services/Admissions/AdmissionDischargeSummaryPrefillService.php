<?php

namespace App\Services\Admissions;

use App\Models\Admission;
use Illuminate\Support\Collection;

class AdmissionDischargeSummaryPrefillService
{
    public function forAdmission(Admission $admission): array
    {
        $record = $admission->visit?->medicalRecord;

        $diagnoses = $record?->diagnoses ?? collect();
        $primaryDiagnosis = $this->diagnosisLabel(
            $diagnoses->firstWhere('is_primary', true) ?? $diagnoses->first()
        );

        $secondaryDiagnoses = $diagnoses
            ->reject(fn ($diagnosis) => (bool) $diagnosis->is_primary)
            ->map(fn ($diagnosis) => $this->diagnosisLabel($diagnosis))
            ->filter()
            ->values();

        $complaints = $this->lines($record?->complaints ?? collect(), fn ($complaint) => trim(collect([
            $complaint->description,
            $complaint->duration ? 'Duration: '.$complaint->duration : null,
            $complaint->severity ? 'Severity: '.$complaint->severity : null,
            $complaint->notes,
        ])->filter()->implode(' | ')));

        $histories = $this->lines($record?->historiesOfPresentingComplaint ?? collect(), fn ($history) => $history->content ?: $history->notes);

        $examinations = $this->lines($record?->physicalExaminations ?? collect(), fn ($exam) => collect([
            $exam->findings,
            $exam->general_examination,
            $exam->systemic_examination,
            $exam->cardiovascular,
            $exam->respiratory,
            $exam->gastrointestinal,
            $exam->central_nervous_system,
            $exam->musculoskeletal,
            $exam->specialty_examination,
            $exam->local_examination,
            $exam->notes,
        ])->filter()->implode('; '));

        $investigations = $this->lines($record?->investigations ?? collect(), fn ($investigation) => trim(collect([
            $investigation->description,
            $investigation->investigation_type ? 'Type: '.$investigation->investigation_type : null,
            $investigation->status ? 'Status: '.$investigation->status : null,
            $investigation->notes,
        ])->filter()->implode(' | ')));

        $treatments = $this->lines($record?->treatments ?? collect(), fn ($treatment) => trim(collect([
            $treatment->description,
            $treatment->type ? 'Type: '.$treatment->type : null,
        ])->filter()->implode(' | ')));

        $prescriptions = $this->prescriptionLines($record?->prescriptions ?? collect());
        $medicationOrders = $this->lines($admission->medicationOrders ?? collect(), fn ($order) => trim(collect([
            $order->display_name,
            $order->dose ? 'Dose: '.$order->dose.($order->dose_unit ? ' '.$order->dose_unit : '') : null,
            $order->frequency_code ? 'Frequency: '.$order->frequency_code : null,
            $order->duration_value ? 'Duration: '.$order->duration_value.' '.$order->duration_unit : null,
            $order->route ? 'Route: '.$order->route : null,
            $order->status ? 'Status: '.$order->status : null,
            $order->instructions,
        ])->filter()->implode(' | ')));
        $medicationAdministrations = $this->lines($admission->medicationAdministrations ?? collect(), fn ($administration) => trim(collect([
            $administration->administered_at?->format('d M Y H:i') ?? $administration->scheduled_at?->format('d M Y H:i'),
            $administration->medicationOrder?->display_name,
            $administration->dose_given ? 'Dose given: '.$administration->dose_given.($administration->dose_unit ? ' '.$administration->dose_unit : '') : null,
            $administration->route ? 'Route: '.$administration->route : null,
            $administration->status ? 'Status: '.$administration->status : null,
            $administration->notes,
            $administration->reaction ? 'Reaction: '.$administration->reaction : null,
        ])->filter()->implode(' | ')));
        $clinicalTasks = $this->lines($admission->clinicalTasks ?? collect(), fn ($task) => trim(collect([
            $task->due_at?->format('d M Y H:i') ?? $task->scheduled_at?->format('d M Y H:i'),
            $task->title,
            $task->task_type ? 'Type: '.$task->task_type : null,
            $task->computed_status ? 'Status: '.$task->computed_status : null,
            $task->description,
            $task->notes,
        ])->filter()->implode(' | ')));
        $nursingTasks = $this->lines($admission->nursingTasks ?? collect(), fn ($task) => trim(collect([
            $task->due_at?->format('d M Y H:i'),
            $task->title,
            $task->task_type ? 'Type: '.$this->enumLabel($task->task_type) : null,
            $task->status ? 'Status: '.$this->enumLabel($task->status) : null,
            $task->description,
        ])->filter()->implode(' | ')));
        $serviceRenderings = $this->lines($admission->serviceRenderings ?? collect(), fn ($rendering) => trim(collect([
            $rendering->rendered_at?->format('d M Y H:i') ?? $rendering->started_at?->format('d M Y H:i'),
            $rendering->service?->name,
            $rendering->status ? 'Status: '.$rendering->status : null,
            $rendering->result_summary,
            $rendering->notes,
        ])->filter()->implode(' | ')));
        $wardRounds = $this->lines($admission->wardRounds ?? collect(), fn ($round) => trim(collect([
            $round->round_date?->format('d M Y H:i'),
            $round->notes,
            $round->instructions ? 'Instructions: '.$round->instructions : null,
        ])->filter()->implode(' | ')));
        $nursingNotes = $this->lines($admission->nursingNotes ?? collect(), fn ($note) => trim(collect([
            $note->observed_at?->format('d M Y H:i'),
            $note->note_type ? $note->note_type->label().':' : null,
            $note->note,
        ])->filter()->implode(' ')));
        $latestVitals = $admission->visit?->vitals?->sortByDesc('recorded_at')->first();

        return [
            'primary_diagnosis' => $primaryDiagnosis ?: $admission->admitting_diagnosis,
            'secondary_diagnoses' => $secondaryDiagnoses,
            'admission_reason' => $this->joinSections([
                $admission->admitting_diagnosis,
                $complaints->isNotEmpty() ? "Presenting complaints:\n".$complaints->implode("\n") : null,
                $histories->isNotEmpty() ? "History:\n".$histories->implode("\n") : null,
            ]),
            'hospital_course' => $this->joinSections([
                $record?->final_note ? "Consultation summary:\n".$record->final_note : null,
                $examinations->isNotEmpty() ? "Examination findings:\n".$examinations->implode("\n") : null,
                $wardRounds->isNotEmpty() ? "Ward rounds:\n".$wardRounds->implode("\n") : null,
                $nursingNotes->isNotEmpty() ? "Nursing notes:\n".$nursingNotes->implode("\n") : null,
                $nursingTasks->isNotEmpty() ? "Nursing tasks:\n".$nursingTasks->implode("\n") : null,
                $clinicalTasks->isNotEmpty() ? "Clinical tasks:\n".$clinicalTasks->implode("\n") : null,
                $latestVitals ? 'Latest vitals: '.$this->vitalsLine($latestVitals) : null,
            ]),
            'investigations_summary' => $investigations->implode("\n"),
            'procedures_summary' => '',
            'treatment_given' => $this->joinSections([
                $treatments->isNotEmpty() ? "Treatment plan:\n".$treatments->implode("\n") : null,
                $medicationAdministrations->isNotEmpty() ? "Medication administration:\n".$medicationAdministrations->implode("\n") : null,
                $serviceRenderings->isNotEmpty() ? "Rendered services:\n".$serviceRenderings->implode("\n") : null,
            ]),
            'discharge_medications' => $this->joinSections([
                $prescriptions->isNotEmpty() ? "Consultation prescriptions:\n".$prescriptions->implode("\n") : null,
                $medicationOrders->isNotEmpty() ? "Inpatient medication orders:\n".$medicationOrders->implode("\n") : null,
            ]),
            'follow_up_instructions' => $this->followUpInstructions($record),
            'follow_up_date' => $this->followUpDate($record),
            'warning_signs' => $this->followUpWarningSigns($record),
            'discharge_condition' => $admission->status?->label(),
            'final_outcome' => $admission->status?->label(),
        ];
    }

    private function diagnosisLabel($diagnosis): ?string
    {
        if (! $diagnosis) {
            return null;
        }

        return trim(collect([
            $diagnosis->icd_code ?: $diagnosis->icdCodeEntry?->code,
            $diagnosis->description,
        ])->filter()->implode(' - '));
    }

    private function lines(Collection $items, callable $callback): Collection
    {
        return $items
            ->map($callback)
            ->map(fn ($line) => trim((string) $line))
            ->filter()
            ->values();
    }

    private function prescriptionLines(Collection $prescriptions): Collection
    {
        return $prescriptions->flatMap(function ($prescription) {
            return ($prescription->items ?? collect())->map(function ($item) {
                return trim(collect([
                    $item->drug?->name ?? $item->drug_name,
                    $item->dosage,
                    $item->frequency,
                    $item->duration,
                    $item->route,
                    $item->instructions,
                ])->filter()->implode(' | '));
            });
        })->filter()->values();
    }

    private function followUpInstructions($record): ?string
    {
        $entry = $this->latestSpecialtyEntry($record, 'follow_up');
        if (! $entry) {
            return null;
        }

        return collect([
            $entry['patient_instructions'] ?? null,
            $entry['follow_up_reason'] ?? null,
            $entry['instructions'] ?? null,
        ])->filter()->implode("\n");
    }

    private function followUpWarningSigns($record): ?string
    {
        $entry = $this->latestSpecialtyEntry($record, 'follow_up');

        return $entry['warning_signs'] ?? $entry['danger_signs'] ?? null;
    }

    private function followUpDate($record): ?string
    {
        $entry = $this->latestSpecialtyEntry($record, 'follow_up');

        return $entry['follow_up_date'] ?? null;
    }

    private function latestSpecialtyEntry($record, string $key): ?array
    {
        $route = $record?->consultationRoute;
        $entries = $route && $route->relationLoaded('specialtyEntries') ? $route->specialtyEntries : collect();
        $entry = $entries
            ->where('section_key', $key)
            ->sortByDesc('created_at')
            ->first();

        return is_array($entry?->entry) ? $entry->entry : null;
    }

    private function vitalsLine($vital): string
    {
        return collect([
            $vital->blood_pressure ? 'BP '.$vital->blood_pressure : null,
            $vital->heart_rate ? 'HR '.$vital->heart_rate : null,
            $vital->respiratory_rate ? 'RR '.$vital->respiratory_rate : null,
            $vital->temperature ? 'Temp '.$vital->temperature.'C' : null,
            $vital->spo2 ? 'SpO2 '.$vital->spo2.'%' : null,
            $vital->blood_sugar ? 'Sugar '.$vital->blood_sugar : null,
        ])->filter()->implode(', ');
    }

    private function enumLabel($value): string
    {
        if (is_object($value) && method_exists($value, 'label')) {
            return $value->label();
        }

        if (is_object($value) && property_exists($value, 'value')) {
            return (string) $value->value;
        }

        return (string) $value;
    }

    private function joinSections(array $sections): string
    {
        return collect($sections)
            ->map(fn ($section) => trim((string) $section))
            ->filter()
            ->implode("\n\n");
    }
}
