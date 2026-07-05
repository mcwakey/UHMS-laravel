<?php

namespace App\Services\Consultation\Specialty;

use App\Models\DoctorConsultationPreference;
use App\Models\User;

class DoctorConsultationPreferenceService
{
    public function getOrCreatePreference(User $user): DoctorConsultationPreference
    {
        return DoctorConsultationPreference::query()->firstOrCreate([
            'user_id' => $user->id,
        ], [
            'pinned_actions' => [],
            'preferred_layout' => 'default',
            'compact_mode' => false,
            'metadata' => [],
        ]);
    }

    public function updatePinnedActions(User $user, array $actions): DoctorConsultationPreference
    {
        $preference = $this->getOrCreatePreference($user);
        $preference->forceFill([
            'pinned_actions' => array_values(array_unique(array_filter($actions))),
        ])->save();

        return $preference->fresh();
    }

    public function updateLayoutPreference(User $user, ?string $layout, bool $compactMode): DoctorConsultationPreference
    {
        $preference = $this->getOrCreatePreference($user);
        $preference->forceFill([
            'preferred_layout' => $layout ?: 'default',
            'compact_mode' => $compactMode,
        ])->save();

        return $preference->fresh();
    }
}
