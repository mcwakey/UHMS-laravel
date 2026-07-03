<?php

namespace App\Services\Consultation;

use App\Enums\PrescriptionStatus;
use App\Models\Drug;
use App\Models\MedicalRecord;
use App\Models\PrescriptionItem;
use Illuminate\Support\Str;

class PrescriptionSafetyService
{
    public function assess(MedicalRecord $record, array $data): PrescriptionSafetyResult
    {
        $record->loadMissing(['patient', 'diagnoses']);

        $warnings = [];
        $blocking = [];
        $items = collect($data['items'] ?? [])->values();

        if ($items->isEmpty()) {
            $blocking[] = $this->entry('missing_required_field', __('consultation.safety.missing_required_field'));
        }

        if (
            config('consultation.prescriptions.require_diagnosis_before_prescribing')
            && $record->diagnoses->isEmpty()
        ) {
            $entry = $this->entry('missing_diagnosis', __('consultation.safety.missing_diagnosis'));
            if (config('consultation.prescriptions.allow_missing_diagnosis_override')) {
                $warnings[] = $entry;
            } else {
                $blocking[] = $entry;
            }
        }

        $drugIds = $items->pluck('drug_id')->filter()->map(fn ($id) => (int) $id)->unique();
        $drugs = Drug::query()
            ->whereIn('id', $drugIds)
            ->with(['genericName'])
            ->get()
            ->keyBy('id');

        foreach ($items as $index => $item) {
            if ($this->itemMissingRequiredField($item)) {
                $blocking[] = $this->entry('missing_required_field', __('consultation.safety.missing_required_field'), $index);
                continue;
            }

            $drug = ! empty($item['drug_id']) ? $drugs->get((int) $item['drug_id']) : null;
            $drugName = (string) ($item['drug_name'] ?? $drug?->name ?? '');

            if ($this->matchesAllergy((string) ($record->patient?->allergies ?? ''), $drugName, $drug)) {
                $warnings[] = $this->entry('allergy_conflict', __('consultation.safety.allergy_conflict'), $index);
            }

            if ($this->hasDuplicateActiveMedication($record, $item, $drugName)) {
                $warnings[] = $this->entry('duplicate_active_medication', __('consultation.safety.duplicate_active_medication'), $index);
            }

            if ($this->isUnverifiedDose((string) ($item['dosage'] ?? ''))) {
                $warnings[] = $this->entry('unusual_dose_unverified', __('consultation.safety.unusual_dose_unverified'), $index);
            }
        }

        return new PrescriptionSafetyResult(
            warnings: $this->uniqueEntries($warnings),
            blockingErrors: $this->uniqueEntries($blocking),
        );
    }

    private function itemMissingRequiredField(array $item): bool
    {
        foreach (['drug_name', 'dosage', 'frequency', 'duration', 'quantity', 'route'] as $field) {
            if (! filled($item[$field] ?? null)) {
                return true;
            }
        }

        return false;
    }

    private function matchesAllergy(string $allergies, string $drugName, ?Drug $drug): bool
    {
        $tokens = collect(preg_split('/[,;\n\r]+/', $allergies) ?: [])
            ->map(fn ($token) => $this->normalise($token))
            ->filter(fn ($token) => Str::length($token) >= 3)
            ->values();

        if ($tokens->isEmpty()) {
            return false;
        }

        $haystacks = collect([
            $drugName,
            $drug?->name,
            $drug?->generic_name,
            $drug?->genericName?->name,
            $drug?->brand_name,
        ])->filter()->map(fn ($value) => $this->normalise($value));

        return $tokens->contains(function (string $allergy) use ($haystacks) {
            return $haystacks->contains(fn (string $candidate) => Str::contains($candidate, $allergy));
        });
    }

    private function hasDuplicateActiveMedication(MedicalRecord $record, array $item, string $drugName): bool
    {
        $days = max(1, (int) config('consultation.prescriptions.duplicate_active_medication_days', 30));
        $normalisedName = $this->normalise($drugName);
        $drugId = ! empty($item['drug_id']) ? (int) $item['drug_id'] : null;

        return PrescriptionItem::query()
            ->where(function ($query) use ($drugId, $normalisedName) {
                if ($drugId) {
                    $query->where('drug_id', $drugId);
                }

                if ($normalisedName !== '') {
                    $query->when($drugId, fn ($nested) => $nested->orWhereRaw('LOWER(drug_name) = ?', [$normalisedName]))
                        ->when(! $drugId, fn ($nested) => $nested->whereRaw('LOWER(drug_name) = ?', [$normalisedName]));
                }
            })
            ->whereHas('prescription', function ($query) use ($record, $days) {
                $query->where('patient_id', $record->patient_id)
                    ->where('id', '!=', 0)
                    ->where('created_at', '>=', now()->subDays($days))
                    ->whereNotIn('status', [
                        PrescriptionStatus::DISPENSED->value,
                        PrescriptionStatus::CANCELLED->value,
                    ]);
            })
            ->exists();
    }

    private function isUnverifiedDose(string $dosage): bool
    {
        $normalised = $this->normalise($dosage);

        return in_array($normalised, ['as directed', 'unknown', 'tbd', 'na', 'n/a', '-'], true);
    }

    private function entry(string $code, string $message, ?int $itemIndex = null): array
    {
        return array_filter([
            'code' => $code,
            'message' => $message,
            'item_index' => $itemIndex,
        ], fn ($value) => $value !== null);
    }

    private function uniqueEntries(array $entries): array
    {
        return collect($entries)
            ->unique(fn (array $entry) => ($entry['code'] ?? '').':'.($entry['item_index'] ?? 'all'))
            ->values()
            ->all();
    }

    private function normalise(?string $value): string
    {
        return trim(Str::lower((string) $value));
    }
}
