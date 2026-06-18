<?php

namespace Tests\Feature\Integrations;

use App\Enums\InvoiceStatus;
use App\Exceptions\Integrations\IntegrationException;
use App\Jobs\Integrations\SendSmsMessageJob;
use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\IntegrationProvider;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Module;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\PaymentProviderCallback;
use App\Models\PaymentRequestLink;
use App\Models\SmsDeliveryReport;
use App\Models\SmsMessage;
use App\Models\SmsNotificationEvent;
use App\Models\User;
use App\Models\Visit;
use App\Services\Integrations\IntegrationProviderService;
use App\Services\Integrations\Payment\PaymentCallbackService;
use App\Services\Integrations\Payment\PaymentGatewayService;
use App\Services\Integrations\Payment\PaymentRefundBridgeService;
use App\Services\Integrations\Payment\PaymentRequestLinkService;
use App\Services\Integrations\Sms\SmsEventSettingsService;
use App\Services\Integrations\Sms\SmsGatewayService;
use App\Services\Integrations\Sms\SmsNotificationEventService;
use App\Services\Integrations\Sms\SmsStatusReconciliationService;
use App\Services\Integrations\Sms\SmsTemplateRenderer;
use App\Services\ModuleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class IntegrationsPhase2Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function userWith(array $permissions = []): User
    {
        $role = Role::findOrCreate('P2Role-' . uniqid(), 'web');
        foreach ($permissions as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $user = User::factory()->create();
        $user->assignRole($role);
        return $user;
    }

    private function smsProvider(bool $active = true, array $overrides = []): IntegrationProvider
    {
        return IntegrationProvider::create(array_merge([
            'module_type' => 'sms', 'code' => 'fake_sms', 'name' => 'Fake SMS',
            'environment' => 'sandbox', 'status' => $active ? 'active' : 'draft', 'is_active' => $active,
        ], IntegrationProviderService::capabilityFlags('sms', 'fake_sms'), $overrides));
    }

    private function paymentProvider(bool $active = true, array $overrides = []): IntegrationProvider
    {
        return IntegrationProvider::create(array_merge([
            'module_type' => 'payment', 'code' => 'fake_payment', 'name' => 'Fake Payment',
            'environment' => 'sandbox', 'status' => $active ? 'active' : 'draft', 'is_active' => $active,
        ], IntegrationProviderService::capabilityFlags('payment', 'fake_payment'), $overrides));
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

    private function initiatedTxn(Invoice $invoice, array $meta = [])
    {
        $this->actingAs($this->userWith());
        $this->paymentProvider(true);
        return app(PaymentGatewayService::class)->initiate([
            'invoice_id' => $invoice->id, 'amount' => (float) $invoice->balance, 'currency' => 'GHS',
            'payment_method' => 'mtn_momo', 'payer_phone' => '0241234567', 'metadata' => $meta,
        ]);
    }

    /* ── SMS queue ──────────────────────────────────────────────────── */

    public function test_sms_dispatch_can_be_queued(): void
    {
        Queue::fake();
        config(['queue.default' => 'database']);
        $this->actingAs($this->userWith());
        $this->smsProvider(true);

        app(SmsGatewayService::class)->send(['body' => 'Hi', 'recipients' => [['phone' => '0241234567']]]);

        Queue::assertPushed(SendSmsMessageJob::class);
    }

    public function test_queued_sms_job_updates_recipient_statuses(): void
    {
        $this->actingAs($this->userWith());
        $provider = $this->smsProvider(true);
        $message = SmsMessage::create([
            'message_uuid' => (string) Str::uuid(), 'provider_id' => $provider->id,
            'message_body' => 'Hi', 'message_type' => 'manual', 'status' => SmsMessage::STATUS_QUEUED,
        ]);
        $message->recipients()->create(['phone_number' => '0241234567', 'normalized_phone_number' => '233241234567', 'status' => 'queued']);

        (new SendSmsMessageJob($message->id))->handle(app(SmsGatewayService::class));

        $this->assertSame(SmsMessage::STATUS_SENT, $message->fresh()->status);
        $this->assertSame('sent', $message->recipients()->first()->status);
    }

    public function test_sms_retry_does_not_duplicate_delivered_recipients(): void
    {
        $this->actingAs($this->userWith());
        $provider = $this->smsProvider(true);
        $message = SmsMessage::create([
            'message_uuid' => (string) Str::uuid(), 'provider_id' => $provider->id,
            'message_body' => 'Hi', 'message_type' => 'manual', 'status' => SmsMessage::STATUS_PARTIALLY_SENT,
        ]);
        $delivered = $message->recipients()->create([
            'phone_number' => '0240000001', 'normalized_phone_number' => '233240000001',
            'status' => 'delivered', 'provider_message_id' => 'KEEP-ME', 'sent_at' => now()->subHour(),
        ]);
        $failed = $message->recipients()->create([
            'phone_number' => '0240000002', 'normalized_phone_number' => '233240000002', 'status' => 'failed',
        ]);

        app(SmsGatewayService::class)->retry($message);

        $this->assertSame('delivered', $delivered->fresh()->status);
        $this->assertSame('KEEP-ME', $delivered->fresh()->provider_message_id, 'Delivered recipient must not be re-sent.');
        $this->assertSame('sent', $failed->fresh()->status);
    }

    public function test_sms_status_reconciliation_is_idempotent(): void
    {
        $this->actingAs($this->userWith());
        $provider = $this->smsProvider(true); // fake_sms supports_status_check = true
        $message = SmsMessage::create([
            'message_uuid' => (string) Str::uuid(), 'provider_id' => $provider->id,
            'message_body' => 'Hi', 'message_type' => 'manual', 'status' => SmsMessage::STATUS_SENT,
        ]);
        $message->recipients()->create([
            'phone_number' => '0241234567', 'normalized_phone_number' => '233241234567',
            'status' => 'sent', 'provider_message_id' => 'REC-1',
        ]);

        app(SmsStatusReconciliationService::class)->reconcile(['limit' => 50]);
        app(SmsStatusReconciliationService::class)->reconcile(['limit' => 50]);

        $this->assertSame(1, SmsDeliveryReport::where('provider_message_id', 'REC-1')->count());
        $this->assertSame('delivered', $message->recipients()->first()->status);
    }

    public function test_sms_template_placeholder_validation_works(): void
    {
        $renderer = app(SmsTemplateRenderer::class);
        $this->assertSame(['evil'], $renderer->unknownPlaceholders('Hi {{patient_name}} {{evil}}'));

        $this->expectException(IntegrationException::class);
        $renderer->render('Hi {{evil}}', [], strict: true);
    }

    /* ── Automatic SMS events ───────────────────────────────────────── */

    public function test_automatic_event_is_skipped_when_toggle_disabled(): void
    {
        $this->actingAs($this->userWith());
        $this->smsProvider(true);
        // toggle defaults disabled
        $event = app(SmsNotificationEventService::class)->dispatch([
            'event_type' => SmsNotificationEvent::TYPE_INVOICE_PAYMENT_REQUEST,
            'source_type' => 'invoice', 'source_id' => 1, 'phone' => '0241234567', 'body' => 'Pay please',
        ]);

        $this->assertSame('skipped', $event->status);
        $this->assertSame('event_disabled', $event->error_message);
        $this->assertSame(0, SmsMessage::count());
    }

    public function test_payment_request_sms_creates_event_when_enabled(): void
    {
        $this->actingAs($this->userWith());
        $this->smsProvider(true);
        app(SmsEventSettingsService::class)->set('enable_payment_request_sms', true);

        $event = app(SmsNotificationEventService::class)->dispatch([
            'event_type' => SmsNotificationEvent::TYPE_INVOICE_PAYMENT_REQUEST,
            'source_type' => 'invoice', 'source_id' => 999, 'phone' => '0241234567', 'body' => 'Pay {{invoice_number}}',
            'data' => ['invoice_number' => 'INV-1'],
        ]);

        $this->assertSame('sent', $event->status);
        $this->assertNotNull($event->sms_message_id);
        $this->assertSame(1, SmsMessage::count());
    }

    /* ── Payment reconciliation + recheck ───────────────────────────── */

    public function test_reconciliation_dashboard_is_permission_protected(): void
    {
        $this->actingAs($this->userWith(['integrations.payments.view']))
            ->get(route('admin.integrations.payments.reconciliation.index'))
            ->assertForbidden();

        $this->actingAs($this->userWith(['integrations.payments.view', 'integrations.payments.reconciliation.view']))
            ->get(route('admin.integrations.payments.reconciliation.index'))
            ->assertOk();
    }

    public function test_pending_transaction_can_be_manually_rechecked_via_route(): void
    {
        $invoice = $this->payableInvoice(100);
        $txn = $this->initiatedTxn($invoice);

        $this->actingAs($this->userWith(['integrations.payments.view', 'integrations.payments.reconciliation.verify']))
            ->post(route('admin.integrations.payments.reconciliation.recheck', $txn))
            ->assertRedirect();

        $this->assertSame(1, Payment::where('invoice_id', $invoice->id)->count());
        $this->assertNotNull($txn->fresh()->uhms_payment_id);
    }

    public function test_stale_pending_command_verifies_safely(): void
    {
        $invoice = $this->payableInvoice(100);
        $this->initiatedTxn($invoice);

        $this->artisan('integrations:payments-recheck-pending', ['--limit' => 50])->assertExitCode(0);

        $this->assertSame(1, Payment::where('invoice_id', $invoice->id)->count());
    }

    public function test_duplicate_recheck_does_not_duplicate_payment(): void
    {
        $invoice = $this->payableInvoice(100);
        $txn = $this->initiatedTxn($invoice);

        app(PaymentGatewayService::class)->verify($txn);
        app(PaymentGatewayService::class)->verify($txn->fresh());

        $this->assertSame(1, Payment::where('invoice_id', $invoice->id)->count());
    }

    public function test_amount_mismatch_remains_blocked(): void
    {
        $invoice = $this->payableInvoice(100);
        $txn = $this->initiatedTxn($invoice, ['fake_force_amount' => 40]);

        app(PaymentGatewayService::class)->verify($txn);

        $this->assertSame(0, Payment::where('invoice_id', $invoice->id)->count());
        $this->assertSame('failed', $txn->fresh()->status);
    }

    /* ── Payment request links ──────────────────────────────────────── */

    public function test_payment_request_link_can_be_created(): void
    {
        $this->actingAs($this->userWith());
        $invoice = $this->payableInvoice(100);

        $link = app(PaymentRequestLinkService::class)->create(['invoice_id' => $invoice->id, 'amount' => 100]);

        $this->assertSame('active', $link->status);
        $this->assertNotEmpty($link->link_uuid);
        $this->assertTrue(ActivityLog::where('event', 'PAYMENT_REQUEST_LINK_CREATED')->exists());
    }

    public function test_expired_payment_request_link_cannot_initiate(): void
    {
        $this->actingAs($this->userWith());
        $link = PaymentRequestLink::create([
            'link_uuid' => (string) Str::uuid(), 'amount' => 50, 'currency' => 'GHS',
            'status' => PaymentRequestLink::STATUS_ACTIVE, 'expires_at' => now()->subDay(),
        ]);

        $this->expectException(IntegrationException::class);
        app(PaymentRequestLinkService::class)->initiate($link, ['payer_phone' => '0241234567']);
    }

    /* ── Refund bridge ──────────────────────────────────────────────── */

    public function test_refund_bridge_blocks_over_refund(): void
    {
        $invoice = $this->payableInvoice(100);
        $txn = $this->initiatedTxn($invoice);
        $txn = app(PaymentGatewayService::class)->verify($txn);

        $this->expectException(IntegrationException::class);
        app(PaymentRefundBridgeService::class)->prepare($txn->fresh(), 150.0, 'too much');
    }

    public function test_unsupported_provider_refund_is_retained_visibly(): void
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

        $this->assertSame('failed', $refund->status);
        $this->assertTrue((bool) data_get($refund->metadata_snapshot, 'unsupported'));
        $this->assertTrue(ActivityLog::where('event', 'PAYMENT_REFUND_PROVIDER_UNSUPPORTED')->exists());
    }

    /* ── Webhook signature hardening ────────────────────────────────── */

    public function test_webhook_signature_failure_does_not_create_payment(): void
    {
        $this->actingAs($this->userWith());
        $invoice = $this->payableInvoice(100);
        $this->paymentProvider(true, [
            'supports_status_check' => false, 'require_signature' => true, 'allow_unsigned_sandbox_callbacks' => false,
        ]);
        $txn = app(PaymentGatewayService::class)->initiate([
            'invoice_id' => $invoice->id, 'amount' => 100, 'currency' => 'GHS',
            'payment_method' => 'mtn_momo', 'payer_phone' => '0241234567',
        ]);

        app(PaymentCallbackService::class)->handle('fake_payment', [
            'payment_reference' => $txn->payment_reference, 'status' => 'paid', 'amount' => 100,
        ]);

        $this->assertSame(0, Payment::where('invoice_id', $invoice->id)->count());
        $callback = PaymentProviderCallback::where('provider_code', 'fake_payment')->latest()->first();
        $this->assertSame('signature_invalid', $callback->processing_error);
    }

    /* ── Module gating + audit + localisation ───────────────────────── */

    public function test_module_middleware_blocks_disabled_payment_reconciliation(): void
    {
        Module::updateOrCreate(['slug' => 'payment_gateway'], ['name' => 'Payment Gateway', 'is_core' => false, 'is_enabled' => false]);
        app(ModuleService::class)->flush();

        $this->actingAs($this->userWith(['integrations.payments.view', 'integrations.payments.reconciliation.view']))
            ->get(route('admin.integrations.payments.reconciliation.index'))
            ->assertForbidden();
    }

    public function test_localisation_keys_exist_in_both_locales(): void
    {
        foreach (['en', 'fr'] as $locale) {
            $this->assertTrue(Lang::has('sms.sms_queue', $locale), "sms.sms_queue missing in {$locale}");
            $this->assertTrue(Lang::has('sms.automatic_sms_events', $locale), "automatic_sms_events missing in {$locale}");
            $this->assertTrue(Lang::has('payments.gateway.payment_reconciliation', $locale), "payment_reconciliation missing in {$locale}");
            $this->assertTrue(Lang::has('payments.gateway.payment_request_links', $locale), "payment_request_links missing in {$locale}");
            $this->assertTrue(Lang::has('integrations.errors.refund_over_amount', $locale), "refund_over_amount missing in {$locale}");
        }
    }
}
