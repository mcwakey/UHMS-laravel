<?php

namespace App\Services\Consultation\Specialty;

use App\Models\ConsultationSpecialtyEntry;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\User;
use App\Models\VisitConsultationRoute;
use App\Services\MedicalRecordEntryLogService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ConsultationSpecialtyEntryService
{
    public function __construct(
        private readonly MedicalRecordEntryLogService $entryLogs,
    ) {}

    public function getEntriesForConsultation($consultation, ?ConsultationSpecialtyProfile $profile = null): Collection
    {
        return ConsultationSpecialtyEntry::query()
            ->where('consultation_id', $this->consultationId($consultation))
            ->when($profile, fn ($query) => $query->where('consultation_specialty_profile_id', $profile->id))
            ->latest()
            ->get();
    }

    public function getEntry($consultation, ConsultationSpecialtyProfile $profile, string $sectionKey): ?ConsultationSpecialtyEntry
    {
        return ConsultationSpecialtyEntry::query()
            ->where('consultation_id', $this->consultationId($consultation))
            ->where('consultation_specialty_profile_id', $profile->id)
            ->where('section_key', $sectionKey)
            ->first();
    }

    public function upsertEntry($consultation, ConsultationSpecialtyProfile $profile, string $sectionKey, array $entry, User $user): ConsultationSpecialtyEntry
    {
        return DB::transaction(function () use ($consultation, $profile, $sectionKey, $entry, $user) {
            $existing = $this->getEntry($consultation, $profile, $sectionKey);

            $model = ConsultationSpecialtyEntry::query()->updateOrCreate(
                [
                    'consultation_id' => $this->consultationId($consultation),
                    'consultation_specialty_profile_id' => $profile->id,
                    'section_key' => $sectionKey,
                ],
                [
                    'entry' => $entry,
                    'created_by' => $existing?->created_by ?? $user->id,
                    'updated_by' => $user->id,
                ],
            );

            $existing
                ? $this->entryLogs->updated($model, $existing->getOriginal(), $user)
                : $this->entryLogs->created($model, $user);

            return $model;
        });
    }

    public function deleteEntry(ConsultationSpecialtyEntry $entry, User $user): bool
    {
        $this->entryLogs->deleted($entry, $user);

        return (bool) $entry->delete();
    }

    public function entriesAsArray($consultation, ConsultationSpecialtyProfile $profile): array
    {
        return $this->getEntriesForConsultation($consultation, $profile)
            ->keyBy('section_key')
            ->map(fn (ConsultationSpecialtyEntry $entry) => $entry->entry ?? [])
            ->all();
    }

    private function consultationId($consultation): ?int
    {
        if ($consultation instanceof VisitConsultationRoute) {
            return $consultation->id;
        }

        return is_numeric($consultation) ? (int) $consultation : ($consultation?->id ?? null);
    }
}
