<?php

namespace Tests\Feature\Consultations;

use App\Http\Controllers\Doctor\Consultations\ConsultationClinicalEntryController;
use App\Http\Controllers\Doctor\Consultations\ConsultationOrderController;
use App\Http\Controllers\Doctor\Consultations\ConsultationPlanningController;
use App\Http\Controllers\Doctor\Consultations\ConsultationPrescriptionController;
use App\Http\Controllers\Doctor\Consultations\ConsultationSessionController;
use App\Http\Controllers\Doctor\Consultations\ConsultationWorkspaceController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ConsultationStructurePhase3Test extends TestCase
{
    private string $show;

    protected function setUp(): void
    {
        parent::setUp();

        $this->show = file_get_contents(resource_path('views/consultations/show.blade.php'));
    }

    public function test_consultation_show_is_now_composed_from_structural_partials(): void
    {
        foreach ([
            'session-context',
            'workflow-sidebar',
            'right-panel',
            'modals',
            'page-config',
        ] as $partial) {
            $this->assertFileExists(resource_path("views/consultations/partials/{$partial}.blade.php"));
            $this->assertStringContainsString("consultations.partials.{$partial}", $this->show);
        }

        $this->assertLessThan(2000, substr_count($this->show, PHP_EOL));
    }

    public function test_js_contract_survived_the_blade_split(): void
    {
        $pageConfig = file_get_contents(resource_path('views/consultations/partials/page-config.blade.php'));

        $this->assertStringContainsString('id="consultation-page-config"', $pageConfig);
        $this->assertStringContainsString("@vite('resources/js/Pages/consultation-show.js')", $pageConfig);
        $this->assertStringContainsString('currentRouteId', $pageConfig);
        $this->assertStringContainsString('data-consultation-form="complaints"', $this->show);
        $this->assertStringContainsString('data-consultation-action="load-procedure-services"', $this->show);
    }

    public function test_no_inline_workflow_handlers_exist_in_show_or_partials(): void
    {
        $files = array_merge(
            [resource_path('views/consultations/show.blade.php')],
            glob(resource_path('views/consultations/partials/*.blade.php')) ?: [],
        );

        foreach ($files as $file) {
            $contents = file_get_contents($file);
            $this->assertDoesNotMatchRegularExpression('/\s(onclick|onchange|onsubmit)=/', $contents, $file);
            $this->assertStringNotContainsString('window.consultationI18n', $contents, $file);
        }
    }

    public function test_consultation_route_names_remain_registered(): void
    {
        foreach ($this->expectedRoutes() as $name => $controller) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route, "Missing route: {$name}");
            $this->assertSame($controller.'@'.$route->getActionMethod(), $route->getActionName());
        }
    }

    public function test_mutation_routes_keep_permission_middleware(): void
    {
        foreach ($this->mutationRoutes() as $name) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route, "Missing route: {$name}");
            $middleware = implode('|', $route->gatherMiddleware());
            $this->assertStringContainsString('can:', $middleware, "Missing can middleware: {$name}");
        }
    }

    public function test_guard_and_idempotency_remain_central_to_consultation_actions(): void
    {
        $base = file_get_contents(app_path('Http/Controllers/Doctor/Consultations/ConsultationWorkflowController.php'));
        $guard = file_get_contents(app_path('Services/Consultation/ConsultationActionGuard.php'));
        $idempotency = file_get_contents(app_path('Services/Consultation/ConsultationIdempotencyService.php'));

        $this->assertStringContainsString('ConsultationActionGuard', $base);
        $this->assertStringContainsString('ConsultationIdempotencyService', $base);
        $this->assertStringContainsString('assertEditableRoute', $guard);
        $this->assertStringContainsString('CONSULTATION_IDEMPOTENCY_REPLAYED', $idempotency);
    }

    public function test_view_cache_compiles_after_structure_split(): void
    {
        $this->assertSame(0, Artisan::call('view:cache'));
        $this->assertSame(0, Artisan::call('view:clear'));
    }

    /**
     * @return array<string, class-string>
     */
    private function expectedRoutes(): array
    {
        return [
            'admin.consultations.index' => ConsultationWorkspaceController::class,
            'admin.consultations.show' => ConsultationWorkspaceController::class,
            'admin.consultations.routes.show' => ConsultationWorkspaceController::class,
            'admin.consultations.summary-fragment' => ConsultationWorkspaceController::class,
            'admin.consultations.complaints.store' => ConsultationClinicalEntryController::class,
            'admin.consultations.diagnoses.store' => ConsultationClinicalEntryController::class,
            'admin.consultations.investigations.store' => ConsultationOrderController::class,
            'admin.consultations.lab-request.store' => ConsultationOrderController::class,
            'admin.consultations.procedures.store' => ConsultationOrderController::class,
            'admin.consultations.prescriptions.store' => ConsultationPrescriptionController::class,
            'admin.consultations.refer' => ConsultationPlanningController::class,
            'admin.consultations.routes.complete' => ConsultationSessionController::class,
        ];
    }

    /**
     * @return list<string>
     */
    private function mutationRoutes(): array
    {
        return [
            'admin.consultations.transition',
            'admin.consultations.start',
            'admin.consultations.routes.store',
            'admin.consultations.routes.activate',
            'admin.consultations.routes.complete',
            'admin.consultations.routes.cancel',
            'admin.consultations.complaints.store',
            'admin.consultations.diagnoses.store',
            'admin.consultations.investigations.store',
            'admin.consultations.treatments.store',
            'admin.consultations.prescriptions.store',
            'admin.consultations.procedures.store',
            'admin.consultations.lab-request.store',
            'admin.consultations.tasks.store',
        ];
    }
}
