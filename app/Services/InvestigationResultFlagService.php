<?php

namespace App\Services;

class InvestigationResultFlagService
{
    /**
     * Return normal, low, high, abnormal, or null when the range is not safely parseable.
     */
    public function evaluate(mixed $value, ?string $referenceRange): ?string
    {
        $rawValue = trim((string) $value);
        $range = trim((string) $referenceRange);

        if ($rawValue === '' || $range === '') {
            return null;
        }

        $numericValue = $this->number($rawValue);
        if ($numericValue !== null) {
            $normalized = str_replace(['–', '—', '−'], '-', $range);

            if (preg_match('/^\s*(-?\d+(?:\.\d+)?)\s*(?:-|to)\s*(-?\d+(?:\.\d+)?)\s*$/i', $normalized, $matches)) {
                $min = (float) $matches[1];
                $max = (float) $matches[2];

                return $numericValue < $min ? 'low' : ($numericValue > $max ? 'high' : 'normal');
            }

            if (preg_match('/^\s*(<=|≤|<|>=|≥|>)\s*(-?\d+(?:\.\d+)?)\s*$/u', $range, $matches)) {
                $limit = (float) $matches[2];

                return match ($matches[1]) {
                    '<' => $numericValue < $limit ? 'normal' : 'high',
                    '<=', '≤' => $numericValue <= $limit ? 'normal' : 'high',
                    '>' => $numericValue > $limit ? 'normal' : 'low',
                    '>=', '≥' => $numericValue >= $limit ? 'normal' : 'low',
                };
            }

            if (preg_match('/^\s*(?:up\s+to|max(?:imum)?\.?)\s*(-?\d+(?:\.\d+)?)\s*$/i', $range, $matches)) {
                return $numericValue <= (float) $matches[1] ? 'normal' : 'high';
            }

            if (preg_match('/^\s*(?:at\s+least|min(?:imum)?\.?)\s*(-?\d+(?:\.\d+)?)\s*$/i', $range, $matches)) {
                return $numericValue >= (float) $matches[1] ? 'normal' : 'low';
            }
        }

        if (! preg_match('/\d/', $range)) {
            return mb_strtolower($rawValue) === mb_strtolower($range) ? 'normal' : 'abnormal';
        }

        return null;
    }

    private function number(string $value): ?float
    {
        $normalized = str_replace([',', ' '], '', $value);

        return is_numeric($normalized) ? (float) $normalized : null;
    }
}
