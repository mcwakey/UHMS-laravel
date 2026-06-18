<?php

namespace App\Services\Integrations\Sms;

use App\Models\Setting;

/**
 * Read/write the automatic-SMS-event toggles. Stored in the Setting key/value
 * store (group `integrations_sms`) so admins can manage them; defaults come from
 * config and are ALL disabled — automatic SMS is strictly opt-in.
 */
class SmsEventSettingsService
{
    public const GROUP = 'integrations_sms';

    /** setting key => config fallback key */
    public const TOGGLES = [
        'enable_payment_request_sms' => 'payment_request',
        'enable_receipt_sms' => 'receipt',
        'enable_appointment_reminder_sms' => 'appointment_reminder',
        'enable_queue_sms' => 'queue',
    ];

    public function enabled(string $key): bool
    {
        $fallback = (bool) (config('integrations.sms_events.' . (self::TOGGLES[$key] ?? '')) ?? false);
        return (bool) Setting::getValue(self::GROUP, $key, $fallback);
    }

    /** @return array<string,bool> */
    public function all(): array
    {
        $out = [];
        foreach (array_keys(self::TOGGLES) as $key) {
            $out[$key] = $this->enabled($key);
        }
        return $out;
    }

    public function set(string $key, bool $value): void
    {
        if (! array_key_exists($key, self::TOGGLES)) {
            return;
        }
        Setting::setValue(self::GROUP, $key, $value, 'boolean');
    }

    /** @param array<string,mixed> $values */
    public function setMany(array $values): void
    {
        foreach (array_keys(self::TOGGLES) as $key) {
            $this->set($key, (bool) ($values[$key] ?? false));
        }
    }
}
