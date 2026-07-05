<?php

namespace App\Services\Maternity;

use App\Enums\BleedingStatus;
use App\Enums\BreastfeedingStatus;
use App\Enums\JaundiceStatus;
use App\Enums\MaternityRiskLevel;
use App\Enums\NewbornBreathingStatus;
use App\Enums\NewbornCordStatus;
use App\Enums\NewbornFeedingStatus;
use App\Enums\PostnatalMotherDangerSign;
use App\Enums\PostnatalMotherRiskFlag;
use App\Enums\PostnatalNewbornDangerSign;
use App\Enums\PostnatalNewbornRiskFlag;
use App\Enums\UterusCondition;
use App\Enums\WoundCondition;

class PostnatalRiskAssessmentService
{
    public function assessMother(array $data): array
    {
        $dangerSigns = $this->values($data['danger_signs'] ?? []);
        $riskFlags = $this->values($data['risk_flags'] ?? []);
        $warnings = [];

        $systolic = (int) ($data['blood_pressure_systolic'] ?? 0);
        $diastolic = (int) ($data['blood_pressure_diastolic'] ?? 0);
        $temperature = $data['temperature'] ?? null;
        $painScore = $data['pain_score'] ?? null;

        if (($systolic >= 140 || $diastolic >= 90) && ! in_array(PostnatalMotherRiskFlag::HYPERTENSIVE_DISORDER->value, $riskFlags, true)) {
            $riskFlags[] = PostnatalMotherRiskFlag::HYPERTENSIVE_DISORDER->value;
            $warnings[] = __('maternity.postnatal_warning_high_bp');
        }

        if ($temperature !== null && (float) $temperature >= 38 && ! in_array(PostnatalMotherDangerSign::FEVER->value, $dangerSigns, true)) {
            $dangerSigns[] = PostnatalMotherDangerSign::FEVER->value;
            $warnings[] = __('maternity.postnatal_warning_mother_fever');
        }

        if (in_array($data['bleeding_status'] ?? null, [BleedingStatus::HEAVY->value, BleedingStatus::CLOTS->value], true)) {
            $dangerSigns[] = PostnatalMotherDangerSign::HEAVY_BLEEDING->value;
            $warnings[] = __('maternity.postnatal_warning_heavy_bleeding');
        }

        if (($data['uterus_condition'] ?? null) === UterusCondition::BOGGY->value) {
            $riskFlags[] = PostnatalMotherRiskFlag::PREVIOUS_PPH->value;
            $warnings[] = __('maternity.postnatal_warning_uterus_review');
        }

        if (($data['wound_condition'] ?? null) === WoundCondition::INFECTED->value) {
            $dangerSigns[] = PostnatalMotherDangerSign::WOUND_INFECTION->value;
            $warnings[] = __('maternity.postnatal_warning_wound_infection');
        }

        if (in_array($data['breastfeeding_status'] ?? null, [BreastfeedingStatus::NEEDS_SUPPORT->value, BreastfeedingStatus::NOT_ESTABLISHED->value], true)) {
            $riskFlags[] = PostnatalMotherRiskFlag::BREASTFEEDING_SUPPORT_NEEDED->value;
            $warnings[] = __('maternity.postnatal_warning_breastfeeding_support');
        }

        if ($painScore !== null && (int) $painScore >= 8) {
            $riskFlags[] = PostnatalMotherRiskFlag::PAIN_UNCONTROLLED->value;
            $warnings[] = __('maternity.postnatal_warning_pain_review');
        }

        $dangerSigns = collect($dangerSigns)->filter()->unique()->values()->all();
        $riskFlags = collect($riskFlags)->filter()->unique()->values()->all();
        $emergencySigns = [
            PostnatalMotherDangerSign::HEAVY_BLEEDING->value,
            PostnatalMotherDangerSign::CONVULSIONS->value,
            PostnatalMotherDangerSign::BREATHLESSNESS->value,
            PostnatalMotherDangerSign::CHEST_PAIN->value,
        ];

        return [
            'danger_signs' => $dangerSigns,
            'risk_flags' => $riskFlags,
            'warnings' => array_values(array_unique($warnings)),
            'risk_level' => collect($dangerSigns)->intersect($emergencySigns)->isNotEmpty()
                ? MaternityRiskLevel::EMERGENCY
                : (count($dangerSigns) > 0 ? MaternityRiskLevel::HIGH : (count($riskFlags) > 0 ? MaternityRiskLevel::MODERATE : MaternityRiskLevel::LOW)),
            'requires_review' => count($dangerSigns) > 0 || count($riskFlags) > 0,
            'referral_recommended' => collect($dangerSigns)->intersect($emergencySigns)->isNotEmpty(),
        ];
    }

    public function assessNewborn(array $data): array
    {
        $dangerSigns = $this->values($data['danger_signs'] ?? []);
        $riskFlags = $this->values($data['risk_flags'] ?? []);
        $warnings = [];

        $temperature = $data['temperature'] ?? null;
        if ($temperature !== null && (float) $temperature < 36.5) {
            $dangerSigns[] = PostnatalNewbornDangerSign::HYPOTHERMIA->value;
            $riskFlags[] = PostnatalNewbornRiskFlag::TEMPERATURE_INSTABILITY->value;
            $warnings[] = __('maternity.postnatal_warning_newborn_temperature');
        } elseif ($temperature !== null && (float) $temperature >= 38) {
            $dangerSigns[] = PostnatalNewbornDangerSign::FEVER->value;
            $riskFlags[] = PostnatalNewbornRiskFlag::TEMPERATURE_INSTABILITY->value;
            $warnings[] = __('maternity.postnatal_warning_newborn_temperature');
        }

        if (($data['weight_kg'] ?? null) !== null && (float) $data['weight_kg'] < 2.5) {
            $riskFlags[] = PostnatalNewbornRiskFlag::LOW_BIRTH_WEIGHT->value;
            $warnings[] = __('maternity.postnatal_warning_low_birth_weight');
        }

        if (($data['feeding_status'] ?? null) === NewbornFeedingStatus::DIFFICULTY->value) {
            $dangerSigns[] = PostnatalNewbornDangerSign::POOR_FEEDING->value;
            $riskFlags[] = PostnatalNewbornRiskFlag::POOR_FEEDING->value;
            $warnings[] = __('maternity.postnatal_warning_newborn_feeding');
        }

        if (in_array($data['breathing_status'] ?? null, [NewbornBreathingStatus::DIFFICULTY->value, NewbornBreathingStatus::APNOEA->value], true)) {
            $dangerSigns[] = PostnatalNewbornDangerSign::DIFFICULTY_BREATHING->value;
            $warnings[] = __('maternity.postnatal_warning_newborn_breathing');
        }

        if (($data['cord_status'] ?? null) === NewbornCordStatus::INFECTED->value) {
            $dangerSigns[] = PostnatalNewbornDangerSign::CORD_INFECTION->value;
            $riskFlags[] = PostnatalNewbornRiskFlag::CORD_CONCERN->value;
            $warnings[] = __('maternity.postnatal_warning_cord_review');
        }

        if (($data['jaundice_status'] ?? null) === JaundiceStatus::SEVERE->value) {
            $dangerSigns[] = PostnatalNewbornDangerSign::JAUNDICE->value;
            $riskFlags[] = PostnatalNewbornRiskFlag::JAUNDICE->value;
            $warnings[] = __('maternity.postnatal_warning_jaundice');
        } elseif (($data['jaundice_status'] ?? null) === JaundiceStatus::MODERATE->value) {
            $riskFlags[] = PostnatalNewbornRiskFlag::JAUNDICE->value;
            $warnings[] = __('maternity.postnatal_warning_jaundice');
        }

        $dangerSigns = collect($dangerSigns)->filter()->unique()->values()->all();
        $riskFlags = collect($riskFlags)->filter()->unique()->values()->all();
        $emergencySigns = [
            PostnatalNewbornDangerSign::DIFFICULTY_BREATHING->value,
            PostnatalNewbornDangerSign::CONVULSIONS->value,
            PostnatalNewbornDangerSign::CYANOSIS->value,
        ];

        return [
            'danger_signs' => $dangerSigns,
            'risk_flags' => $riskFlags,
            'warnings' => array_values(array_unique($warnings)),
            'risk_level' => collect($dangerSigns)->intersect($emergencySigns)->isNotEmpty()
                ? MaternityRiskLevel::EMERGENCY
                : (count($dangerSigns) > 0 ? MaternityRiskLevel::HIGH : (count($riskFlags) > 0 ? MaternityRiskLevel::MODERATE : MaternityRiskLevel::LOW)),
            'requires_review' => count($dangerSigns) > 0 || count($riskFlags) > 0,
            'referral_recommended' => collect($dangerSigns)->intersect($emergencySigns)->isNotEmpty(),
        ];
    }

    private function values(array|string|null $values): array
    {
        if (is_string($values)) {
            $values = [$values];
        }

        return collect($values ?: [])->filter(fn ($value) => filled($value))->unique()->values()->all();
    }
}
