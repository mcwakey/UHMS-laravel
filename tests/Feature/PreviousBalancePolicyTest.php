<?php

namespace Tests\Feature;

use App\Enums\BillingType;
use App\Enums\InvoiceStatus;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Services\Billing\PatientOutstandingBalanceService;
use App\Services\Billing\PatientPaymentAllocationService;
use App\Services\Billing\PreviousBalanceOverrideService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Previous-visit outstanding balance policy + cross-visit payment allocation.
 * Exercises the three services directly against real invoices/receivables so it
 * proves the behaviour without touching existing billing workflows.
 */
class PreviousBalancePolicyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->patient = Patient::factory()->create();
        $this->actingAs($this->user);
        config([
            'billing.previous_balance_policy.enabled' => true,
            'billing.previous_balance_policy.opd_requires_override_above_amount' => 100.00,
            'billing.previous_balance_policy.overpayment_behaviour' => 'reject',
        ]);
    }

    private function makeVisit(VisitType $type = VisitType::OUTPATIENT): Visit
    {
        return Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_type' => $type,
            'status' => VisitStatus::ACTIVE,
        ]);
    }

    /** Create a pending patient-cash invoice with one line for the given amount. */
    private function makeInvoice(Visit $visit, float $amount, ?Carbon $createdAt = null): Invoice
    {
        $invoice = Invoice::create([
            'invoice_number' => 'INV' . fake()->unique()->numberBetween(100000, 999999),
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'billing_type' => BillingType::CASH,
            'subtotal' => $amount,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'nhis_amount' => 0,
            'total_amount' => $amount,
            'amount_paid' => 0,
            'balance' => $amount,
            'status' => InvoiceStatus::PENDING,
            'created_by' => $this->user->id,
        ]);

        if ($createdAt) {
            $invoice->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();
        }

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'source_type' => InvoiceItem::SOURCE_CONSULTATION_SERVICE,
            'description' => 'Service',
            'quantity' => 1,
            'unit_price' => $amount,
            'cash_price' => $amount,
            'selected_price' => $amount,
            'insurance_covered' => 0,
            'discount_amount' => 0,
            'patient_payable' => $amount,
            'paid_amount' => 0,
            'balance' => $amount,
            'payment_status' => 'unpaid',
            'total_price' => $amount,
            'payer_type' => 'cash',
            'created_by' => $this->user->id,
        ]);

        return $invoice->fresh();
    }

    private function balances(): PatientOutstandingBalanceService
    {
        return app(PatientOutstandingBalanceService::class);
    }

    // ── Detection & summary ─────────────────────────────────────────

    public function test_detects_previous_current_and_total_balances(): void
    {
        $previousVisit = $this->makeVisit();
        $this->makeInvoice($previousVisit, 250, now()->subDays(45));

        $currentVisit = $this->makeVisit();
        $this->makeInvoice($currentVisit, 100, now());

        $svc = $this->balances();

        $this->assertEqualsWithDelta(250.00, $svc->getPreviousOutstandingBalance($this->patient, $currentVisit), 0.001);
        $this->assertEqualsWithDelta(100.00, $svc->getCurrentVisitBalance($currentVisit), 0.001);
        $this->assertEqualsWithDelta(350.00, $svc->getTotalOutstandingBalance($this->patient), 0.001);
        $this->assertTrue($svc->hasPreviousOutstandingBalance($this->patient, $currentVisit));

        $summary = $svc->buildPatientBalanceSummary($this->patient, $currentVisit);
        $this->assertEqualsWithDelta(250.00, $summary['previous_outstanding'], 0.001);
        $this->assertEqualsWithDelta(100.00, $summary['current_visit_outstanding'], 0.001);
        $this->assertEqualsWithDelta(350.00, $summary['total_outstanding'], 0.001);
        $this->assertSame('31-60', $summary['ar_bucket']);
        $this->assertEqualsWithDelta(45, $summary['oldest_age_days'], 1);
        $this->assertNotNull($summary['oldest_unpaid_invoice']);
    }

    public function test_current_visit_invoice_is_never_modified_by_previous_debt(): void
    {
        $previousVisit = $this->makeVisit();
        $this->makeInvoice($previousVisit, 250, now()->subDays(45));

        $currentVisit = $this->makeVisit();
        $currentInvoice = $this->makeInvoice($currentVisit, 100, now());

        // Reading the patient account must not touch the current visit invoice.
        $this->balances()->buildPatientBalanceSummary($this->patient, $currentVisit);

        $currentInvoice->refresh();
        $this->assertEqualsWithDelta(100.00, (float) $currentInvoice->total_amount, 0.001);
        $this->assertEqualsWithDelta(100.00, (float) $currentInvoice->balance, 0.001);
        $this->assertSame(1, $currentInvoice->items()->count());
    }

    // ── Cross-visit allocation ──────────────────────────────────────

    public function test_oldest_first_allocation_clears_old_then_current(): void
    {
        // Isolate from the post-payment notification listener (needs seeded roles).
        \Illuminate\Support\Facades\Event::fake([\App\Events\PaymentRecorded::class]);

        $previousVisit = $this->makeVisit();
        $oldInvoice = $this->makeInvoice($previousVisit, 250, now()->subDays(45));

        $currentVisit = $this->makeVisit();
        $currentInvoice = $this->makeInvoice($currentVisit, 100, now());

        $payments = app(PatientPaymentAllocationService::class)->allocatePaymentOldestFirst(
            $this->patient,
            ['amount' => 300, 'payment_method' => \App\Enums\PaymentMethod::CASH],
            $currentVisit,
        );

        $this->assertCount(2, $payments);

        $this->assertEqualsWithDelta(0.00, (float) $oldInvoice->fresh()->balance, 0.001);
        $this->assertSame(InvoiceStatus::PAID, $oldInvoice->fresh()->status);
        $this->assertEqualsWithDelta(50.00, (float) $currentInvoice->fresh()->balance, 0.001);

        // Both split payments are grouped by a shared batch reference.
        $batch = $payments->first()->payment_batch_reference;
        $this->assertNotNull($batch);
        $this->assertTrue($payments->every(fn ($p) => $p->payment_batch_reference === $batch));

        // Remaining patient balance is just the current visit's 50.
        $this->assertEqualsWithDelta(50.00, $this->balances()->getTotalOutstandingBalance($this->patient), 0.001);
    }

    public function test_manual_allocation_cannot_exceed_invoice_balance(): void
    {
        $visit = $this->makeVisit();
        $invoice = $this->makeInvoice($visit, 100, now());

        $this->expectException(\RuntimeException::class);
        app(PatientPaymentAllocationService::class)->allocatePaymentManually(
            $this->patient,
            [['invoice_id' => $invoice->id, 'amount' => 150]],
            ['amount' => 150, 'payment_method' => \App\Enums\PaymentMethod::CASH],
        );
    }

    public function test_overpayment_is_rejected_by_default(): void
    {
        $visit = $this->makeVisit();
        $this->makeInvoice($visit, 100, now());

        $this->expectException(\RuntimeException::class);
        app(PatientPaymentAllocationService::class)->allocatePaymentOldestFirst(
            $this->patient,
            ['amount' => 250, 'payment_method' => \App\Enums\PaymentMethod::CASH],
            $visit,
        );
    }

    // ── OPD override gate ───────────────────────────────────────────

    public function test_opd_override_required_above_threshold_then_cleared_by_approval(): void
    {
        $previousVisit = $this->makeVisit();
        $this->makeInvoice($previousVisit, 250, now()->subDays(45));

        $currentVisit = $this->makeVisit();
        $this->makeInvoice($currentVisit, 100, now());

        $override = app(PreviousBalanceOverrideService::class);

        $this->assertTrue($override->requiresOverride($currentVisit));
        $this->assertTrue($override->isBlocked($currentVisit));

        $override->approveOverride($currentVisit, $this->user, 'Patient will settle old balance next week');

        $this->assertTrue($override->hasActiveOverride($currentVisit));
        $this->assertFalse($override->isBlocked($currentVisit));
    }

    public function test_emergency_care_is_never_blocked_by_previous_debt(): void
    {
        $previousVisit = $this->makeVisit();
        $this->makeInvoice($previousVisit, 250, now()->subDays(45));

        $emergencyVisit = $this->makeVisit(VisitType::EMERGENCY);

        $override = app(PreviousBalanceOverrideService::class);
        $this->assertFalse($override->requiresOverride($emergencyVisit));
        $this->assertFalse($override->isBlocked($emergencyVisit));

        $evaluation = $override->evaluate($emergencyVisit);
        $this->assertFalse($evaluation['blocked']);
        $this->assertSame('EMERGENCY_NEVER_BLOCKED', $evaluation['reason']);
    }

    public function test_below_threshold_previous_balance_does_not_require_override(): void
    {
        $previousVisit = $this->makeVisit();
        $this->makeInvoice($previousVisit, 50, now()->subDays(10));

        $currentVisit = $this->makeVisit();
        $this->makeInvoice($currentVisit, 100, now());

        $override = app(PreviousBalanceOverrideService::class);
        $this->assertFalse($override->requiresOverride($currentVisit));
    }

    public function test_total_outstanding_map_sums_open_patient_balances(): void
    {
        $v1 = $this->makeVisit();
        $this->makeInvoice($v1, 250, now()->subDays(45));
        $v2 = $this->makeVisit();
        $this->makeInvoice($v2, 100, now());

        // Materialise receivables (the batch map does not sync, for list perf).
        $this->balances()->getTotalOutstandingBalance($this->patient);

        $map = $this->balances()->totalOutstandingMap([$this->patient->id, 999999]);
        $this->assertEqualsWithDelta(350.00, $map[$this->patient->id], 0.001);
        $this->assertArrayNotHasKey(999999, $map);
    }

    // ── UI component ────────────────────────────────────────────────

    public function test_alert_component_shows_amounts_for_authorised_user(): void
    {
        $previousVisit = $this->makeVisit();
        $this->makeInvoice($previousVisit, 250, now()->subDays(45));
        $currentVisit = $this->makeVisit();
        $this->makeInvoice($currentVisit, 100, now());

        // Permissions exist thanks to the seed migration; grant them to this user.
        $this->user->givePermissionTo(['billing.previous_balance.amount.view', 'billing.previous_balance.flag.view']);
        $this->actingAs($this->user->fresh());

        $html = \Illuminate\Support\Facades\Blade::render(
            '<x-billing.previous-balance-alert :visit="$visit" />',
            ['visit' => $currentVisit->fresh()]
        );

        $this->assertStringContainsString('250.00', $html);
        $this->assertStringContainsString('350.00', $html);
        $this->assertStringContainsString('31-60', $html);
    }

    public function test_alert_component_hides_amounts_for_flag_only_user(): void
    {
        $previousVisit = $this->makeVisit();
        $this->makeInvoice($previousVisit, 250, now()->subDays(45));
        $currentVisit = $this->makeVisit();
        $this->makeInvoice($currentVisit, 100, now());

        $this->user->givePermissionTo(['billing.previous_balance.flag.view']);
        $this->actingAs($this->user->fresh());

        $html = \Illuminate\Support\Facades\Blade::render(
            '<x-billing.previous-balance-alert :visit="$visit" />',
            ['visit' => $currentVisit->fresh()]
        );

        // Flag-only clinical users must never see the financial amounts.
        $this->assertStringNotContainsString('250.00', $html);
        $this->assertStringContainsString(__('billing.billing_clearance_required'), $html);
    }

    // ── HTTP endpoints ──────────────────────────────────────────────

    public function test_override_endpoint_approves_for_authorised_user(): void
    {
        $previousVisit = $this->makeVisit();
        $this->makeInvoice($previousVisit, 250, now()->subDays(45));
        $currentVisit = $this->makeVisit();
        $this->makeInvoice($currentVisit, 100, now());

        $this->user->givePermissionTo('billing.previous_balance.override');

        $response = $this->actingAs($this->user->fresh())->post(
            route('admin.billing.previous-balance.override', $currentVisit),
            ['reason' => 'Patient will settle old balance next week'],
        );

        $response->assertRedirect();
        $this->assertTrue(app(PreviousBalanceOverrideService::class)->hasActiveOverride($currentVisit->fresh()));
    }

    public function test_override_endpoint_forbidden_without_permission(): void
    {
        $visit = $this->makeVisit();
        $response = $this->actingAs($this->user->fresh())->post(
            route('admin.billing.previous-balance.override', $visit),
            ['reason' => 'no permission'],
        );
        $response->assertForbidden();
    }

    public function test_receive_page_renders_cross_visit_allocation_affordance(): void
    {
        $previousVisit = $this->makeVisit();
        $this->makeInvoice($previousVisit, 250, now()->subDays(45));
        $currentVisit = $this->makeVisit();
        $this->makeInvoice($currentVisit, 100, now());

        foreach (['payments.view', 'payments.create'] as $p) {
            \Spatie\Permission\Models\Permission::findOrCreate($p, 'web');
        }
        $this->user->givePermissionTo([
            'payments.view', 'payments.create',
            'billing.previous_balance.amount.view', 'billing.payment.allocate_cross_visit',
        ]);

        $response = $this->actingAs($this->user->fresh())->get(route('admin.billing.payments.receive'));

        $response->assertOk();
        $response->assertSee('data-cross-visit-allocate', false);
        $response->assertSee(__('billing.split_payment_across_visits'));
    }

    public function test_statement_page_renders_previous_balance_summary_cards(): void
    {
        $previousVisit = $this->makeVisit();
        $this->makeInvoice($previousVisit, 250, now()->subDays(45));
        $currentVisit = $this->makeVisit();
        $this->makeInvoice($currentVisit, 100, now());

        \Spatie\Permission\Models\Permission::findOrCreate('invoices.view', 'web');
        $this->user->givePermissionTo('invoices.view');

        $response = $this->actingAs($this->user->fresh())->get(route('admin.billing.statements.show', $this->patient));

        $response->assertOk();
        $response->assertSee(__('billing.previous_visits_outstanding'));
        $response->assertSee('350.00');
    }

    public function test_allocate_endpoint_records_cross_visit_payment(): void
    {
        \Illuminate\Support\Facades\Event::fake([\App\Events\PaymentRecorded::class]);

        $previousVisit = $this->makeVisit();
        $this->makeInvoice($previousVisit, 250, now()->subDays(45));
        $currentVisit = $this->makeVisit();
        $this->makeInvoice($currentVisit, 100, now());

        \Spatie\Permission\Models\Permission::findOrCreate('payments.view', 'web');
        $this->user->givePermissionTo(['payments.view', 'billing.payment.allocate_cross_visit']);

        $response = $this->actingAs($this->user->fresh())->post(
            route('admin.billing.previous-balance.allocate', $this->patient),
            [
                'amount' => 300,
                'payment_method' => 'cash',
                'mode' => 'oldest_first',
                'visit_id' => $currentVisit->id,
            ],
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEqualsWithDelta(50.00, $this->balances()->getTotalOutstandingBalance($this->patient), 0.001);
    }
}
