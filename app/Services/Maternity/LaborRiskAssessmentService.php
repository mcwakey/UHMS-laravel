<?php

namespace App\Services\Maternity;

use App\Enums\LaborDangerSign;
use App\Enums\LaborRiskFlag;
use App\Enums\LiquorColour;
use App\Enums\MaternityRiskLevel;
use App\Models\LaborEpisode;
use App\Models\LaborObservation;

class LaborRiskAssessmentService
{
    public function assess(array|LaborEpisode|LaborObservation $payload): array
    {
        $data = $this->data($payload);
        $dangerSigns = collect($data['danger_signs'] ?? [])->filter()->values();
        $riskFlags = collect($data['risk_flags'] ?? [])->filter()->values();
        $warnings = [];

        $systolic = $data['blood_pressure_systolic'] ?? null;
        $diastolic = $data['blood_pressure_diastolic'] ?? null;
        if ($systolic >= 140 || $diastolic >= 90) {
            $riskFlags->push(LaborRiskFlag::HYPERTENSION->value);
            $dangerSigns->push(LaborDangerSign::HIGH_BLOOD_PRESSURE->value);
            $warnings[] = __('maternity.labor_warning_high_bp');
        }

        $fetalHeartRate = $data['fetal_heart_rate'] ?? null;
        if ($fetalHeartRate !== null && ($fetalHeartRate < 110 || $fetalHeartRate > 160)) {
            $riskFlags->push(LaborRiskFlag::ABNORMAL_FETAL_HEART_RATE->value);
            $dangerSigns->push(LaborDangerSign::FETAL_DISTRESS->value);
            $warnings[] = __('maternity.labor_warning_abnormal_fhr');
        }

        if (($data['temperature'] ?? null) !== null && (float) $data['temperature'] >= 38.0) {
            $dangerSigns->push(LaborDangerSign::FEVER->value);
            $warnings[] = __('maternity.labor_warning_fever');
        }

        if (($data['liquor_colour'] ?? null) === LiquorColour::MECONIUM_STAINED->value) {
            $riskFlags->push(LaborRiskFlag::MECONIUM_LIQUOR->value);
            $warnings[] = __('maternity.labor_warning_meconium');
        }

        if (in_array($data['presentation'] ?? null, ['breech', 'transverse', 'oblique'], true)) {
            $riskFlags->push(LaborRiskFlag::BREECH_PRESENTATION->value);
            $warnings[] = __('maternity.labor_warning_abnormal_presentation');
        }

        if ($dangerSigns->isNotEmpty()) {
            $warnings[] = __('maternity.labor_warning_danger_signs');
        }

        $dangerSigns = $dangerSigns->unique()->values();
        $riskFlags = $riskFlags->unique()->values();
        $riskLevel = match (true) {
            $dangerSigns->contains(fn ($sign) => in_array($sign, [
                LaborDangerSign::FETAL_DISTRESS->value,
                LaborDangerSign::SEVERE_BLEEDING->value,
                LaborDangerSign::CONVULSIONS->value,
                LaborDangerSign::OBSTRUCTED_LABOR_SUSPECTED->value,
                LaborDangerSign::RUPTURED_UTERUS_SUSPECTED->value,
            ], true)) => MaternityRiskLevel::EMERGENCY,
            $dangerSigns->isNotEmpty() || $riskFlags->count() >= 2 => MaternityRiskLevel::HIGH,
            $riskFlags->isNotEmpty() => MaternityRiskLevel::MODERATE,
            default => MaternityRiskLevel::LOW,
        };

        return [
            'risk_level' => $riskLevel,
            'danger_signs' => $dangerSigns->all(),
            'risk_flags' => $riskFlags->all(),
            'warnings' => array_values(array_unique($warnings)),
            'requires_escalation_warning' => in_array($riskLevel, [MaternityRiskLevel::HIGH, MaternityRiskLevel::EMERGENCY], true),
        ];
    }

    private function data(array|LaborEpisode|LaborObservation $payload): array
    {
        if ($payload instanceof LaborEpisode) {
            return $payload->getAttributes() + [
                'risk_flags' => [],
                'danger_signs' => [],
            ];
        }

        if ($payload instanceof LaborObservation) {
            return $payload->getAttributes() + [
                'risk_flags' => $payload->risk_flags ?? [],
                'danger_signs' => $payload->danger_signs ?? [],
            ];
        }

        return $payload;
    }
}
