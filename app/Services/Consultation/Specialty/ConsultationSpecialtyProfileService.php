<?php

namespace App\Services\Consultation\Specialty;

use App\Enums\DepartmentType;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\ConsultationSpecialtySection;
use Illuminate\Database\Eloquent\Collection;

class ConsultationSpecialtyProfileService
{
    public function getDefaultProfile(): ConsultationSpecialtyProfile
    {
        return $this->ensureGeneralProfileExists();
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

    /**
     * @return Collection<int, ConsultationSpecialtySection>
     */
    public function getSectionsForProfile(ConsultationSpecialtyProfile $profile): Collection
    {
        return $profile->activeSections()->get();
    }

    public function ensureGeneralProfileExists(): ConsultationSpecialtyProfile
    {
        return ConsultationSpecialtyProfile::query()->firstOrCreate(
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
    }
}
