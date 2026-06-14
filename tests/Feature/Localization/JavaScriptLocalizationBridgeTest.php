<?php

namespace Tests\Feature\Localization;

use Tests\TestCase;

/**
 * Phase 16 localisation gate (static checks):
 *  - the global UHMS_I18N bridge is exposed before script.js loads;
 *  - active Blade-embedded JS uses @json(__()) / page-level I18N maps;
 *  - the Phase 15G scanner false-positive rules stay narrow and documented.
 *
 * These are file-content assertions (no DB), so they run fast and guard
 * against silent regressions in the JS localisation strategy.
 */
class JavaScriptLocalizationBridgeTest extends TestCase
{
    private function read(string $relative): string
    {
        $path = base_path($relative);
        $this->assertFileExists($path, "Expected file `{$relative}` to exist.");

        return (string) file_get_contents($path);
    }

    public function test_global_layout_exposes_uhms_i18n_bridge(): void
    {
        $layout = $this->read('resources/views/layouts/app.blade.php');

        $this->assertStringContainsString(
            'window.UHMS_I18N',
            $layout,
            'layouts/app.blade.php must expose window.UHMS_I18N for JS localisation.'
        );
    }

    public function test_insurance_modal_script_uses_blade_i18n_map_not_raw_english(): void
    {
        $partial = $this->read('resources/views/patients/partials/insurance-add-modal-scripts.blade.php');

        // Uses the translated i18n map.
        $this->assertStringContainsString('@json(__(', $partial, 'Insurance modal script must build its I18N map from @json(__()).');
        $this->assertStringContainsString('I18N.selectProvider', $partial, 'Insurance modal script should reference the I18N map.');

        // No raw English UI literals left behind.
        foreach ([
            '">Select Provider</option>',
            '">Select Tier</option>',
            '">No tiers available</option>',
            "'Failed to load providers'",
        ] as $raw) {
            $this->assertStringNotContainsString(
                $raw,
                $partial,
                "Insurance modal script still contains the raw English literal `{$raw}`."
            );
        }
    }

    public function test_visit_create_js_strings_are_translated(): void
    {
        $view = $this->read('resources/views/visits/create.blade.php');

        $this->assertStringContainsString("@json(__('visits.add_to_billing'))", $view);
        $this->assertStringContainsString("@json(__('visits.reference_label'))", $view);
    }

    public function test_scanner_false_positive_rules_remain_narrow(): void
    {
        $scanner = $this->read('scripts/localisation-audit.php');

        // Protocol identifiers (Phase 15G) — exact-match known false positives.
        $this->assertStringContainsString("'HL7 v2.x'", $scanner);
        $this->assertStringContainsString("'ASTM E1394'", $scanner);

        // The dormant demo-widget rule must stay scoped to the two vendor bundle files.
        $this->assertStringContainsString("'resources/js/script.js', 'resources/js/doctors.js'", $scanner);

        // Guard against a blanket "all JS is demo" widening: the rule must still
        // be gated on inline HTML-fragment context, not the whole file.
        $this->assertStringContainsString("'<option'", $scanner);
    }

    public function test_dormant_demo_js_bundles_still_exist_for_the_rule_to_apply(): void
    {
        // If these are ever deleted or become route-linked, the scanner rule and
        // this expectation must be revisited (see Phase 15G report §3.3).
        $this->assertFileExists(base_path('resources/js/script.js'));
        $this->assertFileExists(base_path('resources/js/doctors.js'));
    }
}
