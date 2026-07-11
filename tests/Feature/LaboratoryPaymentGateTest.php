<?php

namespace Tests\Feature;

use App\Enums\VisitType;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\LabRequest;
use App\Models\LabRequestItem;
use App\Models\Visit;
use App\Services\Billing\PaymentGateService;
use Tests\TestCase;

class LaboratoryPaymentGateTest extends TestCase
{
    public function test_intrinsic_settlement_and_policy_permission_remain_explicitly_separate(): void
    {
        $missing = (new LabRequestItem)->setRelation('labRequest', new LabRequest);
        $this->assertTrue($missing->isBillSettled());
        $this->assertSame(
            'INVOICE_ITEM_MISSING_ALLOWED',
            app(PaymentGateService::class)->policyForLabResultEntry($missing)->reason,
        );

        $unpaid = $this->requestItem($this->invoiceItem('unpaid', 100, 0, 0));
        $this->assertFalse($unpaid->isBillSettled());
        $this->assertFalse(app(PaymentGateService::class)->canEnterLabResult($unpaid));

        foreach ([
            $this->invoiceItem('paid', 0, 100, 0),
            $this->invoiceItem('unpaid', 0, 0, 100),
            $this->invoiceItem('waived', 100, 0, 0),
        ] as $settledItem) {
            $requestItem = $this->requestItem($settledItem);
            $this->assertTrue($requestItem->isBillSettled());
            $this->assertTrue(app(PaymentGateService::class)->canEnterLabResult($requestItem));
        }
    }

    private function requestItem(InvoiceItem $invoiceItem): LabRequestItem
    {
        return (new LabRequestItem(['invoice_item_id' => 10]))
            ->setRelation('labRequest', new LabRequest)
            ->setRelation('invoiceItem', $invoiceItem);
    }

    private function invoiceItem(string $status, float $balance, float $paid, float $covered): InvoiceItem
    {
        $visit = (new Visit(['visit_type' => VisitType::OUTPATIENT]))->setRelation('billingOverrides', collect());
        $item = new InvoiceItem([
            'patient_payable' => $covered > 0 ? 0 : 100,
            'paid_amount' => $paid,
            'insurance_covered' => $covered,
            'balance' => $balance,
            'payment_status' => $status,
        ]);

        return $item
            ->setRelation('visit', $visit)
            ->setRelation('invoice', new Invoice(['balance' => $balance, 'adjustment_amount' => 0]));
    }
}
