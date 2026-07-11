<?php

namespace App\Models\Concerns;

trait MasksFrontDeskPhone
{
    /**
     * Mask a phone number for lists / reports: keep the first 3 and last 2
     * digits, star the middle. Returns null for empty input.
     */
    public function maskPhone(?string $phone): ?string
    {
        $phone = trim((string) $phone);
        if ($phone === '') {
            return null;
        }
        if (strlen($phone) <= 5) {
            return str_repeat('*', max(0, strlen($phone) - 2)) . substr($phone, -2);
        }

        return substr($phone, 0, 3) . str_repeat('*', strlen($phone) - 5) . substr($phone, -2);
    }
}
