<?php

namespace App\Services\Maternity\Reconciliation;

use Illuminate\Support\Carbon;

/**
 * Phase 14R.6 — side-effect-free parsers for legacy O&G specialty-entry values.
 *
 * Every method is pure: it reads a string and returns a typed result. Nothing
 * here touches the database, and nothing guesses. When a value is not
 * unambiguously parseable the parser says so, and the caller classifies the
 * entry as `conflict_requires_review` rather than inventing a number.
 *
 * Each result carries a confidence:
 *   exact           — the value was already in canonical form
 *   safe_normalised — reformatted without interpretation (e.g. "120 / 80")
 *   unparseable     — a human must decide
 */
class ObgynEntryValueParser
{
    public const CONFIDENCE_EXACT = 'exact';
    public const CONFIDENCE_SAFE_NORMALISED = 'safe_normalised';
    public const CONFIDENCE_UNPARSEABLE = 'unparseable';

    /**
     * Blood pressure → systolic / diastolic integers.
     *
     * Accepts `120/80` and `120 / 80`. Anything with extra narrative
     * ("120/80 sitting", "high") is refused — guessing from free text is
     * exactly the failure mode this phase exists to prevent.
     *
     * @return array{value: ?array{systolic:int, diastolic:int}, confidence: string, warnings: list<string>}
     */
    public function bloodPressure(mixed $raw): array
    {
        $text = trim((string) $raw);

        if ($text === '') {
            return $this->result(null, self::CONFIDENCE_UNPARSEABLE, ['empty_value']);
        }

        if (preg_match('/^(\d{2,3})\s*\/\s*(\d{2,3})$/', $text, $m)) {
            $systolic = (int) $m[1];
            $diastolic = (int) $m[2];

            if ($systolic < 50 || $systolic > 300 || $diastolic < 20 || $diastolic > 200) {
                return $this->result(null, self::CONFIDENCE_UNPARSEABLE, ['implausible_blood_pressure']);
            }

            if ($diastolic >= $systolic) {
                return $this->result(null, self::CONFIDENCE_UNPARSEABLE, ['diastolic_not_below_systolic']);
            }

            return $this->result(
                ['systolic' => $systolic, 'diastolic' => $diastolic],
                $text === "{$systolic}/{$diastolic}" ? self::CONFIDENCE_EXACT : self::CONFIDENCE_SAFE_NORMALISED,
            );
        }

        return $this->result(null, self::CONFIDENCE_UNPARSEABLE, ['unrecognised_blood_pressure_format']);
    }

    /**
     * Fundal height → decimal centimetres.
     *
     * Accepts `32`, `32cm`, `32 cm`, `32.5 cm`. Refuses "32 weeks size",
     * mixed narrative and unknown units: a fundal height in weeks is a
     * different measurement, not a different format.
     *
     * @return array{value: ?float, confidence: string, warnings: list<string>}
     */
    public function fundalHeight(mixed $raw): array
    {
        $text = trim((string) $raw);

        if ($text === '') {
            return $this->result(null, self::CONFIDENCE_UNPARSEABLE, ['empty_value']);
        }

        if (preg_match('/^(\d{1,2}(?:[.,]\d)?)$/', $text, $m)) {
            return $this->result(
                (float) str_replace(',', '.', $m[1]),
                self::CONFIDENCE_EXACT,
            );
        }

        if (preg_match('/^(\d{1,2}(?:[.,]\d)?)\s*(cm|cms|centimetre|centimeter|centimetres|centimeters)$/i', $text, $m)) {
            return $this->result(
                (float) str_replace(',', '.', $m[1]),
                self::CONFIDENCE_SAFE_NORMALISED,
            );
        }

        if (preg_match('/(week|wk|\/40|size)/i', $text)) {
            return $this->result(null, self::CONFIDENCE_UNPARSEABLE, ['fundal_height_expressed_in_weeks']);
        }

        return $this->result(null, self::CONFIDENCE_UNPARSEABLE, ['unrecognised_fundal_height_format']);
    }

    /**
     * Gestational age → total days, for safe comparison.
     *
     * Accepts `32`, `32w`, `32+3`, `32w 3d`, `32 weeks 3 days`.
     *
     * @return array{value: ?array{weeks:int, days:int, total_days:int}, confidence: string, warnings: list<string>}
     */
    public function gestationalAge(mixed $raw): array
    {
        $text = trim(strtolower((string) $raw));

        if ($text === '') {
            return $this->result(null, self::CONFIDENCE_UNPARSEABLE, ['empty_value']);
        }

        $patterns = [
            '/^(\d{1,2})$/' => self::CONFIDENCE_EXACT,
            '/^(\d{1,2})\s*(?:w|wk|wks|week|weeks)$/' => self::CONFIDENCE_SAFE_NORMALISED,
            '/^(\d{1,2})\s*\+\s*(\d)$/' => self::CONFIDENCE_SAFE_NORMALISED,
            '/^(\d{1,2})\s*(?:w|wk|wks|week|weeks)\s*(\d)\s*(?:d|day|days)$/' => self::CONFIDENCE_SAFE_NORMALISED,
        ];

        foreach ($patterns as $pattern => $confidence) {
            if (preg_match($pattern, $text, $m)) {
                $weeks = (int) $m[1];
                $days = isset($m[2]) ? (int) $m[2] : 0;

                if ($weeks > 45 || $days > 6) {
                    return $this->result(null, self::CONFIDENCE_UNPARSEABLE, ['implausible_gestational_age']);
                }

                return $this->result(
                    ['weeks' => $weeks, 'days' => $days, 'total_days' => $weeks * 7 + $days],
                    $confidence,
                );
            }
        }

        return $this->result(null, self::CONFIDENCE_UNPARSEABLE, ['unrecognised_gestational_age_format']);
    }

    /**
     * A canonical date. Deliberately strict: a locale-ambiguous free-text date
     * (03/04/2026 — March or April?) is refused rather than reinterpreted, and
     * a missing year is never inferred.
     *
     * @return array{value: ?string, confidence: string, warnings: list<string>}
     */
    public function date(mixed $raw): array
    {
        if ($raw instanceof Carbon) {
            return $this->result($raw->toDateString(), self::CONFIDENCE_EXACT);
        }

        $text = trim((string) $raw);

        if ($text === '') {
            return $this->result(null, self::CONFIDENCE_UNPARSEABLE, ['empty_value']);
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $text)) {
            try {
                return $this->result(Carbon::parse($text)->toDateString(), self::CONFIDENCE_EXACT);
            } catch (\Throwable) {
                return $this->result(null, self::CONFIDENCE_UNPARSEABLE, ['unparseable_date']);
            }
        }

        if (preg_match('/^\d{1,2}[\/.-]\d{1,2}[\/.-]\d{2,4}$/', $text)) {
            return $this->result(null, self::CONFIDENCE_UNPARSEABLE, ['locale_ambiguous_date']);
        }

        return $this->result(null, self::CONFIDENCE_UNPARSEABLE, ['unrecognised_date_format']);
    }

    /**
     * A plain non-negative integer (gravida, para, abortions, living children).
     *
     * @return array{value: ?int, confidence: string, warnings: list<string>}
     */
    public function count(mixed $raw): array
    {
        if (is_int($raw)) {
            return $this->result($raw >= 0 ? $raw : null, $raw >= 0 ? self::CONFIDENCE_EXACT : self::CONFIDENCE_UNPARSEABLE);
        }

        $text = trim((string) $raw);

        if ($text === '') {
            return $this->result(null, self::CONFIDENCE_UNPARSEABLE, ['empty_value']);
        }

        if (preg_match('/^\d{1,2}$/', $text)) {
            return $this->result((int) $text, self::CONFIDENCE_SAFE_NORMALISED);
        }

        return $this->result(null, self::CONFIDENCE_UNPARSEABLE, ['unrecognised_count_format']);
    }

    /**
     * Map a free value onto a known enum code. Only exact and explicitly
     * declared safe aliases are accepted; anything else requires review.
     *
     * @param  array<string, string>  $aliases  alias => canonical code
     * @param  list<string>  $known
     * @return array{value: ?string, confidence: string, warnings: list<string>}
     */
    public function enum(mixed $raw, array $known, array $aliases = []): array
    {
        $text = trim(strtolower((string) $raw));

        if ($text === '') {
            return $this->result(null, self::CONFIDENCE_UNPARSEABLE, ['empty_value']);
        }

        if (in_array($text, $known, true)) {
            return $this->result($text, self::CONFIDENCE_EXACT);
        }

        if (isset($aliases[$text]) && in_array($aliases[$text], $known, true)) {
            return $this->result($aliases[$text], self::CONFIDENCE_SAFE_NORMALISED);
        }

        return $this->result(null, self::CONFIDENCE_UNPARSEABLE, ['unknown_enum_value']);
    }

    /**
     * Compare two gestational ages.
     *
     * A scan-derived age is NEVER replaced by an LMP-derived one: when the
     * profile is dated by ultrasound or assisted reproduction, a differing
     * consultation value is reported as a conflict, not as a correction.
     *
     * @return array{matches: bool, difference_days: ?int, warnings: list<string>}
     */
    public function compareGestationalAge(
        ?array $consultationAge,
        ?int $profileWeeks,
        ?int $profileDays,
        ?string $profileDatingMethod = null,
        int $toleranceDays = 7,
    ): array {
        if (! $consultationAge || $profileWeeks === null) {
            return ['matches' => false, 'difference_days' => null, 'warnings' => ['gestational_age_incomparable']];
        }

        $profileTotal = $profileWeeks * 7 + (int) $profileDays;
        $difference = abs($consultationAge['total_days'] - $profileTotal);

        $scanDated = in_array($profileDatingMethod, [
            'early_ultrasound', 'late_ultrasound', 'assisted_reproduction',
        ], true);

        if ($difference === 0) {
            return ['matches' => true, 'difference_days' => 0, 'warnings' => []];
        }

        if ($scanDated) {
            return [
                'matches' => false,
                'difference_days' => $difference,
                'warnings' => ['scan_dated_profile_not_overridden'],
            ];
        }

        return [
            'matches' => $difference <= $toleranceDays,
            'difference_days' => $difference,
            'warnings' => $difference <= $toleranceDays ? [] : ['gestational_age_material_difference'],
        ];
    }

    /**
     * @param  list<string>  $warnings
     * @return array{value: mixed, confidence: string, warnings: list<string>}
     */
    private function result(mixed $value, string $confidence, array $warnings = []): array
    {
        return ['value' => $value, 'confidence' => $confidence, 'warnings' => $warnings];
    }
}
