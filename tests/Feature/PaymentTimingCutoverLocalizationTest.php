<?php

namespace Tests\Feature;

use App\Services\Billing\TypedPaymentGateReason;
use Tests\TestCase;

class PaymentTimingCutoverLocalizationTest extends TestCase
{
    public function test_english_and_french_keys_match_recursively(): void
    {
        $en = require lang_path('en/payment_timing_cutover.php');
        $fr = require lang_path('fr/payment_timing_cutover.php');
        $this->assertSame($this->keyPaths($en), $this->keyPaths($fr));
    }

    public function test_every_typed_reason_code_is_localised(): void
    {
        $reasons = require lang_path('en/payment_timing_cutover.php');
        foreach ([
            TypedPaymentGateReason::TYPED_PREPAYMENT_REQUIRED,
            TypedPaymentGateReason::TYPED_PAY_AFTER_SERVICES_ALLOWED,
            TypedPaymentGateReason::TYPED_RUNNING_BILL_ALLOWED,
            TypedPaymentGateReason::TYPED_EMERGENCY_FALLBACK,
            TypedPaymentGateReason::TYPED_FAILURE_LEGACY_FALLBACK,
        ] as $code) {
            $this->assertArrayHasKey($code, $reasons['reasons']);
            $this->assertNotSame($code, TypedPaymentGateReason::message($code));
        }
    }

    /**
     * @param  array<string, mixed>  $array
     * @return array<int, string>
     */
    private function keyPaths(array $array, string $prefix = ''): array
    {
        $paths = [];
        foreach ($array as $key => $value) {
            $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";
            if (is_array($value)) {
                $paths = array_merge($paths, $this->keyPaths($value, $path));
            } else {
                $paths[] = $path;
            }
        }
        sort($paths);

        return $paths;
    }
}
