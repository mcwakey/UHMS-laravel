<?php

namespace App\Services\Integrations\Sms;

/**
 * Normalises phone numbers to a digits-only international form (no '+').
 *
 * Numbers are NOT assumed to be Ghanaian: the local-prefix rule uses a
 * configurable default country code, and any number already in international
 * form is preserved. Both the original and normalised value are retained by
 * callers so nothing is silently lost.
 */
class SmsPhoneNumberNormalizer
{
    /**
     * @return array{original:string, normalized:string, valid:bool}
     */
    public function normalize(string $raw, ?string $countryCode = null): array
    {
        $countryCode = $countryCode ?: (string) config('integrations.phone.default_country_code', '233');
        $original = trim($raw);

        $cleaned = preg_replace('/[^\d+]/', '', $original) ?? '';
        $hadPlus = str_starts_with($cleaned, '+');
        $digits = ltrim($cleaned, '+');

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);          // 00 international prefix
        } elseif (! $hadPlus && str_starts_with($digits, '0')) {
            $digits = $countryCode . substr($digits, 1); // local 0XXXXXXXXX
        } elseif (! $hadPlus && $digits !== '' && strlen($digits) <= 10 && ! str_starts_with($digits, $countryCode)) {
            $digits = $countryCode . $digits;      // bare local without leading 0
        }

        $min = (int) config('integrations.phone.min_digits', 9);
        $max = (int) config('integrations.phone.max_digits', 15);
        $valid = strlen($digits) >= $min && strlen($digits) <= $max;

        return ['original' => $original, 'normalized' => $digits, 'valid' => $valid];
    }
}
