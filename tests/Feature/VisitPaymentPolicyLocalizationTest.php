<?php

namespace Tests\Feature;

use Tests\TestCase;

class VisitPaymentPolicyLocalizationTest extends TestCase
{
    public function test_english_and_french_keys_match_recursively(): void
    {
        $en = require lang_path('en/visit_payment_policy.php');
        $fr = require lang_path('fr/visit_payment_policy.php');

        $this->assertSame($this->keyPaths($en), $this->keyPaths($fr));
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
