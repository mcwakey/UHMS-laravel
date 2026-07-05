<?php

namespace App\Services\Maternity;

use App\Enums\MaternityRiskLevel;
use App\Enums\NewbornBreathingStatus;
use App\Enums\NewbornDangerSign;
use App\Enums\NewbornFeedingStatus;
use App\Enums\NewbornRiskFlag;
use App\Models\NewbornRecord;

class NewbornRiskAssessmentService
{
    public function assess(array|NewbornRecord $payload): array
    {
        $data = $payload instanceof NewbornRecord ? $payload->getAttributes() + [
            'risk_flags' => $payload->risk_flags ?? [],
            'danger_signs' => $payload->danger_signs ?? [],
        ] : $payload;

        $riskFlags = collect($data['risk_flags'] ?? [])->filter()->values();
        $dangerSigns = collect($data['danger_signs'] ?? [])->filter()->values();
        $warnings = [];

        if (($data['birth_weight_kg'] ?? null) !== null && (float) $data['birth_weight_kg'] < 2.5) {
            $riskFlags->push(NewbornRiskFlag::LOW_BIRTH_WEIGHT->value);
            $warnings[] = __('maternity.newborn_warning_low_birth_weight');
        }

        if (($data['apgar_5_min'] ?? null) !== null && (int) $data['apgar_5_min'] < 7) {
            $riskFlags->push(NewbornRiskFlag::POOR_APGAR->value);
            $warnings[] = __('maternity.newborn_warning_poor_apgar');
        }

        if (! empty($data['resuscitation_required'])) {
            $riskFlags->push(NewbornRiskFlag::RESUSCITATION_REQUIRED->value);
            $warnings[] = __('maternity.newborn_warning_resuscitation');
        }

        if (filled($data['congenital_concerns'] ?? null)) {
            $riskFlags->push(NewbornRiskFlag::CONGENITAL_CONCERN->value);
            $warnings[] = __('maternity.newborn_warning_congenital');
        }

        $temperature = $data['temperature'] ?? null;
        if ($temperature !== null && ((float) $temperature < 36.5 || (float) $temperature >= 37.5)) {
            $riskFlags->push(NewbornRiskFlag::TEMPERATURE_INSTABILITY->value);
            $dangerSigns->push((float) $temperature < 36.5 ? NewbornDangerSign::HYPOTHERMIA->value : NewbornDangerSign::FEVER->value);
            $warnings[] = __('maternity.newborn_warning_temperature');
        }

        if (($data['feeding_status'] ?? null) === NewbornFeedingStatus::DIFFICULTY->value) {
            $riskFlags->push(NewbornRiskFlag::FEEDING_DIFFICULTY->value);
            $dangerSigns->push(NewbornDangerSign::POOR_FEEDING->value);
            $warnings[] = __('maternity.newborn_warning_feeding');
        }

        if (in_array($data['breathing_status'] ?? null, [
            NewbornBreathingStatus::FAST_BREATHING->value,
            NewbornBreathingStatus::DIFFICULTY->value,
            NewbornBreathingStatus::APNOEA->value,
            NewbornBreathingStatus::SUPPORTED->value,
        ], true)) {
            $dangerSigns->push(NewbornDangerSign::DIFFICULTY_BREATHING->value);
            $warnings[] = __('maternity.newborn_warning_breathing');
        }

        $riskFlags = $riskFlags->unique()->values();
        $dangerSigns = $dangerSigns->unique()->values();
        $riskLevel = match (true) {
            $dangerSigns->contains(fn ($sign) => in_array($sign, [
                NewbornDangerSign::DIFFICULTY_BREATHING->value,
                NewbornDangerSign::CONVULSIONS->value,
                NewbornDangerSign::CYANOSIS->value,
                NewbornDangerSign::HYPOTHERMIA->value,
            ], true)) => MaternityRiskLevel::EMERGENCY,
            $dangerSigns->isNotEmpty() || $riskFlags->count() >= 2 => MaternityRiskLevel::HIGH,
            $riskFlags->isNotEmpty() => MaternityRiskLevel::MODERATE,
            default => MaternityRiskLevel::LOW,
        };

        return [
            'risk_level' => $riskLevel,
            'risk_flags' => $riskFlags->all(),
            'danger_signs' => $dangerSigns->all(),
            'warnings' => array_values(array_unique($warnings)),
            'requires_review' => in_array($riskLevel, [MaternityRiskLevel::HIGH, MaternityRiskLevel::EMERGENCY], true),
        ];
    }
}
