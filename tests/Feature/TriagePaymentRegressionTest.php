<?php

namespace Tests\Feature;

class TriagePaymentRegressionTest extends WorkflowJsonResponsesTest
{
    public function test_triage_payment_path_depends_on_the_central_facade(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Admin/Patients/TriageController.php'));

        $this->assertStringContainsString('PaymentGateService', $source);
        $this->assertStringContainsString('PaymentGateContext::triageRouteCompletion', $source);
        $this->assertStringNotContainsString('BillingPolicyService', $source);
    }
}
