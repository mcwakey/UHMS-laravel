<?php

namespace App\Services;

use App\Models\MedicationFrequency;
use Illuminate\Support\Str;

class MedicationFrequencyService
{
    public function resolve(?string $rawFrequency): ?MedicationFrequency
    {
        $code = $this->normalizeCode($rawFrequency);

        if (! $code) {
            return null;
        }

        return MedicationFrequency::query()
            ->where('is_active', true)
            ->where('code', $code)
            ->first();
    }

    public function normalizeCode(?string $rawFrequency): ?string
    {
        if (! $rawFrequency) {
            return null;
        }

        $normalized = Str::upper(trim($rawFrequency));
        $normalized = str_replace(['.', '-', '_', ' '], '', $normalized);

        return match (true) {
            in_array($normalized, ['OD', 'QD', 'ONCEDAILY'], true) => 'OD',
            in_array($normalized, ['BD', 'BID', 'TWICEDAILY'], true) => 'BD',
            in_array($normalized, ['TDS', 'TID', 'THREETIMESDAILY'], true) => 'TDS',
            in_array($normalized, ['QID', 'FOURTIMESDAILY'], true) => 'QID',
            in_array($normalized, ['Q6H', '6HOURLY', 'EVERY6HOURS'], true) => 'Q6H',
            in_array($normalized, ['Q8H', '8HOURLY', 'EVERY8HOURS'], true) => 'Q8H',
            in_array($normalized, ['Q12H', '12HOURLY', 'EVERY12HOURS'], true) => 'Q12H',
            in_array($normalized, ['STAT', 'STATIM', 'NOW', 'IMMEDIATE'], true) => 'STAT',
            in_array($normalized, ['PRN', 'ASNEEDED'], true) => 'PRN',
            in_array($normalized, ['SOS'], true) => 'SOS',
            default => $normalized,
        };
    }

    public function parseDuration(?string $duration): array
    {
        $duration = trim((string) $duration);

        if ($duration === '') {
            return [null, null];
        }

        if (preg_match('/(\d+)\s*(day|days|d|week|weeks|w|month|months|m)?/i', $duration, $matches)) {
            $value = (int) $matches[1];
            $unit = Str::lower($matches[2] ?? 'days');

            $unit = match ($unit) {
                'd', 'day', 'days' => 'days',
                'w', 'week', 'weeks' => 'weeks',
                'm', 'month', 'months' => 'months',
                default => 'days',
            };

            return [$value, $unit];
        }

        return [null, null];
    }

    public function durationInDays(?int $value, ?string $unit): ?int
    {
        if (! $value || $value < 1) {
            return null;
        }

        return match ($unit) {
            'weeks' => $value * 7,
            'months' => $value * 30,
            default => $value,
        };
    }

    public function expectedDoses(?MedicationFrequency $frequency, ?int $durationValue, ?string $durationUnit, ?int $quantity = null): int
    {
        if (! $frequency) {
            return max(0, (int) $quantity);
        }

        if ($frequency->is_prn) {
            return max(0, (int) $quantity);
        }

        if ($frequency->is_stat) {
            return 1;
        }

        $days = $this->durationInDays($durationValue, $durationUnit);
        $times = (int) ($frequency->times_per_day ?: ($frequency->interval_hours ? floor(24 / $frequency->interval_hours) : 0));

        if ($days && $times) {
            return $days * $times;
        }

        return max(0, (int) $quantity);
    }

    public function splitDose(?string $dosage): array
    {
        $dosage = trim((string) $dosage);

        if ($dosage === '') {
            return [null, null];
        }

        if (preg_match('/^([0-9]+(?:\.[0-9]+)?\s*[a-zA-Z%]+)(?:\s+.*)?$/', $dosage, $matches)) {
            return [trim($matches[1]), null];
        }

        return [$dosage, null];
    }
}
