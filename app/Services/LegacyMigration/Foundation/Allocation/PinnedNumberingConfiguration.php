<?php

namespace App\Services\LegacyMigration\Foundation\Allocation;

use DateTimeImmutable;
use DateTimeZone;
use JsonException;

final readonly class PinnedNumberingConfiguration
{
    /** @param list<string> $supportedPlaceholders */
    public function __construct(
        public string $prefix,
        public string $pattern,
        public int $sequenceWidth,
        public NumberingResetPeriod $resetPeriod,
        public string $timezone,
        public string $periodKey,
        public array $supportedPlaceholders = ['PREFIX', 'SEQUENCE', 'YEAR', 'YY', 'MONTH', 'DAY'],
    ) {
        if ($prefix === '' || $pattern === '' || $sequenceWidth < 1 || $sequenceWidth > 18 || $periodKey === '') {
            throw AllocationException::failClosed('PATIENT-NUM-INVALID-CONFIG');
        }

        try {
            new DateTimeZone($timezone);
        } catch (\Throwable) {
            throw AllocationException::failClosed('PATIENT-NUM-INVALID-TIMEZONE');
        }

        if (count(array_keys($supportedPlaceholders, 'SEQUENCE', true)) !== 1
            || substr_count($pattern, '{SEQUENCE}') !== 1) {
            throw AllocationException::failClosed('PATIENT-NUM-NONINJECTIVE-PATTERN');
        }

        preg_match_all('/\{([A-Z_]+)\}/', $pattern, $matches);
        $used = array_values(array_unique($matches[1] ?? []));
        if (array_diff($used, $supportedPlaceholders) !== []) {
            throw AllocationException::failClosed('PATIENT-NUM-UNSUPPORTED-PLACEHOLDER');
        }
    }

    public function fingerprint(): string
    {
        try {
            $payload = json_encode([
                'pattern' => $this->pattern,
                'period_key' => $this->periodKey,
                'prefix' => $this->prefix,
                'reset_period' => $this->resetPeriod->value,
                'sequence_width' => $this->sequenceWidth,
                'supported_placeholders' => $this->supportedPlaceholders,
                'timezone' => $this->timezone,
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (JsonException) {
            throw AllocationException::failClosed('PATIENT-NUM-CONFIG-FINGERPRINT');
        }

        return hash('sha256', $payload);
    }

    public function coordinateToken(): string
    {
        return hash('sha256', "patient-number-coordinate\0".$this->fingerprint());
    }

    public function format(int $ordinal): string
    {
        if ($ordinal < 1 || strlen((string) $ordinal) > $this->sequenceWidth) {
            throw AllocationException::failClosed('PATIENT-NUM-SEQUENCE-OVERFLOW');
        }

        $periodDate = $this->periodDate();
        $values = [
            '{PREFIX}' => $this->prefix,
            '{SEQUENCE}' => str_pad((string) $ordinal, $this->sequenceWidth, '0', STR_PAD_LEFT),
            '{YEAR}' => $periodDate->format('Y'),
            '{YY}' => $periodDate->format('y'),
            '{MONTH}' => $periodDate->format('m'),
            '{DAY}' => $periodDate->format('d'),
        ];

        return strtr($this->pattern, $values);
    }

    private function periodDate(): DateTimeImmutable
    {
        $format = match ($this->resetPeriod) {
            NumberingResetPeriod::Daily => '!Y-m-d',
            NumberingResetPeriod::Monthly => '!Y-m',
            NumberingResetPeriod::Yearly => '!Y',
            NumberingResetPeriod::Never => null,
        };

        if ($format === null) {
            return (new DateTimeImmutable('@0'))->setTimezone(new DateTimeZone($this->timezone));
        }

        $date = DateTimeImmutable::createFromFormat($format, $this->periodKey, new DateTimeZone($this->timezone));
        if ($date === false || $date->format(ltrim($format, '!')) !== $this->periodKey) {
            throw AllocationException::failClosed('PATIENT-NUM-INVALID-PERIOD');
        }

        return $date;
    }
}
