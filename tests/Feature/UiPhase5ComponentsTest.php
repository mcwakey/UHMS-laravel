<?php

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Phase 5 reusable components: stat-card, filter-bar, data-table, action-menu,
 * confirm-form, print-layout. Pure render-level guards (no business logic).
 */
class UiPhase5ComponentsTest extends TestCase
{
    private function render(string $template, array $data = []): string
    {
        return trim(Blade::render($template, $data));
    }

    public function test_stat_card_renders_title_value_icon_and_variant(): void
    {
        $html = $this->render('<x-stat-card title="Today Visits" :value="1234" icon="ti-stethoscope" variant="info" format="number" />');

        $this->assertStringContainsString('Today Visits', $html);
        $this->assertStringContainsString('1,234', $html);              // number formatted
        $this->assertStringContainsString('ti-stethoscope', $html);
        $this->assertStringContainsString('border-info', $html);        // variant border
        $this->assertStringContainsString('card h-100', $html);
    }

    public function test_stat_card_currency_format_and_clickable_route(): void
    {
        $html = $this->render('<x-stat-card title="Collected" :value="2500.5" variant="success" format="currency" route="/admin/reports" />');

        $this->assertStringContainsString('GHS 2,500.50', $html);
        $this->assertStringContainsString('stretched-link', $html);     // whole card clickable
        $this->assertStringContainsString('aria-label', $html);         // accessible link
    }

    public function test_filter_bar_renders_form_apply_and_reset(): void
    {
        $html = $this->render(<<<'BLADE'
            <x-filter-bar action="/admin/patients" reset-url="/admin/patients">
                <div class="col-md-4"><input name="search" class="form-control"></div>
            </x-filter-bar>
        BLADE);

        $this->assertStringContainsString('method="GET"', $html);
        $this->assertStringContainsString('action="/admin/patients"', $html);
        $this->assertStringContainsString('name="search"', $html);      // slot field
        $this->assertStringContainsString('Apply Filters', $html);
        $this->assertStringContainsString('Reset', $html);
        $this->assertStringContainsString('row g-2', $html);
    }

    public function test_data_table_wraps_responsive_table_with_head_and_body(): void
    {
        $html = $this->render(<<<'BLADE'
            <x-data-table>
                <x-slot:head><tr><th>Patient</th></tr></x-slot:head>
                <tr><td>Ama</td></tr>
            </x-data-table>
        BLADE);

        $this->assertStringContainsString('table-responsive', $html);
        $this->assertStringContainsString('table table-hover align-middle', $html);
        $this->assertStringContainsString('<thead', $html);
        $this->assertStringContainsString('Patient', $html);
        $this->assertStringContainsString('Ama', $html);
    }

    public function test_data_table_renders_pagination_when_paginator_has_pages(): void
    {
        $paginator = new LengthAwarePaginator([1, 2], total: 30, perPage: 10, currentPage: 1, options: ['path' => '/admin/patients']);
        $html = $this->render('<x-data-table :paginator="$p"><tr><td>row</td></tr></x-data-table>', ['p' => $paginator]);

        $this->assertStringContainsString('card-footer', $html);
        $this->assertStringContainsString('pagination', $html);
    }

    public function test_action_menu_renders_accessible_dropdown_with_items(): void
    {
        $html = $this->render(<<<'BLADE'
            <x-action-menu>
                <a href="#" class="dropdown-item">View</a>
            </x-action-menu>
        BLADE);

        $this->assertStringContainsString('dropdown-toggle', $html);
        $this->assertStringContainsString('aria-label="Actions"', $html);   // icon trigger accessible
        $this->assertStringContainsString('dropdown-menu dropdown-menu-end', $html);
        $this->assertStringContainsString('>View<', $html);
    }

    public function test_confirm_form_renders_method_spoofing_and_confirm_attributes(): void
    {
        $html = $this->render('<x-confirm-form action="/admin/payments/1/reverse" method="DELETE" button-label="Reverse" confirm-title="Reverse this payment?" require-reason />');

        $this->assertStringContainsString('action="/admin/payments/1/reverse"', $html);
        $this->assertStringContainsString('name="_method"', $html);                 // method spoofing
        $this->assertStringContainsString('value="DELETE"', $html);
        $this->assertStringContainsString('name="_token"', $html);                  // CSRF
        $this->assertStringContainsString('data-title="Reverse this payment?"', $html);
        $this->assertStringContainsString('data-require-reason="1"', $html);
        $this->assertStringContainsString('uhmsConfirmSubmit', $html);
        $this->assertStringContainsString('Reverse', $html);
    }

    public function test_confirm_form_disabled_shows_reason_title(): void
    {
        $html = $this->render('<x-confirm-form action="/x" button-label="Delete" :disabled="true" disabled-reason="Already issued" />');

        $this->assertStringContainsString('disabled', $html);
        $this->assertStringContainsString('title="Already issued"', $html);
    }

    public function test_print_layout_renders_document_shell(): void
    {
        $html = $this->render(<<<'BLADE'
            <x-print-layout title="Invoice" subtitle="INV-001" :signatures="['Prepared by','Authorised by']">
                <p>Body content here</p>
            </x-print-layout>
        BLADE);

        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('Invoice', $html);               // title (uppercased via CSS)
        $this->assertStringContainsString('text-uppercase', $html);
        $this->assertStringContainsString('INV-001', $html);
        $this->assertStringContainsString('Body content here', $html);     // content slot
        $this->assertStringContainsString('Prepared by', $html);
        $this->assertStringContainsString('d-print-none', $html);          // print button hidden when printing
        $this->assertStringContainsString('window.print()', $html);
    }
}
