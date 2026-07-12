<?php

namespace Tests\Feature;

use App\Enums\BillingType;
use App\Enums\InvoiceStatus;
use App\Enums\VisitFinancialClearanceExceptionType;
use App\Enums\VisitFinancialClearanceStatus;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceReceivable;
use App\Models\Setting;
use App\Models\User;
use App\Models\Visit;
use App\Services\Billing\VisitFinancialClearanceConfigurationService;
use App\Services\Billing\VisitFinancialClearanceExceptionService;
use App\Services\Billing\VisitFinancialClearanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class VisitFinancialClearanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_settled_visit_clears_and_financial_close_preserves_clinical_status(): void
    {
        config(['visit_financial_clearance.force_disabled' => false]);
        Setting::setValue(VisitFinancialClearanceConfigurationService::GROUP, 'mode', 'active');
        $actor = User::factory()->create();
        $visit = Visit::factory()->create(['created_by' => $actor->id]);
        Permission::findOrCreate('visits.financial_clearance.close');
        $actor->givePermissionTo('visits.financial_clearance.close');
        $this->invoice($visit, 100, 100, 0);
        $clinicalStatus = $visit->status;

        $clearance = app(VisitFinancialClearanceService::class)->assess($visit, $actor);
        $this->assertSame(VisitFinancialClearanceStatus::CLEARED, $clearance->status);
        $closed = app(VisitFinancialClearanceService::class)->financiallyClose($visit, $actor, 'End of visit reconciliation');

        $this->assertSame(VisitFinancialClearanceStatus::FINANCIALLY_CLOSED, $closed->status);
        $this->assertSame($clinicalStatus, $visit->fresh()->status);
        $this->assertDatabaseHas('invoice_receivables', ['visit_id' => $visit->id, 'balance' => 0]);
    }

    public function test_outstanding_requires_separate_approved_exception_and_preserves_receivable(): void
    {
        $requester = User::factory()->create();
        $approver = User::factory()->create();
        $visit = Visit::factory()->create(['created_by' => $requester->id]);
        foreach (['visits.financial_clearance_exception.request', 'visits.financial_clearance_exception.approve'] as $name) Permission::findOrCreate($name);
        $requester->givePermissionTo('visits.financial_clearance_exception.request');
        $approver->givePermissionTo('visits.financial_clearance_exception.approve');
        $this->invoice($visit, 120, 20, 100);

        $pending = app(VisitFinancialClearanceService::class)->assess($visit);
        $this->assertSame(VisitFinancialClearanceStatus::PENDING, $pending->status);
        $exception = app(VisitFinancialClearanceExceptionService::class)->request($visit, VisitFinancialClearanceExceptionType::OUTSTANDING_BALANCE_APPROVAL, '100.00', 'Approved credit account', $requester, 'REF-1');
        app(VisitFinancialClearanceExceptionService::class)->approve($exception, '100.00', 'Credit checked', $approver);

        $this->assertSame(VisitFinancialClearanceStatus::CONDITIONALLY_CLEARED, $visit->financialClearance()->first()->status);
        $this->assertDatabaseHas('invoice_receivables', ['visit_id' => $visit->id, 'balance' => 100, 'status' => InvoiceReceivable::STATUS_PARTIALLY_PAID]);
    }

    private function invoice(Visit $visit, float $responsibility, float $paid, float $balance): Invoice
    {
        $invoice = Invoice::create(['invoice_number' => 'INV-'.$visit->id, 'visit_id' => $visit->id, 'patient_id' => $visit->patient_id, 'billing_type' => BillingType::CASH, 'subtotal' => $responsibility, 'total_amount' => $responsibility, 'amount_paid' => $paid, 'balance' => $balance, 'status' => $balance > 0 ? InvoiceStatus::PARTIALLY_PAID : InvoiceStatus::PAID, 'created_by' => $visit->created_by]);
        InvoiceItem::create(['invoice_id' => $invoice->id, 'visit_id' => $visit->id, 'patient_id' => $visit->patient_id, 'description' => 'Service', 'quantity' => 1, 'unit_price' => $responsibility, 'selected_price' => $responsibility, 'total_price' => $responsibility, 'patient_payable' => $responsibility, 'paid_amount' => $paid, 'balance' => $balance, 'payment_status' => $balance > 0 ? 'partially_paid' : 'paid']);
        InvoiceReceivable::create(['invoice_id' => $invoice->id, 'visit_id' => $visit->id, 'patient_id' => $visit->patient_id, 'payer_type' => InvoiceReceivable::PAYER_PATIENT, 'original_amount' => $responsibility, 'allocated_amount' => $responsibility, 'paid_amount' => $paid, 'balance' => $balance, 'aging_start_date' => today(), 'status' => $balance > 0 ? InvoiceReceivable::STATUS_PARTIALLY_PAID : InvoiceReceivable::STATUS_PAID]);
        return $invoice;
    }
}
