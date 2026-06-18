<?php

namespace Tests\Feature\Integrations;

use App\Enums\InvoiceStatus;
use App\Models\ActivityLog;
use App\Models\IntegrationProvider;
use App\Models\IntegrationProviderCredential;
use App\Models\Department;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Module;
use App\Models\Patient;
use App\Models\Visit;
use App\Models\Payment;
use App\Models\PaymentProviderCallback;
use App\Models\SmsDeliveryReport;
use App\Models\SmsMessage;
use App\Models\User;
use App\Services\Integrations\IntegrationCredentialService;
use App\Services\Integrations\IntegrationProviderService;
use App\Services\Integrations\Payment\PaymentCallbackService;
use App\Services\Integrations\Payment\PaymentGatewayService;
use App\Services\Integrations\Sms\SmsCallbackService;
use App\Services\Integrations\Sms\SmsGatewayService;
use App\Services\Integrations\Providers\Payment\MtnMomoPaymentProvider;
use App\Services\Integrations\Providers\Sms\NaloSmsProvider;
use App\Services\ModuleService;
use App\Support\Integrations\Payment\PaymentInitiationRequest;
use App\Support\Integrations\Sms\SmsSendRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class IntegrationsGatewayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /* ── helpers ────────────────────────────────────────────────────── */

    private function userWith(array $permissions = []): User
    {
        $role = Role::findOrCreate('IntegRole-' . uniqid(), 'web');
        foreach ($permissions as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function smsProvider(string $code = 'fake_sms', bool $active = false, array $overrides = []): IntegrationProvider
    {
        return IntegrationProvider::create(array_merge([
            'module_type' => 'sms',
            'code' => $code,
            'name' => 'SMS ' . $code,
            'environment' => 'sandbox',
            'status' => $active ? 'active' : 'draft',
            'is_active' => $active,
        ], IntegrationProviderService::capabilityFlags('sms', $code), $overrides));
    }

    private function paymentProvider(string $code = 'fake_payment', bool $active = false, array $overrides = []): IntegrationProvider
    {
        return IntegrationProvider::create(array_merge([
            'module_type' => 'payment',
            'code' => $code,
            'name' => 'PAY ' . $code,
            'environment' => 'sandbox',
            'status' => $active ? 'active' : 'draft',
            'is_active' => $active,
        ], IntegrationProviderService::capabilityFlags('payment', $code), $overrides));
    }

    private function payableInvoice(float $amount = 100.0): Invoice
    {
        // PaymentRecorded → NotifyAccountants notifies the standard Accountant role,
        // which exists in a seeded app; ensure it exists for the notifier here too.
        Role::findOrCreate('Accountant', 'web');

        $dept = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $dept->id]);
        $patient = Patient::factory()->create(['registered_by' => $user->id]);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'current_department_id' => $dept->id,
            'created_by' => $user->id,
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV' . uniqid(),
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'billing_type' => 'cash',
            'subtotal' => $amount,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'nhis_amount' => 0,
            'total_amount' => $amount,
            'amount_paid' => 0,
            'balance' => $amount,
            'status' => InvoiceStatus::PENDING,
            'created_by' => $user->id,
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'description' => 'Service',
            'quantity' => 1,
            'created_by' => $user->id,
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
        ]);

        return $invoice->load('items');
    }

    /* ── Provider activation rule ───────────────────────────────────── */

    public function test_only_one_sms_provider_can_be_active_at_a_time(): void
    {
        $this->actingAs($this->userWith());
        $a = $this->smsProvider('fake_sms', true);
        $b = $this->smsProvider('nalo_sms');

        app(IntegrationProviderService::class)->activate($b);

        $this->assertFalse($a->fresh()->is_active);
        $this->assertTrue($b->fresh()->is_active);
        $this->assertSame(1, IntegrationProvider::sms()->where('is_active', true)->count());
    }

    public function test_only_one_payment_provider_can_be_active_at_a_time(): void
    {
        $this->actingAs($this->userWith());
        $a = $this->paymentProvider('fake_payment', true);
        $b = $this->paymentProvider('mtn_momo');

        app(IntegrationProviderService::class)->activate($b);

        $this->assertFalse($a->fresh()->is_active);
        $this->assertTrue($b->fresh()->is_active);
        $this->assertSame(1, IntegrationProvider::payment()->where('is_active', true)->count());
    }

    /* ── Credentials ────────────────────────────────────────────────── */

    public function test_provider_credentials_are_encrypted_and_masked(): void
    {
        $provider = $this->smsProvider('nalo_sms');
        app(IntegrationCredentialService::class)->upsert($provider, ['api_key' => 'super-secret-123']);

        $raw = DB::table('integration_provider_credentials')
            ->where('integration_provider_id', $provider->id)
            ->where('credential_key', 'api_key')
            ->value('encrypted_value');

        $this->assertNotSame('super-secret-123', $raw, 'Credential must not be stored in plaintext.');
        $this->assertSame('super-secret-123', IntegrationProviderCredential::where('integration_provider_id', $provider->id)
            ->where('credential_key', 'api_key')->first()->encrypted_value);

        $masked = app(IntegrationCredentialService::class)->maskedMap($provider);
        $this->assertArrayHasKey('api_key', $masked);
        $this->assertStringNotContainsString('super-secret', $masked['api_key']);
    }

    public function test_unauthorized_user_cannot_manage_provider_credentials(): void
    {
        $provider = $this->smsProvider('nalo_sms');

        $this->actingAs($this->userWith(['integrations.sms.view']))
            ->put(route('admin.integrations.sms.providers.credentials.update', $provider), [
                'credentials' => ['api_key' => 'x'],
            ])
            ->assertForbidden();
    }

    /* ── SMS sending ────────────────────────────────────────────────── */

    public function test_fake_sms_provider_sends_and_creates_records(): void
    {
        $this->actingAs($this->userWith());
        $this->smsProvider('fake_sms', true);

        $message = app(SmsGatewayService::class)->send([
            'body' => 'Hello',
            'recipients' => [['phone' => '0241234567'], ['phone' => '0209876543']],
        ]);

        $this->assertSame(SmsMessage::STATUS_SENT, $message->fresh()->status);
        $this->assertSame(2, $message->recipients()->count());
        $this->assertSame(2, $message->recipients()->where('status', 'sent')->count());
    }

    public function test_sms_failure_is_retained_and_visible(): void
    {
        $this->actingAs($this->userWith());
        // Nalo with no base URL / credentials → guaranteed failure, retained.
        $this->smsProvider('nalo_sms', true, ['base_url' => null]);

        $message = app(SmsGatewayService::class)->send([
            'body' => 'Hello',
            'recipients' => [['phone' => '0241234567']],
        ]);

        $this->assertSame(SmsMessage::STATUS_FAILED, $message->fresh()->status);
        $this->assertSame(1, $message->recipients()->where('status', 'failed')->count());
        $this->assertNotNull($message->recipients()->first()->error_code);
    }

    public function test_nalo_sms_adapter_normalizes_request_through_interface(): void
    {
        $provider = $this->smsProvider('nalo_sms', true, ['base_url' => null]);
        $adapter = new NaloSmsProvider($provider, []); // no credentials

        $result = $adapter->send(new SmsSendRequest('Body', [['phone' => '233241234567', 'recipient_id' => null, 'name' => null]]));

        $this->assertFalse($result->success);
        $this->assertArrayHasKey('233241234567', $result->perRecipient);
        $this->assertSame('failed', $result->perRecipient['233241234567']['status']);
    }

    public function test_sms_delivery_callback_is_idempotent(): void
    {
        $this->actingAs($this->userWith());
        $provider = $this->smsProvider('fake_sms', true);
        $message = SmsMessage::create([
            'message_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'provider_id' => $provider->id,
            'message_body' => 'Hi',
            'message_type' => 'manual',
            'status' => SmsMessage::STATUS_SENT,
        ]);
        $recipient = $message->recipients()->create([
            'phone_number' => '0241234567',
            'normalized_phone_number' => '233241234567',
            'status' => 'sent',
            'provider_message_id' => 'FAKE-XYZ',
        ]);

        $payload = ['provider_message_id' => 'FAKE-XYZ', 'status' => 'delivered'];
        app(SmsCallbackService::class)->handle('fake_sms', $payload);
        app(SmsCallbackService::class)->handle('fake_sms', $payload);

        $this->assertSame(1, SmsDeliveryReport::where('provider_message_id', 'FAKE-XYZ')->count());
        $this->assertSame('delivered', $recipient->fresh()->status);
    }

    /* ── Payment initiation + verification ──────────────────────────── */

    public function test_payment_initiation_creates_provider_transaction(): void
    {
        $this->actingAs($this->userWith());
        $this->paymentProvider('fake_payment', true);

        $txn = app(PaymentGatewayService::class)->initiate([
            'amount' => 50.0, 'currency' => 'GHS', 'payment_method' => 'mtn_momo', 'payer_phone' => '0241234567',
        ]);

        $this->assertDatabaseHas('payment_provider_transactions', ['id' => $txn->id, 'status' => 'pending']);
        $this->assertNull($txn->uhms_payment_id);
    }

    public function test_payment_is_not_marked_paid_until_verified(): void
    {
        $this->actingAs($this->userWith());
        $this->paymentProvider('fake_payment', true);
        $invoice = $this->payableInvoice(100);

        app(PaymentGatewayService::class)->initiate([
            'invoice_id' => $invoice->id, 'amount' => 100, 'currency' => 'GHS',
            'payment_method' => 'mtn_momo', 'payer_phone' => '0241234567',
        ]);

        $this->assertSame(0, Payment::where('invoice_id', $invoice->id)->count());
        $this->assertNotSame(InvoiceStatus::PAID->value, $invoice->fresh()->status->value ?? $invoice->fresh()->status);
    }

    public function test_verified_payment_creates_uhms_payment_through_service(): void
    {
        $this->actingAs($this->userWith());
        $this->paymentProvider('fake_payment', true);
        $invoice = $this->payableInvoice(100);

        $txn = app(PaymentGatewayService::class)->initiate([
            'invoice_id' => $invoice->id, 'amount' => 100, 'currency' => 'GHS',
            'payment_method' => 'mtn_momo', 'payer_phone' => '0241234567',
        ]);
        $txn = app(PaymentGatewayService::class)->verify($txn);

        $this->assertNotNull($txn->fresh()->uhms_payment_id);
        $this->assertSame(1, Payment::where('invoice_id', $invoice->id)->count());
        $this->assertSame(InvoiceStatus::PAID->value, $invoice->fresh()->status->value ?? $invoice->fresh()->status);
    }

    public function test_amount_mismatch_blocks_payment_creation(): void
    {
        $this->actingAs($this->userWith());
        $this->paymentProvider('fake_payment', true);
        $invoice = $this->payableInvoice(100);

        $txn = app(PaymentGatewayService::class)->initiate([
            'invoice_id' => $invoice->id, 'amount' => 100, 'currency' => 'GHS',
            'payment_method' => 'mtn_momo', 'payer_phone' => '0241234567',
            'metadata' => ['fake_force_amount' => 50], // provider will "report" the wrong amount
        ]);
        $txn = app(PaymentGatewayService::class)->verify($txn);

        $this->assertSame('failed', $txn->fresh()->status);
        $this->assertSame('amount_mismatch', $txn->fresh()->error_code);
        $this->assertSame(0, Payment::where('invoice_id', $invoice->id)->count());
    }

    public function test_duplicate_callback_does_not_duplicate_payment(): void
    {
        $this->actingAs($this->userWith());
        $this->paymentProvider('fake_payment', true);
        $invoice = $this->payableInvoice(100);

        $txn = app(PaymentGatewayService::class)->initiate([
            'invoice_id' => $invoice->id, 'amount' => 100, 'currency' => 'GHS',
            'payment_method' => 'mtn_momo', 'payer_phone' => '0241234567',
        ]);

        $payload = ['payment_reference' => $txn->payment_reference, 'status' => 'paid', 'amount' => 100];
        app(PaymentCallbackService::class)->handle('fake_payment', $payload);
        app(PaymentCallbackService::class)->handle('fake_payment', $payload);

        $this->assertSame(1, Payment::where('invoice_id', $invoice->id)->count());
        $this->assertTrue(ActivityLog::where('event', 'PAYMENT_TRANSACTION_DUPLICATE_CALLBACK_IGNORED')->exists());
    }

    public function test_payment_callback_is_stored(): void
    {
        $this->paymentProvider('fake_payment', true);
        $this->payableInvoice(100);

        app(PaymentCallbackService::class)->handle('fake_payment', ['payment_reference' => 'PG-X', 'status' => 'paid']);

        $this->assertSame(1, PaymentProviderCallback::where('provider_code', 'fake_payment')->count());
    }

    public function test_unknown_reference_callback_is_retained_but_not_posted(): void
    {
        $this->paymentProvider('fake_payment', true);

        app(PaymentCallbackService::class)->handle('fake_payment', ['payment_reference' => 'UNKNOWN-REF', 'status' => 'paid', 'amount' => 10]);

        $callback = PaymentProviderCallback::where('provider_code', 'fake_payment')->first();
        $this->assertNotNull($callback);
        $this->assertSame('unknown_reference', $callback->processing_error);
        $this->assertSame(0, Payment::count());
    }

    public function test_payment_callback_http_endpoint_is_public_and_stores(): void
    {
        $this->paymentProvider('fake_payment', true);

        // No auth, no CSRF token — the api route must accept the webhook.
        $this->postJson('/api/integrations/payments/fake_payment/callback', [
            'payment_reference' => 'PG-HTTP', 'status' => 'paid',
        ])->assertOk()->assertJson(['status' => 'received']);

        $this->assertSame(1, PaymentProviderCallback::where('provider_code', 'fake_payment')->count());
    }

    public function test_mtn_momo_adapter_normalizes_response_through_interface(): void
    {
        $provider = $this->paymentProvider('mtn_momo', true, ['base_url' => null]);
        $adapter = new MtnMomoPaymentProvider($provider, []); // not configured

        $result = $adapter->initiate(new PaymentInitiationRequest('PG-1', 10.0, 'GHS', payerPhone: '0241234567'));

        $this->assertFalse($result->success);
        $this->assertSame('failed', $result->status);
        $this->assertSame('not_configured', $result->errorCode);
    }

    /* ── Module gating ──────────────────────────────────────────────── */

    public function test_module_middleware_blocks_disabled_sms_gateway(): void
    {
        Module::updateOrCreate(['slug' => 'sms_gateway'], ['name' => 'SMS Gateway', 'is_core' => false, 'is_enabled' => false]);
        app(ModuleService::class)->flush();

        $this->actingAs($this->userWith(['integrations.sms.view']))
            ->get(route('admin.integrations.sms.providers.index'))
            ->assertForbidden();
    }

    public function test_module_middleware_blocks_disabled_payment_gateway(): void
    {
        Module::updateOrCreate(['slug' => 'payment_gateway'], ['name' => 'Payment Gateway', 'is_core' => false, 'is_enabled' => false]);
        app(ModuleService::class)->flush();

        $this->actingAs($this->userWith(['integrations.payments.view']))
            ->get(route('admin.integrations.payments.providers.index'))
            ->assertForbidden();
    }

    /* ── Audit + localisation ───────────────────────────────────────── */

    public function test_audit_events_are_recorded(): void
    {
        $this->actingAs($this->userWith());
        $provider = $this->paymentProvider('fake_payment');
        app(IntegrationProviderService::class)->activate($provider);

        $this->assertTrue(ActivityLog::where('event', 'PAYMENT_PROVIDER_ACTIVATED')->exists());
    }

    public function test_localisation_keys_exist_in_both_locales(): void
    {
        foreach (['en', 'fr'] as $locale) {
            $this->assertTrue(Lang::has('integrations.providers', $locale), "integrations.providers missing in {$locale}");
            $this->assertTrue(Lang::has('sms.sms_gateway', $locale), "sms.sms_gateway missing in {$locale}");
            $this->assertTrue(Lang::has('payments.gateway.payment_gateway', $locale), "payments.gateway.payment_gateway missing in {$locale}");
            $this->assertTrue(Lang::has('statuses.payment_transaction.paid', $locale), "status label missing in {$locale}");
        }
    }
}
