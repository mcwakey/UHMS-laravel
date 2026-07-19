<?php

namespace App\Services\Consultation\Specialty;

use App\Data\Consultation\Specialty\ResolvedConsultationSpecialty;
use App\Enums\DepartmentType;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\ConsultationSpecialtySection;
use Illuminate\Database\Eloquent\Collection;

class ConsultationSpecialtyProfileService
{
    private ?ConsultationSpecialtyProfile $defaultProfile = null;

    /** @var array<int, Collection<int, ConsultationSpecialtySection>> */
    private array $visibleSections = [];

    private const GENERAL_SECTIONS = [
        'patient_summary',
        'complaints',
        'hopc',
        'examination',
        'diagnosis',
        'investigations',
        'prescription',
        'procedures',
        'tasks',
        'notes',
        'summary',
        'completion_readiness',
    ];

    public function getDefaultProfile(): ConsultationSpecialtyProfile
    {
        return $this->defaultProfile ??= ConsultationSpecialtyProfile::query()
            ->byCode(ConsultationSpecialtyProfile::GENERAL_MEDICINE)
            ->first()
            ?? $this->ensureGeneralProfileExists();
    }

    /**
     * @return Collection<int, ConsultationSpecialtyProfile>
     */
    public function getActiveProfiles(): Collection
    {
        return ConsultationSpecialtyProfile::query()
            ->active()
            ->ordered()
            ->get();
    }

    public function findByCode(string $code): ?ConsultationSpecialtyProfile
    {
        return ConsultationSpecialtyProfile::query()
            ->byCode($code)
            ->first();
    }

    public function getActiveProfileById(?int $id): ?ConsultationSpecialtyProfile
    {
        return $id
            ? ConsultationSpecialtyProfile::query()->active()->find($id)
            : null;
    }

    public function getActiveProfileByCode(?string $code): ?ConsultationSpecialtyProfile
    {
        return $code
            ? ConsultationSpecialtyProfile::query()->active()->byCode($code)->first()
            : null;
    }

    /**
     * @return Collection<int, ConsultationSpecialtySection>
     */
    public function getSectionsForProfile(ConsultationSpecialtyProfile $profile): Collection
    {
        return $profile->activeSections()->get();
    }

    /**
     * @return Collection<int, ConsultationSpecialtySection>
     */
    public function getVisibleOrderedSections(ConsultationSpecialtyProfile $profile): Collection
    {
        return $this->visibleSections[$profile->id] ??= $profile->activeSections()->get();
    }

    public function fallbackResolvedContext(
        ?object $department = null,
        ?object $consultationRoute = null,
        ?string $reason = 'No matching active specialty profile mapping was found.',
    ): ResolvedConsultationSpecialty {
        $profile = $this->getDefaultProfile();

        return new ResolvedConsultationSpecialty(
            profile: $profile,
            source: 'fallback',
            reason: $reason,
            department: $department,
            consultationRoute: $consultationRoute,
            sections: $this->getVisibleOrderedSections($profile)->all(),
            isFallback: true,
        );
    }

    public function ensureGeneralProfileExists(): ConsultationSpecialtyProfile
    {
        $profile = ConsultationSpecialtyProfile::query()->updateOrCreate(
            ['code' => ConsultationSpecialtyProfile::GENERAL_MEDICINE],
            [
                'name' => 'General Medicine',
                'description' => 'Default general consultation workspace profile.',
                'department_type' => DepartmentType::CONSULTATION->value,
                'icon' => 'ti-stethoscope',
                'color' => 'primary',
                'is_active' => true,
                'sort_order' => 0,
            ],
        );

        foreach (self::GENERAL_SECTIONS as $index => $sectionKey) {
            $profile->sections()->updateOrCreate(
                ['section_key' => $sectionKey],
                [
                    'label' => str($sectionKey)->replace('_', ' ')->title()->toString(),
                    'display_order' => ($index + 1) * 10,
                    'is_required' => $sectionKey === 'patient_summary',
                    'is_visible' => true,
                ],
            );
        }

        $this->visibleSections[$profile->id] = $profile->activeSections()->get();

        return $this->defaultProfile = $profile;
    }
}
