<?php

namespace Tests\Feature\Integrations;

use App\Enums\CreditNoteType;
use App\Enums\InvoiceStatus;
use App\Exceptions\Integrations\IntegrationException;
use App\Models\ActivityLog;
use App\Models\CreditNote;
use App\Models\Department;
use App\Models\IntegrationProvider;
use App\Models\IntegrationProviderChecklist;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Module;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\PaymentProviderRefund;
use App\Models\PaymentRequestLink;
use App\Models\SmsNotificationEvent;
use App\Models\User;
use App\Models\Visit;
use App\Services\Integrations\IntegrationProviderService;
use App\Services\Integrations\Payment\PaymentGatewayService;
use App\Services\Integrations\Payment\PaymentRefundBridgeService;
use App\Services\Integrations\Payment\PaymentRequestLinkService;
use App\Services\Integrations\Payment\PublicPaymentLinkService;
use App\Services\Integrations\ProviderGoLiveChecklistService;
use App\Services\Integrations\Sms\SmsEventSettingsService;
use App\Services\Integrations\Sms\SmsNotificationEventService;
use App\Services\ModuleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Lang;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class IntegrationsPhase3Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function userWith(array $permissions = []): User
    {
        $role = Role::findOrCreate('P3Role-' . uniqid(), 'web');
        foreach ($permissions as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $user = User::factory()->create();
        $user->assignRole($role);
        return $user;
    }

    private function paymentProvider(bool $active = true, array $overrides = []): IntegrationProvider
    {
        return IntegrationProvider::create(array_merge([
            'module_type' => 'payment', 'code' => 'fake_payment', 'name' => 'Fake Payment',
            'environment' => 'sandbox', 'status' => $active ? 'active' : 'draft', 'is_active' => $active,
        ], IntegrationProviderService::capabilityFlags('payment', 'fake_payment'), $overrides));
    }

    private function smsProvider(bool $active = true): IntegrationProvider
    {
        return IntegrationProvider::create(array_merge([
            'module_type' => 'sms', 'code' => 'fake_sms', 'name' => 'Fake SMS',
            'environment' => 'sandbox', 'status' => $active ? 'active' : 'draft', 'is_active' => $active,
        ], IntegrationProviderService::capabilityFlags('sms', 'fake_sms')));
    }

    private function payableInvoice(float $amount = 100.0): Invoice
    {
        Role::findOrCreate('Accountant', 'web');
        $dept = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $dept->id]);
        $patient = Patient::factory()->create(['registered_by' => $user->id, 'phone' => '0241234567']);
        $visit = Visit::factory()->create(['patient_id' => $patient->id, 'current_department_id' => $dept->id, 'created_by' => $user->id]);
        $invoice = Invoice::create([
            'invoice_number' => 'INV' . uniqid(), 'visit_id' => $visit->id, 'patient_id' => $patient->id,
            'billing_type' => 'cash', 'subtotal' => $amount, 'tax_amount' => 0, 'discount_amount' => 0,
            'nhis_amount' => 0, 'total_amount' => $amount, 'amount_paid' => 0, 'balance' => $amount,
            'status' => InvoiceStatus::PENDING, 'created_by' => $user->id,
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id, 'visit_id' => $visit->id, 'patient_id' => $patient->id,
            'description' => 'Service', 'quantity' => 1, 'unit_price' => $amount, 'cash_price' => $amount,
            'selected_price' => $amount, 'insurance_covered' => 0, 'discount_amount' => 0, 'patient_payable' => $amount,
            'paid_amount' => 0, 'balance' => $amount, 'payment_status' => 'unpaid', 'total_price' => $amount,
            'payer_type' => 'cash', 'created_by' => $user->id,
        ]);
        return $invoice->load('items');
    }

    private function link(Invoice $invoice, array $overrides = []): PaymentRequestLink
    {
        $this->actingAs($this->userWith());
        return tap(app(PaymentRequestLinkService::class)->create([
            'invoice_id' => $invoice->id, 'visit_id' => $invoice->visit_id,
            'patient_id' => $invoice->patient_id, 'amount' => (float) $invoice->balance,
        ]))->update($overrides);
    }

    /* ── Public portal ──────────────────────────────────────────────── */

    public function test_public_payment_page_uses_token_and_hides_internal_ids(): void
    {
        $invoice = $this->payableInvoice(100);
        $this->paymentProvider(true);
        $link = $this->link($invoice);

        $response = $this->get(route('public.payments.show', $link->public_token));
        $response->assertOk()->assertSee($invoice->invoice_number)->assertDontSee('token_hash');
        // The public URL is the random token, never the numeric id.
        $this->assertStringContainsString($link->public_token, route('public.payments.show', $link->public_token));
        $this->assertStringNotContainsString('/' . $link->id, route('public.payments.show', $link->public_token));
    }

    public function test_expired_link_cannot_initiate(): void
    {
        $invoice = $this->payableInvoice(100);
        $this->paymentProvider(true);
        $link = $this->link($invoice, ['expires_at' => now()->subDay()]);

        $this->post(route('public.payments.initiate', $link->public_token), ['payer_phone' => '0241234567'])
            ->assertRedirect();
        $this->assertSame(0, $invoice->fresh()->payments()->count() ?? 0);
        $this->assertNull($link->fresh()->payment_provider_transaction_id);
    }

    public function test_cancelled_link_cannot_initiate(): void
    {
        $invoice = $this->payableInvoice(100);
        $this->paymentProvider(true);
        $link = $this->link($invoice, ['status' => PaymentRequestLink::STATUS_CANCELLED]);

        $this->expectException(IntegrationException::class);
        app(PublicPaymentLinkService::class)->initiate($link->fresh(), ['payer_phone' => '0241234567']);
    }

    public function test_already_paid_invoice_link_shows_paid_state(): void
    {
        $invoice = $this->payableInvoice(100);
        $invoice->update(['status' => InvoiceStatus::PAID, 'balance' => 0, 'amount_paid' => 100]);
        $link = $this->link($invoice);

        $this->get(route('public.payments.show', $link->public_token))
            ->assertOk()->assertSee(__('payments.gateway.payment_successful'));
    }

    public function test_link_initiation_creates_transaction_but_no_uhms_payment(): void
    {
        $invoice = $this->payableInvoice(100);
        $this->paymentProvider(true);
        $link = $this->link($invoice);

        $txn = app(PublicPaymentLinkService::class)->initiate($link, ['payer_phone' => '0241234567']);

        $this->assertNotNull($txn->id);
        $this->assertSame(PaymentRequestLink::STATUS_INITIATED, $link->fresh()->status);
        $this->assertSame(0, Payment::where('invoice_id', $invoice->id)->count());
    }

    public function test_verified_link_creates_one_uhms_payment_and_refresh_does_not_duplicate(): void
    {
        $invoice = $this->payableInvoice(100);
        $this->paymentProvider(true);
        $link = $this->link($invoice);

        app(PublicPaymentLinkService::class)->initiate($link, ['payer_phone' => '0241234567']);
        app(PublicPaymentLinkService::class)->recheck($link->fresh());
        app(PublicPaymentLinkService::class)->recheck($link->fresh()); // duplicate refresh

        $this->assertSame(1, Payment::where('invoice_id', $invoice->id)->count());
    }

    public function test_receipt_page_only_after_verified_payment(): void
    {
        $invoice = $this->payableInvoice(100);
        $this->paymentProvider(true);
        $link = $this->link($invoice);
        app(PublicPaymentLinkService::class)->initiate($link, ['payer_phone' => '0241234567']);

        // Before verification → redirected to status.
        $this->get(route('public.payments.receipt', $link->public_token))
            ->assertRedirect(route('public.payments.status', $link->public_token));

        app(PublicPaymentLinkService::class)->recheck($link->fresh());

        $this->get(route('public.payments.receipt', $link->public_token))->assertOk()->assertSee(__('payments.receipt'));
    }

    public function test_module_middleware_blocks_public_portal_when_disabled(): void
    {
        Module::updateOrCreate(['slug' => 'payment_gateway'], ['name' => 'Payment Gateway', 'is_core' => false, 'is_enabled' => false]);
        app(ModuleService::class)->flush();
        $invoice = $this->payableInvoice(100);
        $link = $this->link($invoice);

        $this->get(route('public.payments.show', $link->public_token))->assertForbidden();
    }

    /* ── SMS workflows ──────────────────────────────────────────────── */

    public function test_payment_request_sms_includes_payment_link_when_enabled(): void
    {
        $this->actingAs($this->userWith(['integrations.payments.view', 'integrations.payments.request_links.manage']));
        $invoice = $this->payableInvoice(100);
        $this->smsProvider(true);
        app(SmsEventSettingsService::class)->set('enable_payment_request_sms', true);

        $this->post(route('admin.integrations.payments.invoices.payment-request-sms', $invoice), ['phone' => '0241234567'])
            ->assertRedirect();

        $message = \App\Models\SmsMessage::latest()->first();
        $this->assertNotNull($message);
        $this->assertStringContainsString('/pay/', $message->message_body);
    }

    public function test_receipt_sms_queues_after_verified_payment_when_enabled(): void
    {
        $this->actingAs($this->userWith());
        $invoice = $this->payableInvoice(100);
        $this->smsProvider(true);
        $this->paymentProvider(true);
        app(SmsEventSettingsService::class)->set('enable_receipt_sms', true);

        $txn = app(PaymentGatewayService::class)->initiate([
            'invoice_id' => $invoice->id, 'amount' => 100, 'currency' => 'GHS',
            'payment_method' => 'mtn_momo', 'payer_phone' => '0241234567',
        ]);
        app(PaymentGatewayService::class)->verify($txn);

        $this->assertTrue(SmsNotificationEvent::where('event_type', 'payment_receipt')->where('status', 'sent')->exists());
    }

    public function test_appointment_reminder_command_respects_toggle(): void
    {
        $this->smsProvider(true);
        // toggle disabled by default
        $this->artisan('integrations:sms-send-appointment-reminders', ['--date' => now()->addDay()->toDateString()])
            ->assertExitCode(0);
        $this->assertSame(0, \App\Models\SmsMessage::count());
    }

    public function test_queue_sms_hook_respects_toggle(): void
    {
        $this->actingAs($this->userWith());
        $this->smsProvider(true);
        // enable_queue_sms disabled by default
        $event = app(SmsNotificationEventService::class)->queueNotification(1, '0241234567', ['queue_number' => 'A5']);

        $this->assertSame('skipped', $event->status);
        $this->assertSame(0, \App\Models\SmsMessage::count());
    }

    /* ── Scheduler + go-live ────────────────────────────────────────── */

    public function test_scheduler_status_page_is_permission_protected(): void
    {
        $this->actingAs($this->userWith())
            ->get(route('admin.integrations.scheduler.index'))->assertForbidden();

        $this->actingAs($this->userWith(['integrations.scheduler.view']))
            ->get(route('admin.integrations.scheduler.index'))->assertOk();
    }

    public function test_golive_checklist_is_created_for_provider(): void
    {
        $this->actingAs($this->userWith());
        $provider = $this->paymentProvider(false);

        $checklist = app(ProviderGoLiveChecklistService::class)->forProvider($provider);

        $this->assertSame(count(ProviderGoLiveChecklistService::DEFAULT_ITEMS), $checklist->items->count());
        $this->assertTrue(ActivityLog::where('event', 'PROVIDER_GOLIVE_CHECKLIST_CREATED')->exists());
    }

    public function test_live_activation_blocked_when_checklist_incomplete(): void
    {
        $this->actingAs($this->userWith());
        $provider = $this->paymentProvider(false, ['environment' => 'live']);

        try {
            app(IntegrationProviderService::class)->activate($provider);
            $this->fail('Expected IntegrationException.');
        } catch (IntegrationException $e) {
            // expected
        }

        $this->assertFalse($provider->fresh()->is_active);
        $this->assertTrue(ActivityLog::where('event', 'PROVIDER_LIVE_ACTIVATION_BLOCKED')->exists());
    }

    public function test_live_activation_override_is_audited(): void
    {
        $this->actingAs($this->userWith());
        $provider = $this->paymentProvider(false, ['environment' => 'live']);

        app(IntegrationProviderService::class)->activate($provider, true, 'Emergency go-live approved by CFO');

        $this->assertTrue($provider->fresh()->is_active);
        $this->assertTrue(ActivityLog::where('event', 'PROVIDER_GOLIVE_OVERRIDE_USED')->exists());
    }

    public function test_golive_waiver_requires_permission_and_reason(): void
    {
        $provider = $this->paymentProvider(false);
        $this->actingAs($this->userWith());
        $checklist = app(ProviderGoLiveChecklistService::class)->forProvider($provider);

        // Without manage permission → forbidden.
        $this->actingAs($this->userWith())
            ->post(route('admin.integrations.golive.items.update', $checklist), [
                'item_key' => 'test_connection_passed', 'status' => 'waived',
            ])->assertForbidden();

        // With permission but no waiver reason → validation error.
        $this->actingAs($this->userWith(['integrations.payments.golive.manage']))
            ->post(route('admin.integrations.golive.items.update', $checklist), [
                'item_key' => 'test_connection_passed', 'status' => 'waived',
            ])->assertSessionHasErrors('waiver_reason');
    }

    /* ── Refund bridge ──────────────────────────────────────────────── */

    public function test_provider_refund_links_to_approved_credit_note(): void
    {
        $this->actingAs($this->userWith());
        $invoice = $this->payableInvoice(100);
        $this->paymentProvider(true, ['supports_refund' => true]);
        $txn = app(PaymentGatewayService::class)->initiate([
            'invoice_id' => $invoice->id, 'amount' => 100, 'currency' => 'GHS',
            'payment_method' => 'mtn_momo', 'payer_phone' => '0241234567',
        ]);
        $txn = app(PaymentGatewayService::class)->verify($txn);

        $creditNote = CreditNote::create([
            'credit_note_number' => 'CN' . uniqid(), 'invoice_id' => $invoice->id, 'patient_id' => $invoice->patient_id,
            'type' => CreditNoteType::CREDIT_NOTE, 'status' => 'issued', 'amount' => 100, 'reason' => 'Refund',
        ]);

        $refund = app(PaymentRefundBridgeService::class)->prepare($txn->fresh(), 100.0, 'refund', null, $creditNote->id);

        $this->assertSame($creditNote->id, $refund->uhms_credit_note_id);
        $this->assertTrue(ActivityLog::where('event', 'PAYMENT_REFUND_BRIDGE_LINKED')->exists());
    }

    public function test_unsupported_provider_refund_marks_manual_required(): void
    {
        $this->actingAs($this->userWith());
        $invoice = $this->payableInvoice(100);
        $this->paymentProvider(true, ['supports_refund' => false]);
        $txn = app(PaymentGatewayService::class)->initiate([
            'invoice_id' => $invoice->id, 'amount' => 100, 'currency' => 'GHS',
            'payment_method' => 'mtn_momo', 'payer_phone' => '0241234567',
        ]);
        $txn = app(PaymentGatewayService::class)->verify($txn);

        $refund = app(PaymentRefundBridgeService::class)->prepare($txn->fresh(), 100.0, 'refund');

        $this->assertTrue((bool) data_get($refund->metadata_snapshot, 'manual_required'));
        $this->assertTrue(ActivityLog::where('event', 'PAYMENT_REFUND_MANUAL_REQUIRED')->exists());
    }

    /* ── Export + localisation ──────────────────────────────────────── */

    public function test_payment_reconciliation_export_requires_permission(): void
    {
        $this->actingAs($this->userWith(['integrations.payments.view']))
            ->get(route('admin.integrations.payments.reconciliation.export'))->assertForbidden();

        $this->actingAs($this->userWith(['integrations.payments.view', 'integrations.payments.reconciliation.view']))
            ->get(route('admin.integrations.payments.reconciliation.export'))->assertOk();
    }

    public function test_localisation_keys_exist_in_both_locales(): void
    {
        foreach (['en', 'fr'] as $locale) {
            $this->assertTrue(Lang::has('payments.gateway.public_payment', $locale), "public_payment missing in {$locale}");
            $this->assertTrue(Lang::has('integrations.go_live_checklist', $locale), "go_live_checklist missing in {$locale}");
            $this->assertTrue(Lang::has('integrations.scheduler_status', $locale), "scheduler_status missing in {$locale}");
            $this->assertTrue(Lang::has('integrations.golive_items.test_connection_passed', $locale), "golive item missing in {$locale}");
        }
    }
}
