<?php

namespace Tests\Feature;

use App\Data\Billing\PaymentGateContext;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Models\VisitConsultationRoute;
use App\Models\VisitConsultationRouteService;
use App\Services\ActivityLogService;
use App\Services\Billing\BillingPolicyDecision;
use App\Services\Billing\BillingPolicyService;
use App\Services\Billing\PaymentGateService;
use App\Services\ConsultationNextPatientService;
use App\Services\ConsultationRouteService;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class ConsultationPaymentReadinessTest extends TestCase
{
    public function test_first_blocker_and_readiness_shape_are_preserved_through_facade(): void
    {
        $first = (new VisitConsultationRouteService)->setRelation('invoiceItem', new InvoiceItem);
        $second = (new VisitConsultationRouteService)->setRelation('invoiceItem', new InvoiceItem);
        $route = (new VisitConsultationRoute(['department_id' => 8]))
            ->setRelation('routeServices', collect([$first, $second]));
        $user = new User;
        $gate = Mockery::mock(PaymentGateService::class);
        $gate->shouldReceive('policyFor')->once()->withArgs(fn ($item, $actor, $context) => $item === $first->invoiceItem
            && $actor === $user
            && $context instanceof PaymentGateContext
            && $context->operation === 'consultation.next_patient.readiness'
        )->andReturn(BillingPolicyDecision::block(
            BillingPolicyService::MODE_STRICT_PAY_BEFORE_SERVICE,
            'ITEM_UNPAID',
            'First consultation blocker.',
        ));
        $service = new ConsultationNextPatientService(
            Mockery::mock(ConsultationRouteService::class),
            $gate,
            Mockery::mock(ActivityLogService::class),
        );

        $method = new ReflectionMethod($service, 'paymentReadiness');
        $result = $method->invoke($service, $route, $user);

        $this->assertSame(['allowed' => false, 'message' => 'First consultation blocker.'], $result);
    }

    public function test_allowed_services_keep_existing_ready_shape(): void
    {
        $routeService = (new VisitConsultationRouteService)->setRelation('invoiceItem', new InvoiceItem);
        $route = (new VisitConsultationRoute)->setRelation('routeServices', collect([$routeService]));
        $gate = Mockery::mock(PaymentGateService::class);
        $gate->shouldReceive('policyFor')->once()->andReturn(BillingPolicyDecision::allow(
            BillingPolicyService::MODE_STRICT_PAY_BEFORE_SERVICE,
            'ITEM_PAID',
            'Paid',
        ));
        $service = new ConsultationNextPatientService(
            Mockery::mock(ConsultationRouteService::class),
            $gate,
            Mockery::mock(ActivityLogService::class),
        );

        $result = (new ReflectionMethod($service, 'paymentReadiness'))->invoke($service, $route, new User);
        $this->assertSame(['allowed' => true, 'message' => __('consultations.payment_ready')], $result);
    }
}
