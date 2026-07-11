<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Services\Billing\PaymentGateOperationConfigurationService;
use App\Services\Billing\PaymentGateOperationRegistry;
use Illuminate\Database\Seeder;

/**
 * Seeds a safe default policy per registered payment-gate operation (Payment
 * Timing Policy Phase 4).
 *
 * Idempotent: it uses firstOrCreate so a re-run never overwrites administrator
 * choices. Defaults mirror the registry (wired hard gates stay compatibility-
 * safe, unwired operations stay disabled) and NEVER activate typed enforcement.
 */
class PaymentGateOperationSettingsSeeder extends Seeder
{
    public function run(): void
    {
        /** @var PaymentGateOperationRegistry $registry */
        $registry = app(PaymentGateOperationRegistry::class);
        $group = PaymentGateOperationConfigurationService::GROUP;
        $seeded = 0;

        foreach ($registry->operations() as $operation => $definition) {
            $payload = [
                'mode' => $definition['default_mode']->value,
                'missing_context' => $definition['missing_billing_context']->value,
                'visit_context_rule' => $definition['visit_context_rule']->value,
                'override_scope_rule' => $definition['override_scope_rule']->value,
                'emergency_exempt' => (bool) ($definition['emergency_exempt'] ?? false),
                'inpatient_exempt' => (bool) ($definition['inpatient_exempt'] ?? false),
                // Phase 4 never activates typed enforcement — always seed disabled.
                'typed_enforcement_eligible' => false,
            ];

            $created = Setting::firstOrCreate(
                ['group' => $group, 'key' => $operation],
                ['value' => json_encode($payload), 'type' => 'json'],
            );

            if ($created->wasRecentlyCreated) {
                $seeded++;
            }
        }

        $this->command?->info("Payment gate operation defaults seeded: {$seeded} new of ".count($registry->operations())." registered (existing values preserved).");
    }
}
