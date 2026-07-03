<?php

namespace Tests\Feature\Consultations;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ConsultationJavascriptLifecyclePhase2Test extends TestCase
{
    private string $blade;

    private string $consultationMarkup;

    private string $module;

    protected function setUp(): void
    {
        parent::setUp();

        $this->blade = file_get_contents(resource_path('views/consultations/show.blade.php'));
        $partials = collect(glob(resource_path('views/consultations/partials/*.blade.php')) ?: [])
            ->map(fn (string $file): string => file_get_contents($file))
            ->implode("\n");

        $this->consultationMarkup = $this->blade."\n".$partials;
        $this->module = file_get_contents(resource_path('js/Pages/consultation-show.js'));
    }

    public function test_consultation_page_uses_the_js_module_entrypoint(): void
    {
        $vite = file_get_contents(base_path('vite.config.js'));

        $this->assertStringContainsString("@vite('resources/js/Pages/consultation-show.js')", $this->consultationMarkup);
        $this->assertStringContainsString("'resources/js/Pages/consultation-show.js'", $vite);
        $this->assertStringContainsString('window.UHMSConsultation', $this->module);
        $this->assertStringContainsString('function init(', $this->module);
        $this->assertStringContainsString('function destroy(', $this->module);
    }

    public function test_consultation_page_exposes_safe_json_config_instead_of_executable_globals(): void
    {
        $this->assertStringContainsString('id="consultation-page-config"', $this->consultationMarkup);
        $this->assertStringContainsString('type="application/json"', $this->consultationMarkup);
        $this->assertStringContainsString('JSON_HEX_TAG', $this->consultationMarkup);
        $this->assertStringContainsString('currentRouteId', $this->consultationMarkup);
        $this->assertStringNotContainsString('window.consultationI18n', $this->consultationMarkup);
        $this->assertStringNotContainsString('window.currentConsultationRouteId', $this->consultationMarkup);
    }

    public function test_large_inline_consultation_script_was_replaced_by_module_contract(): void
    {
        $this->assertStringNotContainsString('function refreshConsultationSection', $this->consultationMarkup);
        $this->assertStringNotContainsString('function loadProcedureServices', $this->consultationMarkup);
        $this->assertStringNotContainsString('function loadLabReqItems', $this->consultationMarkup);
        $this->assertStringNotContainsString('function preparePrescriptionSubmit', $this->consultationMarkup);
        $this->assertDoesNotMatchRegularExpression('/<script>\s*window\./', $this->consultationMarkup);
    }

    public function test_workflow_controls_no_longer_use_inline_event_handlers(): void
    {
        $this->assertDoesNotMatchRegularExpression('/\s(onclick|onchange|onsubmit)=/', $this->consultationMarkup);
    }

    public function test_department_pickers_use_delegated_data_actions(): void
    {
        $this->assertStringContainsString('data-consultation-action="load-procedure-services"', $this->consultationMarkup);
        $this->assertStringContainsString('data-consultation-action="load-investigation-services"', $this->consultationMarkup);
        $this->assertStringContainsString('data-consultation-action="load-lab-request-items"', $this->consultationMarkup);
    }

    public function test_consultation_forms_use_shared_ajax_and_idempotency_contract(): void
    {
        foreach (['complaints', 'hopc', 'examination', 'diagnoses', 'investigations', 'treatments', 'prescriptions', 'procedures', 'tasks'] as $form) {
            $this->assertStringContainsString('data-consultation-form="'.$form.'"', $this->consultationMarkup);
        }

        $this->assertStringContainsString('data-consultation-form="lab-request"', $this->consultationMarkup);
        $this->assertStringContainsString('data-modal-form="true"', $this->consultationMarkup);
        $this->assertStringContainsString('data-route-context-required="true"', $this->consultationMarkup);
        $this->assertGreaterThanOrEqual(10, substr_count($this->consultationMarkup, '<x-consultation-idempotency-key'));
    }

    public function test_no_duplicate_investigation_department_ids_are_left(): void
    {
        $this->assertSame(1, substr_count($this->consultationMarkup, 'id="investigationDeptSelect"'));
        $this->assertStringContainsString('id="investigationDeptFallbackSelect"', $this->consultationMarkup);
    }

    public function test_shared_helpers_are_present_in_the_module(): void
    {
        foreach (['routeContext', 'ajaxForms', 'modalHelper', 'sectionRefresh', 'selectLoader', 'idempotency'] as $helper) {
            $this->assertStringContainsString($helper, $this->module);
        }

        $this->assertStringContainsString('data-consultation-action', $this->module);
        $this->assertStringContainsString('data-consultation-form', $this->module);
    }

    public function test_view_cache_compiles_with_consultation_page_contract(): void
    {
        $this->assertSame(0, Artisan::call('view:cache'));
        $this->assertSame(0, Artisan::call('view:clear'));
    }
}
