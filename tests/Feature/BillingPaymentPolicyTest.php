<?php

namespace Tests\Feature;

use App\Enums\BillingType;
use App\Enums\InvoiceStatus;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Exceptions\BillingGateException;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitBillingOverride;
use App\Services\Billing\BillingPolicyService;
use App\Services\Billing\InvoiceItemSettlementService;
use App\Services\Billing\PaymentGateService;
use App\Services\Billing\VisitBillingOverrideService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Context-aware billing payment gate (decision engine). Exercises the policy /
 * settlement / gate / override services directly — no controller wiring — so it
 * proves the engine without touching existing workflows.
 */
class BillingPaymentPolicyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function gate(): PaymentGateService
    {
        return app(PaymentGateService::class);
    }

    private function makeVisit(VisitType $type): Visit
    {
        return Visit::factory()->create([
            'visit_type' => $type,
            'status' => VisitStatus::ACTIVE,
        ]);
    }

    private function billItem(Visit $visit, array $overrides = []): InvoiceItem
    {
        $invoice = Invoice::create([
            'invoice_number' => 'INV' . fake()->unique()->numberBetween(10000, 99999),
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'billing_type' => BillingType::CASH,
            'subtotal' => 100,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'nhis_amount' => 0,
            'total_amount' => 100,
            'amount_paid' => 0,
            'balance' => 100,
            'status' => InvoiceStatus::PENDING,
            'created_by' => $this->user->id,
        ]);

        return InvoiceItem::create(array_merge([
            'invoice_id' => $invoice->id,
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'source_type' => InvoiceItem::SOURCE_CONSULTATION_SERVICE,
            'description' => 'Consultation',
            'quantity' => 1,
            'unit_price' => 100,
            'cash_price' => 100,
            'selected_price' => 100,
            'insurance_covered' => 0,
            'discount_amount' => 0,
            'patient_payable' => 100,
            'paid_amount' => 0,
            'balance' => 100,
            'payment_status' => 'unpaid',
            'total_price' => 100,
            'payer_type' => 'cash',
            'created_by' => $this->user->id,
        ], $overrides));
    }

    // ── Care context ────────────────────────────────────────────────

    public function test_care_context_detection_from_visit_type(): void
    {
        $policy = app(BillingPolicyService::class);
        $this->assertSame('OPD', $policy->careContext($this->makeVisit(VisitType::OUTPATIENT)));
        $this->assertSame('EMERGENCY', $policy->careContext($this->makeVisit(VisitType::EMERGENCY)));
        $this->assertSame('ADMISSION', $policy->careContext($this->makeVisit(VisitType::INPATIENT)));
    }

    // ── Settlement status ───────────────────────────────────────────

    public function test_settlement_status_reflects_invoice_item_fields(): void
    {
        $s = app(InvoiceItemSettlementService::class);
        $visit = $this->makeVisit(VisitType::OUTPATIENT);

        $this->assertSame('BILLED_UNPAID', $s->settlementStatus($this->billItem($visit)));
        $this->assertSame('PAID', $s->settlementStatus($this->billItem($visit, ['payment_status' => 'paid', 'paid_amount' => 100, 'balance' => 0])));
        $this->assertSame('PARTIALLY_PAID', $s->settlementStatus($this->billItem($visit, ['paid_amount' => 40, 'balance' => 60])));
        $this->assertSame('WAIVED', $s->settlementStatus($this->billItem($visit, ['payment_status' => 'waived'])));
        $this->assertSame('COVERED_BY_INSURANCE', $s->settlementStatus($this->billItem($visit, ['patient_payable' => 0, 'insurance_covered' => 100, 'balance' => 0])));

        $coveredWithStaleBalance = $this->billItem($visit, [
            'patient_payable' => 0,
            'insurance_covered' => 100,
            'balance' => 100,
        ]);
        $this->assertSame('COVERED_BY_INSURANCE', $s->settlementStatus($coveredWithStaleBalance));
        $this->assertSame(0.0, $s->outstandingBalance($coveredWithStaleBalance));

        $paidWithStaleBalance = $this->billItem($visit, [
            'patient_payable' => 100,
            'paid_amount' => 100,
            'balance' => 100,
        ]);
        $this->assertSame('PAID', $s->settlementStatus($paidWithStaleBalance));
        $this->assertSame(0.0, $s->outstandingBalance($paidWithStaleBalance));

        $legacyCashWithOnlyBalance = $this->billItem($visit, [
            'patient_payable' => 0,
            'insurance_covered' => 0,
            'paid_amount' => 0,
            'balance' => 100,
        ]);
        $this->assertSame('BILLED_UNPAID', $s->settlementStatus($legacyCashWithOnlyBalance));
        $this->assertSame(100.0, $s->outstandingBalance($legacyCashWithOnlyBalance));
    }

    // ── OPD strict gate ─────────────────────────────────────────────

    public function test_opd_unpaid_item_is_blocked_with_friendly_message(): void
    {
        $item = $this->billItem($this->makeVisit(VisitType::OUTPATIENT));

        $this->assertFalse($this->gate()->canRenderInvoiceItem($item, $this->user));

        $this->expectException(BillingGateException::class);
        $this->expectExceptionMessage('Consultation fee has not been settled.');
        $this->gate()->assertCanRenderInvoiceItem($item, $this->user);
    }

    public function test_opd_paid_covered_or_waived_items_are_allowed(): void
    {
        $visit = $this->makeVisit(VisitType::OUTPATIENT);
        $gate = $this->gate();

        $this->assertTrue($gate->canRenderInvoiceItem($this->billItem($visit, ['payment_status' => 'paid', 'balance' => 0]), $this->user));
        $this->assertTrue($gate->canRenderInvoiceItem($this->billItem($visit, ['patient_payable' => 0, 'insurance_covered' => 100, 'balance' => 0]), $this->user));
        $this->assertTrue($gate->canRenderInvoiceItem($this->billItem($visit, ['payment_status' => 'waived']), $this->user));
    }

    public function test_fully_adjusted_invoice_allows_unpaid_item_to_proceed(): void
    {
        $item = $this->billItem($this->makeVisit(VisitType::OUTPATIENT));
        $item->invoice->forceFill([
            'adjustment_amount' => 100,
            'balance' => 0,
            'status' => InvoiceStatus::PAID,
        ])->save();

        $settlement = app(InvoiceItemSettlementService::class);

        $this->assertSame(
            InvoiceItemSettlementService::ADJUSTED,
            $settlement->settlementStatus($item->fresh())
        );
        $this->assertTrue($settlement->canProceedWithoutCashPayment($item->fresh()));
        $this->assertTrue($this->gate()->canRenderInvoiceItem($item->fresh(), $this->user));
    }

    public function test_partially_adjusted_invoice_does_not_unlock_unpaid_item(): void
    {
        $item = $this->billItem($this->makeVisit(VisitType::OUTPATIENT));
        $item->invoice->forceFill([
            'adjustment_amount' => 40,
            'balance' => 60,
            'status' => InvoiceStatus::PARTIALLY_PAID,
        ])->save();

        $settlement = app(InvoiceItemSettlementService::class);

        $this->assertSame(
            InvoiceItemSettlementService::BILLED_UNPAID,
            $settlement->settlementStatus($item->fresh())
        );
        $this->assertFalse($settlement->canProceedWithoutCashPayment($item->fresh()));
        $this->assertFalse($this->gate()->canRenderInvoiceItem($item->fresh(), $this->user));
    }

    public function test_opd_partial_payment_is_blocked_by_default(): void
    {
        $item = $this->billItem($this->makeVisit(VisitType::OUTPATIENT), ['paid_amount' => 40, 'balance' => 60]);
        $this->assertFalse($this->gate()->canRenderInvoiceItem($item, $this->user));
    }

    // ── Running bill (Emergency / Admission) ────────────────────────

    public function test_emergency_unpaid_item_is_allowed_running_bill(): void
    {
        $item = $this->billItem($this->makeVisit(VisitType::EMERGENCY));
        $decision = $this->gate()->policyFor($item, $this->user);

        $this->assertTrue($decision->allowed);
        $this->assertSame(BillingPolicyService::MODE_RUNNING_BILL, $decision->mode);
    }

    public function test_admission_unpaid_item_is_allowed_running_bill(): void
    {
        $item = $this->billItem($this->makeVisit(VisitType::INPATIENT));
        $this->assertTrue($this->gate()->canRenderInvoiceItem($item, $this->user));
    }

    // ── Advisory mode ───────────────────────────────────────────────

    public function test_advisory_mode_allows_everything(): void
    {
        config(['billing_policy.enforce' => false]);
        $item = $this->billItem($this->makeVisit(VisitType::OUTPATIENT));
        $this->assertTrue($this->gate()->canRenderInvoiceItem($item, $this->user));
    }

    // ── Deferred OPD settlement override ────────────────────────────

    public function test_deferred_settlement_requires_reason(): void
    {
        $this->expectException(\RuntimeException::class);
        app(VisitBillingOverrideService::class)->approveDeferredSettlement(
            $this->makeVisit(VisitType::OUTPATIENT), $this->user, '   '
        );
    }

    public function test_deferred_settlement_only_for_opd(): void
    {
        $this->expectException(\RuntimeException::class);
        app(VisitBillingOverrideService::class)->approveDeferredSettlement(
            $this->makeVisit(VisitType::EMERGENCY), $this->user, 'Patient will pay at end'
        );
    }

    public function test_active_deferred_settlement_allows_unpaid_opd_item(): void
    {
        $visit = $this->makeVisit(VisitType::OUTPATIENT);
        $item = $this->billItem($visit);

        $this->assertFalse($this->gate()->canRenderInvoiceItem($item, $this->user));

        $override = app(VisitBillingOverrideService::class)
            ->approveDeferredSettlement($visit, $this->user, 'Trusted patient, pays at end');

        $this->assertSame(VisitBillingOverride::STATUS_ACTIVE, $override->status);

        $decision = $this->gate()->policyFor($item->fresh(), $this->user);
        $this->assertTrue($decision->allowed);
        $this->assertSame(BillingPolicyService::MODE_DEFERRED_VISIT_SETTLEMENT, $decision->mode);
        $this->assertTrue($decision->overrideUsed);
    }

    public function test_revoked_deferred_settlement_blocks_again(): void
    {
        $visit = $this->makeVisit(VisitType::OUTPATIENT);
        $item = $this->billItem($visit);
        $svc = app(VisitBillingOverrideService::class);

        $override = $svc->approveDeferredSettlement($visit, $this->user, 'Pays at end');
        $this->assertTrue($this->gate()->canRenderInvoiceItem($item->fresh(), $this->user));

        $svc->revokeOverride($override, $this->user, 'Changed mind');
        $this->assertFalse($this->gate()->canRenderInvoiceItem($item->fresh(), $this->user));
    }

    public function test_expired_deferred_settlement_blocks(): void
    {
        $visit = $this->makeVisit(VisitType::OUTPATIENT);
        $item = $this->billItem($visit);

        VisitBillingOverride::create([
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'override_type' => VisitBillingOverride::TYPE_DEFERRED_OPD_SETTLEMENT,
            'scope' => VisitBillingOverride::SCOPE_VISIT,
            'reason' => 'Expired override',
            'authorized_by' => $this->user->id,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->subHour(),
            'status' => VisitBillingOverride::STATUS_ACTIVE,
            'created_by' => $this->user->id,
        ]);

        $this->assertFalse($this->gate()->canRenderInvoiceItem($item, $this->user));
    }

    public function test_complete_deferred_settlement_marks_completed(): void
    {
        $visit = $this->makeVisit(VisitType::OUTPATIENT);
        $svc = app(VisitBillingOverrideService::class);
        $svc->approveDeferredSettlement($visit, $this->user, 'Pays at end');

        $this->assertTrue($svc->hasActiveDeferredSettlement($visit));

        $svc->completeDeferredSettlement($visit, $this->user);

        $this->assertFalse($svc->hasActiveDeferredSettlement($visit));
        $this->assertDatabaseHas('visit_billing_overrides', [
            'visit_id' => $visit->id,
            'status' => VisitBillingOverride::STATUS_COMPLETED,
        ]);
    }

    public function test_deferred_settlement_records_pathway_event(): void
    {
        $visit = $this->makeVisit(VisitType::OUTPATIENT);
        app(VisitBillingOverrideService::class)->approveDeferredSettlement($visit, $this->user, 'Pays at end');

        $this->assertDatabaseHas('visit_pathway_events', [
            'visit_id' => $visit->id,
            'event_type' => 'DEFERRED_SETTLEMENT_APPROVED',
        ]);
    }
}
