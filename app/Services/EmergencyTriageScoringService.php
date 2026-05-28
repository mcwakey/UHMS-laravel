<?php

namespace App\Services;

use App\Models\EmergencyCase;
use App\Support\EmergencyTriageResult;

class EmergencyTriageScoringService
{
    private const RANKS = [
        EmergencyCase::TRIAGE_GREEN => 1,
        EmergencyCase::TRIAGE_YELLOW => 2,
        EmergencyCase::TRIAGE_ORANGE => 3,
        EmergencyCase::TRIAGE_RED => 4,
        EmergencyCase::TRIAGE_BLACK => 5,
    ];

    public function calculateFromVitals(array $vitals, array $flags = []): EmergencyTriageResult
    {
        $category = EmergencyCase::TRIAGE_GREEN;
        $score = 0;
        $reasons = [];
        $warnings = [];

        $promote = function (string $newCategory, int $points, string $reason) use (&$category, &$score, &$reasons) {
            if (self::RANKS[$newCategory] > self::RANKS[$category]) {
                $category = $newCategory;
            }
            $score = max($score, $points);
            $reasons[] = $reason;
        };

        if ($this->truthy($flags['dead_on_arrival'] ?? null)) {
            return new EmergencyTriageResult(100, EmergencyCase::TRIAGE_BLACK, ['Dead on arrival / no signs of life.']);
        }

        $spo2 = $this->number($vitals['spo2'] ?? null);
        if ($spo2 === null) $warnings[] = 'SpO2 not recorded.';
        elseif ($spo2 < 90) $promote(EmergencyCase::TRIAGE_RED, 95, 'SpO2 below critical threshold.');
        elseif ($spo2 < 94) $promote(EmergencyCase::TRIAGE_ORANGE, 75, 'SpO2 below urgent threshold.');

        $respiratoryRate = $this->number($vitals['respiratory_rate'] ?? null);
        if ($respiratoryRate === null) $warnings[] = 'Respiratory rate not recorded.';
        elseif ($respiratoryRate < 8 || $respiratoryRate > 30) $promote(EmergencyCase::TRIAGE_RED, 92, 'Respiratory rate is critically abnormal.');
        elseif ($respiratoryRate >= 25) $promote(EmergencyCase::TRIAGE_ORANGE, 72, 'Respiratory rate is very urgent.');
        elseif ($respiratoryRate >= 21) $promote(EmergencyCase::TRIAGE_YELLOW, 50, 'Respiratory rate is abnormal.');

        $systolic = $this->number($vitals['blood_pressure_systolic'] ?? null);
        $diastolic = $this->number($vitals['blood_pressure_diastolic'] ?? null);
        if ($systolic === null) $warnings[] = 'Systolic blood pressure not recorded.';
        elseif ($systolic < 90) $promote(EmergencyCase::TRIAGE_RED, 90, 'Systolic BP suggests shock.');
        elseif ($systolic < 100 || $systolic > 180) $promote(EmergencyCase::TRIAGE_ORANGE, 70, 'Blood pressure is very urgent.');
        elseif ($systolic < 110 || ($diastolic !== null && $diastolic > 110)) $promote(EmergencyCase::TRIAGE_YELLOW, 45, 'Blood pressure is abnormal.');

        $heartRate = $this->number($vitals['heart_rate'] ?? null);
        if ($heartRate === null) $warnings[] = 'Pulse / heart rate not recorded.';
        elseif ($heartRate < 40 || $heartRate > 130) $promote(EmergencyCase::TRIAGE_RED, 88, 'Pulse is critically abnormal.');
        elseif ($heartRate < 50 || $heartRate >= 120) $promote(EmergencyCase::TRIAGE_ORANGE, 68, 'Pulse is very urgent.');
        elseif ($heartRate >= 100) $promote(EmergencyCase::TRIAGE_YELLOW, 42, 'Pulse is abnormal.');

        $temperature = $this->number($vitals['temperature'] ?? null);
        if ($temperature === null) $warnings[] = 'Temperature not recorded.';
        elseif ($temperature < 35 || $temperature >= 39.5) $promote(EmergencyCase::TRIAGE_ORANGE, 65, 'Temperature is very urgent.');
        elseif ($temperature < 36 || $temperature >= 38) $promote(EmergencyCase::TRIAGE_YELLOW, 40, 'Temperature is abnormal.');

        $avpu = strtoupper((string) ($flags['avpu'] ?? ''));
        if ($avpu === '') $warnings[] = 'AVPU consciousness level not recorded.';
        elseif (in_array($avpu, ['P', 'U'], true)) $promote(EmergencyCase::TRIAGE_RED, 94, 'Altered consciousness on AVPU.');
        elseif ($avpu === 'V') $promote(EmergencyCase::TRIAGE_ORANGE, 72, 'Responds to voice only on AVPU.');

        $painScore = $this->number($flags['pain_score'] ?? null);
        if ($painScore === null) $warnings[] = 'Pain score not recorded.';
        elseif ($painScore >= 8) $promote(EmergencyCase::TRIAGE_ORANGE, 66, 'Severe pain score.');
        elseif ($painScore >= 4) $promote(EmergencyCase::TRIAGE_YELLOW, 38, 'Moderate pain score.');

        foreach ($this->dangerSigns($flags) as $sign => $label) {
            if (! $this->truthy($flags[$sign] ?? null) && ! in_array($sign, (array) ($flags['danger_signs'] ?? []), true)) {
                continue;
            }

            match ($sign) {
                'respiratory_distress', 'seizure', 'shock', 'uncontrolled_bleeding' => $promote(EmergencyCase::TRIAGE_RED, 90, $label),
                'trauma', 'bleeding', 'pregnancy' => $promote(EmergencyCase::TRIAGE_ORANGE, 70, $label),
                default => $promote(EmergencyCase::TRIAGE_YELLOW, 45, $label),
            };
        }

        if ($score === 0) {
            $score = 10;
            $reasons[] = 'No urgent danger signs detected from recorded data.';
        }

        return new EmergencyTriageResult($score, $category, array_values(array_unique($reasons)), array_values(array_unique($warnings)));
    }

    public function suggestCategory(EmergencyCase $case): EmergencyTriageResult
    {
        $case->loadMissing('latestVitals');

        return $this->calculateFromVitals([
            'blood_pressure_systolic' => $case->latestVitals?->blood_pressure_systolic,
            'blood_pressure_diastolic' => $case->latestVitals?->blood_pressure_diastolic,
            'heart_rate' => $case->latestVitals?->heart_rate,
            'temperature' => $case->latestVitals?->temperature,
            'respiratory_rate' => $case->latestVitals?->respiratory_rate,
            'spo2' => $case->latestVitals?->spo2,
        ], [
            'avpu' => $case->avpu,
            'pain_score' => $case->pain_score,
            'danger_signs' => $case->danger_signs ?: [],
        ]);
    }

    private function dangerSigns(array $flags): array
    {
        return [
            'respiratory_distress' => 'Respiratory distress present.',
            'seizure' => 'Seizure activity present.',
            'shock' => 'Shock indicators present.',
            'uncontrolled_bleeding' => 'Uncontrolled bleeding present.',
            'trauma' => 'Major trauma flag present.',
            'bleeding' => 'Bleeding flag present.',
            'pregnancy' => 'Pregnancy flag present.',
        ];
    }

    private function number(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function truthy(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === true;
    }
}