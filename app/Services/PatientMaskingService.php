<?php

namespace App\Services;

class PatientMaskingService
{
    public function mask(mixed $value, string $strategy = 'hidden'): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $value = (string) $value;

        return match ($strategy) {
            'none', 'date' => $value,
            'phone' => $this->phone($value),
            'email' => $this->email($value),
            'identifier' => $this->identifier($value),
            default => __('patients.privacy.hidden'),
        };
    }

    public function phone(string $value): string
    {
        $clean = preg_replace('/\s+/', '', $value) ?: $value;
        $length = strlen($clean);

        if ($length <= 6) {
            return substr($clean, 0, 1).str_repeat('*', max($length - 2, 1)).substr($clean, -1);
        }

        return substr($clean, 0, 3).str_repeat('*', max($length - 6, 3)).substr($clean, -3);
    }

    public function email(string $value): string
    {
        if (! str_contains($value, '@')) {
            return $this->identifier($value);
        }

        [$local, $domain] = explode('@', $value, 2);
        $prefix = substr($local, 0, min(strlen($local), 2));

        return $prefix.'****@'.$domain;
    }

    public function identifier(string $value): string
    {
        $length = strlen($value);

        if ($length <= 6) {
            return substr($value, 0, 1).str_repeat('*', max($length - 2, 1)).substr($value, -1);
        }

        return substr($value, 0, 3).str_repeat('*', max($length - 6, 3)).substr($value, -3);
    }
}
