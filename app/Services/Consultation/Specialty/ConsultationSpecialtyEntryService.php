<?php

namespace App\Services\Consultation\Specialty;

use App\Models\ConsultationSpecialtyEntry;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\User;
use App\Models\VisitConsultationRoute;
use App\Services\Consultation\Maternity\ConsultationMaternitySpecialtyWriteGuard;
use App\Services\Consultation\Maternity\ConsultationMaternityWriteBlockedException;
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
            ->with(['createdBy', 'updatedBy'])
            ->latest()
            ->get();
    }

    public function getEntry($consultation, ConsultationSpecialtyProfile $profile, string $sectionKey): ?ConsultationSpecialtyEntry
    {
        return ConsultationSpecialtyEntry::query()
            ->where('consultation_id', $this->consultationId($consultation))
            ->where('consultation_specialty_profile_id', $profile->id)
            ->where('section_key', $sectionKey)
            ->latest()
            ->first();
    }

    public function getEntryById($consultation, ConsultationSpecialtyProfile $profile, string $sectionKey, int $entryId): ?ConsultationSpecialtyEntry
    {
        return ConsultationSpecialtyEntry::query()
            ->whereKey($entryId)
            ->where('consultation_id', $this->consultationId($consultation))
            ->where('consultation_specialty_profile_id', $profile->id)
            ->where('section_key', $sectionKey)
            ->first();
    }

    /**
     * Phase 14R.4 — single source-of-truth chokepoint.
     *
     * The controller already guards form posts, but order sets, quick actions
     * and templates write through this service directly. Enforcing here means
     * NO sanctioned runtime path can persist a maternity-owned field while the
     * guard applies. Returns the entry with blocked keys removed, or throws
     * when the caller asked for strict rejection.
     *
     * @param  array<string, mixed>  $entry
     * @return array<string, mixed>
     */
    public function guardMaternityOwnedFields(
        $consultation,
        ConsultationSpecialtyProfile $profile,
        string $sectionKey,
        array $entry,
        bool $throw = true,
    ): array {
        $route = $consultation instanceof VisitConsultationRoute
            ? $consultation
            : VisitConsultationRoute::find($this->consultationId($consultation));

        if (! $route) {
            return $entry;
        }

        $blocked = app(ConsultationMaternitySpecialtyWriteGuard::class)
            ->blockedFields($route, $profile, $sectionKey, $entry);

        if ($blocked === []) {
            return $entry;
        }

        if ($throw) {
            throw ConsultationMaternityWriteBlockedException::forFields($blocked);
        }

        // Non-strict callers (automation) drop the blocked keys and surface the
        // fact through their own result payload rather than failing the batch.
        return array_diff_key($entry, array_flip($blocked));
    }

    public function createEntry($consultation, ConsultationSpecialtyProfile $profile, string $sectionKey, array $entry, User $user): ConsultationSpecialtyEntry
    {
        $entry = $this->guardMaternityOwnedFields($consultation, $profile, $sectionKey, $entry);

        return DB::transaction(function () use ($consultation, $profile, $sectionKey, $entry, $user) {
            $model = ConsultationSpecialtyEntry::query()->create([
                'consultation_id' => $this->consultationId($consultation),
                'consultation_specialty_profile_id' => $profile->id,
                'section_key' => $sectionKey,
                'entry' => $entry,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $this->entryLogs->created($model, $user);

            return $model;
        });
    }

    public function updateEntry(ConsultationSpecialtyEntry $model, array $entry, User $user): ConsultationSpecialtyEntry
    {
        return DB::transaction(function () use ($model, $entry, $user) {
            $original = $model->getOriginal();

            $model->forceFill([
                'entry' => $entry,
                'updated_by' => $user->id,
            ])->save();

            $this->entryLogs->updated($model, $original, $user);

            return $model;
        });
    }

    public function upsertEntry($consultation, ConsultationSpecialtyProfile $profile, string $sectionKey, array $entry, User $user): ConsultationSpecialtyEntry
    {
        $entry = $this->guardMaternityOwnedFields($consultation, $profile, $sectionKey, $entry);

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
        return $this->entriesAsArrayFrom($this->getEntriesForConsultation($consultation, $profile));
    }

    public function entriesAsArrayFrom(Collection $entries): array
    {
        return $entries
            ->groupBy('section_key')
            ->map(fn (Collection $entries) => $entries->first()?->entry ?? [])
            ->all();
    }

    public function entriesGroupedForWorkspace($consultation, ConsultationSpecialtyProfile $profile): array
    {
        return $this->entriesGroupedForWorkspaceFrom($this->getEntriesForConsultation($consultation, $profile));
    }

    public function entriesGroupedForWorkspaceFrom(Collection $entries): array
    {
        return $entries
            ->groupBy('section_key')
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
