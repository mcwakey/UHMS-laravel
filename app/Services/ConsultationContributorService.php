<?php

namespace App\Services;

use App\Models\MedicalRecord;
use App\Models\User;

class ConsultationContributorService
{
    public function recordContribution(MedicalRecord $record, User $user, ?string $role = null): void
    {
        if (! $record->consultation_route_id) {
            return;
        }

        $record->consultationRoute?->contributors()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'role' => $role,
                'first_contributed_at' => $record->consultationRoute
                    ? $record->consultationRoute->contributors()->where('user_id', $user->id)->value('first_contributed_at') ?: now()
                    : now(),
                'last_contributed_at' => now(),
            ],
        );
    }
}
