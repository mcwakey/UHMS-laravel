<?php

use App\Services\Integrations\Providers\Payment\FakePaymentProvider;
use App\Services\Integrations\Providers\Payment\MtnMomoPaymentProvider;
use App\Services\Integrations\Providers\Payment\NaloPaymentProvider;
use App\Services\Integrations\Providers\Sms\FakeSmsProvider;
use App\Services\Integrations\Providers\Sms\NaloSmsProvider;

/*
|--------------------------------------------------------------------------
| UHMS External Integrations — SMS & Payment Gateways (Phase 1)
|--------------------------------------------------------------------------
|
| Provider-agnostic configuration for the SMS Gateway and Payment Gateway
| modules. Provider CREDENTIALS are never stored here — they live encrypted
| in integration_provider_credentials. This file only declares which adapter
| class implements each provider code and the safe operational knobs.
|
*/

return [

    // Shared outbound HTTP timeout (seconds) for all provider adapters.
    'http_timeout' => (int) env('INTEGRATIONS_HTTP_TIMEOUT', 20),

    // Optional comma-separated IP allow-list for inbound provider callbacks.
    // Empty = allow any source (signature/secret verification still applies).
    'callback_ip_allowlist' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('INTEGRATIONS_CALLBACK_IP_ALLOWLIST', ''))
    ))),

    // Fake providers exist for local/dev/testing only. They must never be
    // selectable in production unless this flag is explicitly enabled.
    'allow_fake_providers' => (bool) env(
        'INTEGRATIONS_ALLOW_FAKE_PROVIDERS',
        env('APP_ENV') !== 'production'
    ),

    'default_currency' => env('INTEGRATIONS_DEFAULT_CURRENCY', 'GHS'),

    /*
    | Provider registry: code => adapter class. The registry is the single
    | source of truth that maps a stored provider row to the adapter that
    | knows how to talk to that provider's API. Add new providers here.
    */
    'providers' => [
        'sms' => [
            'nalo_sms' => [
                'adapter' => NaloSmsProvider::class,
                'label' => 'Nalo Solutions SMS',
                'is_fake' => false,
                'sandbox_url' => 'https://sms.nalosolutions.com/smsbackend/clientapi/Resl_Nalo/send-message/',
                'live_url' => 'https://sms.nalosolutions.com/smsbackend/clientapi/Resl_Nalo/send-message/',
            ],
            'fake_sms' => [
                'adapter' => FakeSmsProvider::class,
                'label' => 'Fake SMS (testing)',
                'is_fake' => true,
                'sandbox_url' => null,
                'live_url' => null,
            ],
        ],
        'payment' => [
            'mtn_momo' => [
                'adapter' => MtnMomoPaymentProvider::class,
                'label' => 'MTN Mobile Money',
                'is_fake' => false,
                'sandbox_url' => 'https://sandbox.momodeveloper.mtn.com',
                'live_url' => 'https://proxy.momoapi.mtn.com',
            ],
            'nalo_payment' => [
                'adapter' => NaloPaymentProvider::class,
                'label' => 'Nalo Solutions Payments',
                'is_fake' => false,
                'sandbox_url' => 'https://api.nalosolutions.com/payplus/api/',
                'live_url' => 'https://api.nalosolutions.com/payplus/api/',
            ],
            'fake_payment' => [
                'adapter' => FakePaymentProvider::class,
                'label' => 'Fake Payment (testing)',
                'is_fake' => true,
                'sandbox_url' => null,
                'live_url' => null,
            ],
        ],
    ],

    // Automatic SMS event toggles. ALL default to disabled — automatic SMS
    // is opt-in. Manual test SMS is always available to authorised admins.
    'sms_events' => [
        'payment_request' => (bool) env('SMS_EVENT_PAYMENT_REQUEST', false),
        'receipt' => (bool) env('SMS_EVENT_RECEIPT', false),
        'appointment_reminder' => (bool) env('SMS_EVENT_APPOINTMENT_REMINDER', false),
        'queue' => (bool) env('SMS_EVENT_QUEUE', false),
        'admin_alert' => (bool) env('SMS_EVENT_ADMIN_ALERT', false),
    ],

    // Safe, minimal default bodies for automatic SMS events (placeholders are
    // resolved by SmsTemplateRenderer — never include clinical detail). Operators
    // should create proper SMS templates; these are conservative fallbacks.
    'sms_default_bodies' => [
        'invoice_payment_request' => 'Dear {{patient_name}}, invoice {{invoice_number}} of {{currency}} {{amount}} is awaiting payment. Pay: {{payment_link}} — {{hospital_name}}.',
        'payment_receipt' => 'Payment of {{currency}} {{amount}} received. Receipt {{receipt_number}}. Thank you, {{hospital_name}}.',
        'appointment_reminder' => 'Reminder: you have an appointment on {{appointment_date}} at {{appointment_time}}. {{hospital_name}}.',
        'queue_notification' => 'Your queue number is {{queue_number}}. {{hospital_name}}.',
    ],

    // Default country dialling context for phone normalisation. Numbers are
    // never assumed to be Ghanaian — this only seeds the local-prefix rule.
    'phone' => [
        'default_country_code' => env('INTEGRATIONS_DEFAULT_COUNTRY_CODE', '233'),
        'min_digits' => 9,
        'max_digits' => 15,
    ],
];
