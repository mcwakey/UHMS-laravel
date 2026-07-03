<?php

namespace Tests\Feature\Consultations;

use App\Http\Controllers\Doctor\ConsultationController;
use App\Http\Controllers\Doctor\Consultations\ConsultationClinicalEntryController;
use App\Http\Controllers\Doctor\Consultations\ConsultationOrderController;
use App\Http\Controllers\Doctor\Consultations\ConsultationPlanningController;
use App\Http\Controllers\Doctor\Consultations\ConsultationPrescriptionController;
use App\Http\Controllers\Doctor\Consultations\ConsultationSessionController;
use App\Http\Controllers\Doctor\Consultations\ConsultationWorkspaceController;
use App\Http\Requests\Consultations\CancelConsultationRouteRequest;
use App\Http\Requests\Consultations\CompleteConsultationRouteRequest;
use App\Http\Requests\Consultations\StoreConsultationDiagnosisRequest;
use App\Http\Requests\Consultations\StoreConsultationFollowUpRequest;
use App\Http\Requests\Consultations\StoreConsultationLabRequest;
use App\Http\Requests\Consultations\StoreConsultationPrescriptionRequest;
use App\Http\Requests\Consultations\StoreConsultationProcedureRequest;
use App\Http\Requests\Consultations\StoreConsultationReferralRequest;
use App\Http\Requests\Consultations\TransitionConsultationRouteRequest;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

class ConsultationControllerExtractionPhase4Test extends TestCase
{
    public function test_consultation_route_names_still_resolve_to_workflow_controllers(): void
    {
        $expected = [
            'admin.consultations.index' => ConsultationWorkspaceController::class.'@index',
            'admin.consultations.show' => ConsultationWorkspaceController::class.'@show',
            'admin.consultations.summary-fragment' => ConsultationWorkspaceController::class.'@summaryFragment',
            'admin.consultations.prescriptions.store' => ConsultationPrescriptionController::class.'@storePrescription',
            'admin.consultations.lab-request.store' => ConsultationOrderController::class.'@storeLabRequest',
            'admin.consultations.procedures.store' => ConsultationOrderController::class.'@storeProcedureRequest',
            'admin.consultations.complaints.store' => ConsultationClinicalEntryController::class.'@storeComplaint',
            'admin.consultations.hopc.store' => ConsultationClinicalEntryController::class.'@storeHistoryOfPresentingComplaint',
            'admin.consultations.examinations.store' => ConsultationClinicalEntryController::class.'@storeExamination',
            'admin.consultations.diagnoses.store' => ConsultationClinicalEntryController::class.'@storeDiagnosis',
            'admin.consultations.routes.complete' => ConsultationSessionController::class.'@completeRoute',
            'admin.consultations.routes.cancel' => ConsultationSessionController::class.'@cancelRoute',
            'admin.consultations.transition' => ConsultationSessionController::class.'@transitionVisit',
            'admin.consultations.refer' => ConsultationPlanningController::class.'@refer',
            'admin.consultations.routes.follow-up.store' => ConsultationPlanningController::class.'@storeFollowUpAppointment',
        ];

        foreach ($expected as $name => $uses) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route, "Route [{$name}] is registered.");
            $this->assertSame($uses, $route->getActionName(), "Route [{$name}] points to the expected workflow controller.");
        }
    }

    public function test_legacy_doctor_consultation_controller_is_a_compatibility_shell(): void
    {
        $legacy = new ReflectionClass(ConsultationController::class);

        foreach ([
            'storePrescription',
            'storeLabRequest',
            'storeProcedureRequest',
            'storeDiagnosis',
            'completeRoute',
            'cancelRoute',
            'transitionVisit',
            'refer',
        ] as $method) {
            $this->assertFalse($legacy->hasMethod($method), "Legacy controller should not declare [{$method}].");
        }

        $source = file_get_contents(app_path('Http/Controllers/Doctor/ConsultationController.php'));
        $this->assertStringContainsString('Compatibility shell', $source);
        $this->assertStringNotContainsString('ConsultationActionGuard', $source);
        $this->assertStringNotContainsString('ConsultationIdempotencyService', $source);
    }

    public function test_workflow_controllers_receive_their_domain_methods(): void
    {
        $this->assertTrue(method_exists(ConsultationPrescriptionController::class, 'storePrescription'));
        $this->assertTrue(method_exists(ConsultationOrderController::class, 'storeLabRequest'));
        $this->assertTrue(method_exists(ConsultationOrderController::class, 'storeProcedureRequest'));
        $this->assertTrue(method_exists(ConsultationClinicalEntryController::class, 'storeDiagnosis'));
        $this->assertTrue(method_exists(ConsultationSessionController::class, 'completeRoute'));
        $this->assertTrue(method_exists(ConsultationPlanningController::class, 'refer'));
    }

    public function test_high_risk_mutations_use_focused_form_requests(): void
    {
        $expected = [
            [ConsultationPrescriptionController::class, 'storePrescription', StoreConsultationPrescriptionRequest::class],
            [ConsultationOrderController::class, 'storeLabRequest', StoreConsultationLabRequest::class],
            [ConsultationOrderController::class, 'storeProcedureRequest', StoreConsultationProcedureRequest::class],
            [ConsultationClinicalEntryController::class, 'storeDiagnosis', StoreConsultationDiagnosisRequest::class],
            [ConsultationPlanningController::class, 'refer', StoreConsultationReferralRequest::class],
            [ConsultationPlanningController::class, 'storeFollowUpAppointment', StoreConsultationFollowUpRequest::class],
            [ConsultationSessionController::class, 'transitionVisit', TransitionConsultationRouteRequest::class],
            [ConsultationSessionController::class, 'completeRoute', CompleteConsultationRouteRequest::class],
            [ConsultationSessionController::class, 'cancelRoute', CancelConsultationRouteRequest::class],
        ];

        foreach ($expected as [$controller, $method, $request]) {
            $reflection = new ReflectionMethod($controller, $method);
            $firstParameter = $reflection->getParameters()[0] ?? null;

            $this->assertNotNull($firstParameter, "{$controller}@{$method} has a request parameter.");
            $this->assertSame($request, $firstParameter->getType()?->getName(), "{$controller}@{$method} uses {$request}.");
        }
    }

    public function test_guard_and_idempotency_stay_on_the_shared_workflow_boundary(): void
    {
        $base = file_get_contents(app_path('Http/Controllers/Doctor/Consultations/ConsultationWorkflowController.php'));
        $this->assertStringContainsString('ConsultationActionGuard $actionGuard', $base);
        $this->assertStringContainsString('ConsultationIdempotencyService $idempotency', $base);

        foreach ([
            'HandlesConsultationClinicalEntries.php',
            'HandlesConsultationOrders.php',
            'HandlesConsultationPrescriptions.php',
            'HandlesConsultationSessions.php',
        ] as $file) {
            $source = file_get_contents(app_path('Http/Controllers/Doctor/Consultations/Concerns/'.$file));
            $this->assertStringContainsString('consultationMutationContext', $source);
        }
    }
}

