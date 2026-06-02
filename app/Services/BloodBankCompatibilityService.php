<?php

namespace App\Services;

class BloodBankCompatibilityService
{
    /**
     * Basic red-cell ABO/Rh compatibility. Plasma/component-specific matrices
     * can be added later without changing callers.
     */
    public function isCompatible(string $donorGroup, string $recipientGroup): bool
    {
        [$donorAbo, $donorRh] = $this->split($donorGroup);
        [$recipientAbo, $recipientRh] = $this->split($recipientGroup);

        $aboOk = match ($donorAbo) {
            'O' => true,
            'A' => in_array($recipientAbo, ['A', 'AB'], true),
            'B' => in_array($recipientAbo, ['B', 'AB'], true),
            'AB' => $recipientAbo === 'AB',
            default => false,
        };

        $rhOk = $donorRh === '-' || $recipientRh === '+';

        return $aboOk && $rhOk;
    }

    protected function split(string $group): array
    {
        $group = strtoupper(trim($group));
        $rh = str_ends_with($group, '-') ? '-' : '+';
        $abo = str_replace(['+', '-'], '', $group);

        return [$abo, $rh];
    }
}
