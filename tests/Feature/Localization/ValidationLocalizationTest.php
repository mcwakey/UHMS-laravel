<?php

namespace Tests\Feature\Localization;

use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Phase 16 localisation gate: French validation errors must use the French
 * field attribute names defined in lang/fr/validation.php (the `attributes`
 * array), covering representative auth/patient/visit/consultation/billing/
 * stock/emergency fields.
 */
class ValidationLocalizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('fr');
    }

    /**
     * Build a required-rule validation error for a single field and return the
     * first message, so we can assert the localised attribute is interpolated.
     */
    private function requiredMessageFor(string $field): string
    {
        $validator = Validator::make([], [$field => 'required']);

        $this->assertTrue($validator->fails(), "Expected `{$field}` to fail required validation.");

        return (string) $validator->errors()->first($field);
    }

    public function test_fr_attributes_array_has_expected_french_field_names(): void
    {
        $attributes = trans('validation.attributes', [], 'fr');

        $this->assertIsArray($attributes, 'lang/fr/validation.php must define an attributes array.');

        $expected = [
            // auth / profile
            'first_name' => 'prénom',
            'email' => 'adresse e-mail',
            'password' => 'mot de passe',
            'current_password' => 'mot de passe actuel',
            // consultation / clinical
            'blood_pressure' => 'tension artérielle',
            'respiratory_rate' => 'fréquence respiratoire',
            'diagnoses' => 'diagnostics',
            // stock / store
            'quantity' => 'quantité',
            'unit_price' => 'prix unitaire',
        ];

        foreach ($expected as $key => $frenchName) {
            $this->assertArrayHasKey($key, $attributes, "Missing FR attribute `{$key}`.");
            $this->assertSame(
                $frenchName,
                $attributes[$key],
                "FR attribute `{$key}` should be `{$frenchName}`, got `{$attributes[$key]}`."
            );
        }
    }

    public function test_required_messages_interpolate_the_french_attribute(): void
    {
        $cases = [
            'first_name' => 'prénom',
            'email' => 'adresse e-mail',
            'quantity' => 'quantité',
            'blood_pressure' => 'tension artérielle',
        ];

        foreach ($cases as $field => $frenchName) {
            $message = $this->requiredMessageFor($field);

            $this->assertStringContainsString(
                $frenchName,
                $message,
                "French validation message for `{$field}` should contain `{$frenchName}`. Got: `{$message}`."
            );
            // Guard against the raw snake_case field leaking into the message.
            $this->assertStringNotContainsString(
                $field,
                $message,
                "French validation message for `{$field}` leaked the raw field name. Got: `{$message}`."
            );
        }
    }
}
