<?php

namespace App\Services;

use App\Models\BloodUnit;
use Illuminate\Support\Collection;

/**
 * Component-aware ABO/Rh blood compatibility engine.
 *
 * Compatibility matrices live in config/blood_bank.php so policy can be tuned
 * without code changes. This service is the single backend authority for
 * compatibility — callers (crossmatch, issue) must never bypass it or rely on
 * front-end logic alone.
 */
class BloodBankCompatibilityService
{
    public const COMPATIBLE = 'COMPATIBLE';

    public const COMPATIBLE_WITH_CAUTION = 'COMPATIBLE_WITH_CAUTION';

    public const INCOMPATIBLE = 'INCOMPATIBLE';

    /**
     * Backward-compatible boolean check used by existing callers.
     * Signature: (donor group, recipient group [, component]).
     */
    public function isCompatible(string $donorGroup, string $recipientGroup, string $componentType = 'WHOLE_BLOOD'): bool
    {
        return $this->evaluate($recipientGroup, $donorGroup, $componentType)['status'] === self::COMPATIBLE;
    }

    /**
     * Full compatibility evaluation for a donor unit against a recipient.
     *
     * @return array{status:string, abo_ok:bool, rh_ok:bool, component_model:string, reason:string}
     */
    public function evaluate(string $recipientGroup, string $donorGroup, string $componentType = 'WHOLE_BLOOD'): array
    {
        [$rAbo, $rRh] = $this->split($recipientGroup);
        [$dAbo, $dRh] = $this->split($donorGroup);
        $model = $this->componentModel($componentType);

        $allowedAbo = (array) config("blood_bank.compatibility.{$model}.{$rAbo}", []);
        $aboOk = in_array($dAbo, $allowedAbo, true);
        $rhEnforced = (bool) config("blood_bank.rh.enforce_for_{$model}", false);
        // Rh-negative recipient must not receive Rh-positive (when enforced).
        $rhOk = ! $rhEnforced || $rRh === '+' || $dRh === '-';

        if (! $aboOk) {
            return [
                'status' => self::INCOMPATIBLE,
                'abo_ok' => false,
                'rh_ok' => $rhOk,
                'component_model' => $model,
                'reason' => "{$dAbo} {$this->modelLabel($model)} are not ABO-compatible with {$rAbo} recipient.",
            ];
        }

        if (! $rhOk) {
            return [
                'status' => self::INCOMPATIBLE,
                'abo_ok' => true,
                'rh_ok' => false,
                'component_model' => $model,
                'reason' => "Rh-positive {$this->modelLabel($model)} cannot be given to an Rh-negative recipient without emergency override.",
            ];
        }

        // Platelets: ABO-non-identical (but allowed) and Rh+→Rh- are "with caution".
        if ($model === 'platelet') {
            $caution = ($dAbo !== $rAbo) || ($rRh === '-' && $dRh === '+');
            if ($caution) {
                return [
                    'status' => self::COMPATIBLE_WITH_CAUTION,
                    'abo_ok' => true,
                    'rh_ok' => true,
                    'component_model' => $model,
                    'reason' => "Non-identical platelet match for {$rAbo}{$rRh} recipient — acceptable with caution per policy.",
                ];
            }
        }

        return [
            'status' => self::COMPATIBLE,
            'abo_ok' => true,
            'rh_ok' => true,
            'component_model' => $model,
            'reason' => "{$dAbo}{$dRh} {$this->modelLabel($model)} are compatible with {$rAbo}{$rRh} recipient.",
        ];
    }

    /**
     * Detailed compatibility wrapper (spec: compatibilityDetails).
     */
    public function compatibilityDetails(string $recipientGroup, string $donorGroup, string $componentType = 'WHOLE_BLOOD'): array
    {
        return array_merge(
            ['recipient_group' => $this->normalise($recipientGroup), 'donor_group' => $this->normalise($donorGroup), 'component' => $componentType],
            $this->evaluate($recipientGroup, $donorGroup, $componentType)
        );
    }

    /**
     * All donor ABO/Rh groups that may be given to this recipient for a component.
     *
     * @return array<int,string>
     */
    public function compatibleGroupsFor(string $recipientGroup, string $componentType = 'WHOLE_BLOOD'): array
    {
        [$rAbo, $rRh] = $this->split($recipientGroup);
        $model = $this->componentModel($componentType);
        $allowedAbo = (array) config("blood_bank.compatibility.{$model}.{$rAbo}", []);
        $rhEnforced = (bool) config("blood_bank.rh.enforce_for_{$model}", false);

        $groups = [];
        foreach ($allowedAbo as $abo) {
            foreach (['-', '+'] as $rh) {
                if ($rhEnforced && $rRh === '-' && $rh === '+') {
                    continue;
                }
                $groups[] = $abo.$rh;
            }
        }

        return $groups;
    }

    public function explainIncompatibility(string $recipientGroup, string $donorGroup, string $componentType = 'WHOLE_BLOOD'): string
    {
        return $this->evaluate($recipientGroup, $donorGroup, $componentType)['reason'];
    }

    /**
     * Suggest available, screened, non-expired units compatible with a recipient,
     * ordered by exact match → compatible → earliest expiry.
     *
     * @return Collection<int,BloodUnit>
     */
    public function suggestUnits(string $recipientGroup, string $componentType = 'WHOLE_BLOOD', int $limit = 25): Collection
    {
        $recipientGroup = $this->normalise($recipientGroup);
        $compatibleGroups = $this->compatibleGroupsFor($recipientGroup, $componentType);

        if (empty($compatibleGroups)) {
            return collect();
        }

        $units = BloodUnit::query()
            ->with('storageLocation', 'donor')
            ->whereIn('blood_group', $compatibleGroups)
            ->where('screening_status', BloodUnit::SCREENING_PASSED)
            ->whereIn('status', [BloodUnit::STATUS_AVAILABLE, BloodUnit::STATUS_RESERVED, BloodUnit::STATUS_CROSSMATCHED])
            ->whereDate('expiry_date', '>=', today())
            ->orderBy('expiry_date')
            ->limit(max($limit, 100))
            ->get();

        return $units
            ->map(function (BloodUnit $unit) use ($recipientGroup, $componentType) {
                $eval = $this->evaluate($recipientGroup, $unit->blood_group, $componentType);
                $unit->setAttribute('compatibility_status', $eval['status']);
                $unit->setAttribute('compatibility_reason', $eval['reason']);
                $unit->setAttribute('is_exact_match', $this->normalise($unit->blood_group) === $recipientGroup);

                return $unit;
            })
            ->sortBy([
                fn ($u) => $u->is_exact_match ? 0 : 1,
                fn ($u) => $u->compatibility_status === self::COMPATIBLE ? 0 : 1,
                fn ($u) => $u->expiry_date?->timestamp ?? PHP_INT_MAX,
            ])
            ->take($limit)
            ->values();
    }

    public function componentModel(string $componentType): string
    {
        $key = strtoupper(trim($componentType));

        return config("blood_bank.component_compatibility_type.{$key}")
            ?? config('blood_bank.default_compatibility_type', 'red_cell');
    }

    protected function modelLabel(string $model): string
    {
        return match ($model) {
            'plasma' => 'plasma',
            'platelet' => 'platelets',
            default => 'red cells',
        };
    }

    protected function normalise(string $group): string
    {
        [$abo, $rh] = $this->split($group);

        return $abo.$rh;
    }

    /**
     * @return array{0:string,1:string} [ABO, Rh sign]
     */
    protected function split(string $group): array
    {
        $group = strtoupper(trim($group));
        $rh = str_ends_with($group, '-') ? '-' : '+';
        $abo = str_replace(['+', '-', ' '], '', $group);

        return [$abo, $rh];
    }
}
