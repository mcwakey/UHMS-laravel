<?php

namespace Tests\Feature;

use App\Data\Billing\PaymentGateContext;
use App\Enums\PaymentGateStage;
use App\Enums\VisitType;
use App\Exceptions\BillingGateException;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\LabRequest;
use App\Models\LabRequestItem;
use App\Models\Visit;
use App\Services\Billing\BillingPolicyService;
use App\Services\Billing\PaymentGateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentGateServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_evaluation_and_legacy_convenience_contracts_are_unchanged(): void
    {
        $item = $this->item(VisitType::OUTPATIENT, false);
        $context = PaymentGateContext::forOperation(PaymentGateStage::COMPLETE, 'test.complete');
        $policy = app(BillingPolicyService::class);
        $gate = app(PaymentGateService::class);

        $expected = $policy->getInvoiceItemPolicy($item, null, $context);
        $actual = $gate->evaluateInvoiceItem($item, $context);
        $this->assertSame($expected->toArray(), $actual->toArray());
        $this->assertFalse($gate->canRenderInvoiceItem($item));

        try {
            $gate->assertCanRenderInvoiceItem($item);
            $this->fail('Expected the existing billing gate exception.');
        } catch (BillingGateException $exception) {
            $this->assertSame('Please settle the bill before this service can be rendered.', $exception->getMessage());
            $this->assertSame('ITEM_UNPAID', $exception->decision?->reason);
        }
    }

    public function test_laboratory_policy_keeps_intrinsic_missing_paid_and_unpaid_results(): void
    {
        $gate = app(PaymentGateService::class);
        $missing = (new LabRequestItem)->setRelation('labRequest', new LabRequest);
        DB::enableQueryLog();
        $this->assertTrue($gate->policyForLabResultEntry($missing)->allowed);
        $this->assertSame('INVOICE_ITEM_MISSING_ALLOWED', $gate->policyForLabResultEntry($missing)->reason);
        $this->assertCount(0, DB::getQueryLog());

        $unpaidItem = $this->item(VisitType::OUTPATIENT, false);
        $requestItem = (new LabRequestItem(['invoice_item_id' => 5]))
            ->setRelation('labRequest', new LabRequest)
            ->setRelation('invoiceItem', $unpaidItem);
        $unpaid = $gate->policyForLabResultEntry($requestItem);
        $this->assertFalse($unpaid->allowed);
        $this->assertSame('Payment required: this investigation must be paid before results can be entered.', $unpaid->message);

        $requestItem->setRelation('invoiceItem', $this->item(VisitType::OUTPATIENT, true));
        $this->assertTrue($gate->policyForLabResultEntry($requestItem)->allowed);
    }

    public function test_pharmacy_paid_only_policy_preserves_existing_emergency_behavior(): void
    {
        $gate = app(PaymentGateService::class);
        $unpaidEmergency = $gate->policyForPaidPharmacyItem($this->item(VisitType::EMERGENCY, false));
        $this->assertFalse($unpaidEmergency->allowed);
        $this->assertSame('This item cannot be dispensed until its bill is settled (paid).', $unpaidEmergency->message);

        $this->assertTrue($gate->policyForPaidPharmacyItem($this->item(VisitType::EMERGENCY, true))->allowed);
    }

    private function item(VisitType $type, bool $paid): InvoiceItem
    {
        $visit = (new Visit(['visit_type' => $type]))->setRelation('billingOverrides', collect());
        $item = new InvoiceItem([
            'patient_payable' => 100,
            'paid_amount' => $paid ? 100 : 0,
            'insurance_covered' => 0,
            'balance' => $paid ? 0 : 100,
            'payment_status' => $paid ? 'paid' : 'unpaid',
        ]);

        return $item
            ->setRelation('visit', $visit)
            ->setRelation('invoice', new Invoice(['balance' => $paid ? 0 : 100, 'adjustment_amount' => 0]));
    }
}
