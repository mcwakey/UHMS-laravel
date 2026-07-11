<?php

namespace Database\Seeders;

use App\Enums\VisitPaymentTimingPolicy;
use App\Enums\VisitType;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class PaymentTimingSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'enabled' => ['0', 'boolean'],
            'default_policy' => [VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE->value, 'string'],
            'outpatient_policy' => [VisitPaymentTimingPolicy::INHERIT->value, 'string'],
            'inpatient_policy' => [VisitPaymentTimingPolicy::RUNNING_BILL->value, 'string'],
            'emergency_policy' => [VisitPaymentTimingPolicy::RUNNING_BILL->value, 'string'],
            'emergency_never_block_stabilisation' => ['1', 'boolean'],
            'require_settlement_for_pay_after_services' => ['1', 'boolean'],
            'require_settlement_for_running_bill' => ['1', 'boolean'],
            'allow_outstanding_balance_override' => ['1', 'boolean'],
        ];

        // Keep this assertion close to the seed vocabulary so adding a visit
        // type cannot silently omit its configurable default.
        foreach (VisitType::cases() as $visitType) {
            if (! array_key_exists("{$visitType->value}_policy", $settings)) {
                $settings["{$visitType->value}_policy"] = [VisitPaymentTimingPolicy::INHERIT->value, 'string'];
            }
        }

        foreach ($settings as $key => [$value, $type]) {
            Setting::firstOrCreate(
                ['group' => 'payment_timing', 'key' => $key],
                ['value' => $value, 'type' => $type],
            );
        }
    }
}
