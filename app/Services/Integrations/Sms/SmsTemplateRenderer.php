<?php

namespace App\Services\Integrations\Sms;

use App\Exceptions\Integrations\IntegrationException;

/**
 * Resolves `{{placeholder}}` tokens in SMS bodies in the SERVICE layer (never in
 * Blade). Only an allow-list of safe, non-clinical placeholders is permitted —
 * unknown tokens are reported so a template can't silently leak or break.
 */
class SmsTemplateRenderer
{
    /** Safe, allowed placeholders. Deliberately excludes diagnosis/lab/clinical data. */
    public const ALLOWED = [
        'patient_name', 'invoice_number', 'amount', 'currency', 'payment_link',
        'appointment_date', 'appointment_time', 'queue_number', 'hospital_name',
        'receipt_number',
    ];

    public function render(string $body, array $data, bool $strict = true): string
    {
        $unknown = $this->unknownPlaceholders($body);
        if ($strict && $unknown !== []) {
            throw new IntegrationException(
                'Unknown placeholder(s): ' . implode(', ', $unknown),
                'integrations.errors.unknown_placeholders',
                ['list' => implode(', ', $unknown)],
            );
        }

        return preg_replace_callback('/\{\{\s*([a-zA-Z_]+)\s*\}\}/', function ($m) use ($data) {
            $key = $m[1];
            if (! in_array($key, self::ALLOWED, true)) {
                return $m[0]; // leave unknown token verbatim in non-strict mode
            }
            return (string) ($data[$key] ?? '');
        }, $body) ?? $body;
    }

    /** @return array<int,string> placeholders found in the body */
    public function placeholders(string $body): array
    {
        preg_match_all('/\{\{\s*([a-zA-Z_]+)\s*\}\}/', $body, $m);
        return array_values(array_unique($m[1] ?? []));
    }

    /** @return array<int,string> placeholders that are not in the allow-list */
    public function unknownPlaceholders(string $body): array
    {
        return array_values(array_diff($this->placeholders($body), self::ALLOWED));
    }

    /** Sample data for the preview screen. */
    public function sampleData(): array
    {
        return [
            'patient_name' => 'Ama Mensah',
            'invoice_number' => 'INV-000123',
            'amount' => '150.00',
            'currency' => 'GHS',
            'payment_link' => url('/pay/sample'),
            'appointment_date' => now()->addDay()->format('Y-m-d'),
            'appointment_time' => '09:30',
            'queue_number' => 'A12',
            'hospital_name' => config('app.name', 'UHMS'),
            'receipt_number' => 'RCPT-000456',
        ];
    }
}
