<?php

namespace App\Services\Maternity;

use App\Enums\AntenatalDangerSign;
use App\Enums\AntenatalRiskFlag;
use App\Enums\MaternityRiskLevel;
use App\Models\AntenatalVisit;

class AntenatalRiskAssessmentService
{
    public function assess(array|AntenatalVisit $payload): array
    {
        $data = $payload instanceof AntenatalVisit ? $payload->getAttributes() + [
            'danger_signs' => $payload->danger_signs ?? [],
            'risk_flags' => $payload->risk_flags ?? [],
        ] : $payload;

        $dangerSigns = collect($data['danger_signs'] ?? [])->filter()->values();
        $riskFlags = collect($data['risk_flags'] ?? [])->filter()->values();
        $warnings = [];

        if (($data['blood_pressure_systolic'] ?? null) >= 140 || ($data['blood_pressure_diastolic'] ?? null) >= 90) {
            $riskFlags->push(AntenatalRiskFlag::HIGH_BLOOD_PRESSURE->value);
            $warnings[] = __('maternity.anc_warning_high_bp');
        }

        if (($data['haemoglobin'] ?? null) !== null && (float) $data['haemoglobin'] < 10.0) {
            $riskFlags->push(AntenatalRiskFlag::LOW_HAEMOGLOBIN->value);
            $warnings[] = __('maternity.anc_warning_low_hb');
        }

        if (in_array($data['presentation'] ?? null, ['breech', 'transverse', 'oblique'], true)) {
            $riskFlags->push(AntenatalRiskFlag::BREECH_OR_ABNORMAL_PRESENTATION->value);
            $warnings[] = __('maternity.anc_warning_abnormal_presentation');
        }

        if ($dangerSigns->isNotEmpty()) {
            $warnings[] = __('maternity.anc_warning_danger_signs');
        }

        $riskFlags = $riskFlags->unique()->values();
        $riskLevel = match (true) {
            $dangerSigns->contains(fn ($sign) => in_array($sign, [
                AntenatalDangerSign::CONVULSIONS->value,
                AntenatalDangerSign::VAGINAL_BLEEDING->value,
                AntenatalDangerSign::SEVERE_ABDOMINAL_PAIN->value,
                AntenatalDangerSign::BREATHLESSNESS->value,
            ], true)) => MaternityRiskLevel::EMERGENCY,
            $dangerSigns->isNotEmpty() || $riskFlags->count() >= 2 => MaternityRiskLevel::HIGH,
            $riskFlags->isNotEmpty() => MaternityRiskLevel::MODERATE,
            default => MaternityRiskLevel::LOW,
        };

        return [
            'risk_level' => $riskLevel,
            'danger_signs' => $dangerSigns->values()->all(),
            'risk_flags' => $riskFlags->values()->all(),
            'warnings' => $warnings,
            'requires_referral_warning' => in_array($riskLevel, [MaternityRiskLevel::HIGH, MaternityRiskLevel::EMERGENCY], true),
        ];
    }
}
