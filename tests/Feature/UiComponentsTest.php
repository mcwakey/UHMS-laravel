<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Guards the shared UI governance layer: the <x-status-badge> component must
 * map a workflow status to the configured Bootstrap variant consistently, and
 * always render readable text (never colour-only).
 */
class UiComponentsTest extends TestCase
{
    private function render(string $template, array $data = []): string
    {
        return trim(Blade::render($template, $data));
    }

    public function test_status_badge_maps_domain_status_to_configured_variant(): void
    {
        $html = $this->render('<x-status-badge status="AVAILABLE" domain="blood_unit" />');

        $this->assertStringContainsString('badge', $html);
        $this->assertStringContainsString('bg-success', $html);   // AVAILABLE → success
        $this->assertStringContainsString('Available', $html);    // text present (accessibility)
    }

    public function test_status_badge_adds_dark_text_for_low_contrast_variants(): void
    {
        $html = $this->render('<x-status-badge status="HELD" domain="mar" />');

        $this->assertStringContainsString('bg-warning', $html);
        $this->assertStringContainsString('text-dark', $html);    // warning needs dark text
    }

    public function test_status_badge_soft_style_uses_subtle_class(): void
    {
        $html = $this->render('<x-status-badge status="GIVEN" domain="mar" soft />');

        $this->assertStringContainsString('badge-soft-success', $html);
        $this->assertStringNotContainsString('bg-success', $html); // solid style suppressed
    }

    public function test_status_badge_uses_backed_enum_colour_and_label(): void
    {
        // InvoiceStatus::PENDING => color 'warning', label 'Pending'
        $html = $this->render(
            '<x-status-badge :status="$s" />',
            ['s' => \App\Enums\InvoiceStatus::PENDING]
        );

        $this->assertStringContainsString('bg-warning', $html);
        $this->assertStringContainsString('Pending', $html);
    }

    public function test_status_badge_same_status_is_consistent_across_callers(): void
    {
        $a = $this->render('<x-status-badge status="PAID" domain="invoice" />');
        $b = $this->render('<x-status-badge status="paid" domain="invoice" />'); // case-insensitive

        $this->assertStringContainsString('bg-success', $a);
        $this->assertStringContainsString('bg-success', $b);
    }

    public function test_priority_domain_resolves_from_top_level_map(): void
    {
        $this->assertStringContainsString('bg-danger', $this->render('<x-status-badge status="EMERGENCY" domain="priority" />'));
        $this->assertStringContainsString('bg-secondary', $this->render('<x-status-badge status="ROUTINE" domain="priority" />'));
    }

    public function test_triage_colours_are_clinically_fixed(): void
    {
        $this->assertStringContainsString('bg-danger', $this->render('<x-status-badge status="RED" domain="triage" />'));
        $this->assertStringContainsString('bg-success', $this->render('<x-status-badge status="GREEN" domain="triage" />'));
        $this->assertStringContainsString('bg-dark', $this->render('<x-status-badge status="BLACK" domain="triage" />'));
    }

    public function test_unknown_status_falls_back_safely(): void
    {
        $html = $this->render('<x-status-badge status="WHATEVER_NEW_STATE" />');

        $this->assertStringContainsString('bg-secondary', $html);          // fallback variant
        $this->assertStringContainsString('Whatever New State', $html);    // humanised text
    }

    public function test_label_override_and_custom_classes_are_respected(): void
    {
        $html = $this->render('<x-status-badge status="PAID" domain="invoice" label="Settled" class="ms-2" />');

        $this->assertStringContainsString('Settled', $html);
        $this->assertStringContainsString('ms-2', $html);
        $this->assertStringNotContainsString('Paid', $html);
    }

    public function test_page_header_renders_title_description_and_actions(): void
    {
        $html = $this->render(<<<'BLADE'
            <x-page-header title="Patients" description="Manage folders" icon="ti-users">
                <x-slot:actions><a href="#" class="btn btn-primary">New Patient</a></x-slot:actions>
            </x-page-header>
        BLADE);

        $this->assertStringContainsString('Patients', $html);
        $this->assertStringContainsString('Manage folders', $html);
        $this->assertStringContainsString('uhms-page-header', $html);
        $this->assertStringContainsString('New Patient', $html);
        $this->assertStringContainsString('ti-users', $html);
    }

    public function test_page_header_renders_inline_title_slot(): void
    {
        $html = $this->render(<<<'BLADE'
            <x-page-header title="Patients" icon="ti-users">
                <span class="badge bg-secondary">Total: 42</span>
            </x-page-header>
        BLADE);

        $this->assertStringContainsString('Patients', $html);
        $this->assertStringContainsString('Total: 42', $html); // inline default slot rendered next to title
    }

    public function test_empty_state_renders_message_and_action(): void
    {
        $html = $this->render(<<<'BLADE'
            <x-empty-state title="Nothing here" message="No emergency cases are currently active.">
                <x-slot:action><a href="#" class="btn btn-sm btn-primary">Create</a></x-slot:action>
            </x-empty-state>
        BLADE);

        $this->assertStringContainsString('No emergency cases are currently active.', $html);
        $this->assertStringContainsString('Nothing here', $html);
        $this->assertStringContainsString('Create', $html);
    }
}
