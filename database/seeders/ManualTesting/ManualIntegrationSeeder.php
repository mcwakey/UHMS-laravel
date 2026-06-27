<?php

namespace Database\Seeders\ManualTesting;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ManualIntegrationSeeder extends ManualTestingSeederBase
{
    public function run(): void
    {
        $this->seedProviders();
        $this->seedSms();
        $this->seedPaymentTransactions();
    }

    private function seedProviders(): void
    {
        if (! $this->hasTable('integration_providers')) {
            return;
        }

        foreach ([
            ['sms', 'mt_nalo_sms', 'Manual NaloSolutions SMS', true],
            ['payment', 'mt_mtn_momo', 'Manual MTN MoMo', true],
            ['payment', 'mt_nalo_payment', 'Manual Nalo Payment', false],
        ] as [$module, $code, $name, $active]) {
            $this->updateOrInsert('integration_providers', ['module_type' => $module, 'code' => $code], [
                'module_type' => $module,
                'code' => $code,
                'name' => $name,
                'description' => 'Manual test provider. Does not call external APIs.',
                'environment' => 'sandbox',
                'status' => $active ? 'active' : 'inactive',
                'is_active' => $active,
                'supports_send' => $module === 'sms',
                'supports_status_check' => true,
                'supports_callback' => true,
                'supports_collection' => $module === 'payment',
                'sender_id' => 'UHMS-TEST',
                'metadata_snapshot' => $this->metadata(['external_api_calls' => false]),
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);
        }
    }

    private function seedSms(): void
    {
        if (! $this->hasTable('sms_templates')) {
            return;
        }

        $creator = DB::table('users')->where('email', 'admin.manual@uhms.test')->value('id');
        foreach (['appointment_reminder', 'billing_reminder', 'lab_result_ready', 'payment_confirmation', 'emergency_alert'] as $i => $code) {
            $this->updateOrInsert('sms_templates', ['code' => 'MT_'.$code], [
                'code' => 'MT_'.$code,
                'name' => 'Manual '.str_replace('_', ' ', $code),
                'description' => 'Manual testing SMS template',
                'language' => 'en',
                'body' => 'Manual test message for '.$code.'.',
                'is_active' => true,
                'created_by' => $creator,
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);
        }

        if (! $this->hasTable('sms_messages') || $this->countManual('sms_messages', 'provider_batch_reference') > 0) {
            return;
        }

        $providerId = DB::table('integration_providers')->where('code', 'mt_nalo_sms')->value('id');
        $templateId = DB::table('sms_templates')->where('code', 'MT_appointment_reminder')->value('id');
        $rows = [];
        foreach (['queued', 'sent', 'failed', 'retried', 'delivered'] as $i => $status) {
            $rows[] = [
                'message_uuid' => (string) Str::uuid(),
                'provider_id' => $providerId,
                'template_id' => $templateId,
                'sender_id' => 'UHMS-TEST',
                'message_body' => 'Manual SMS '.$status,
                'message_type' => 'manual_test',
                'status' => $status,
                'provider_batch_reference' => $this->ref('SMS', $i + 1),
                'metadata_snapshot' => $this->metadata(['status' => $status]),
                'created_by' => $creator,
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ];
        }
        $this->insert('sms_messages', $rows);
    }

    private function seedPaymentTransactions(): void
    {
        if (! $this->hasTable('payment_provider_transactions') || $this->countManual('payment_provider_transactions', 'payment_reference') > 0) {
            return;
        }

        $providerId = DB::table('integration_providers')->where('code', 'mt_mtn_momo')->value('id');
        $invoices = DB::table('invoices')->where('invoice_number', 'like', 'MT-BILL-%')->select('id', 'visit_id', 'patient_id', 'total_amount')->limit(40)->get();
        $rows = [];
        foreach ($invoices as $index => $invoice) {
            $status = ['successful', 'failed', 'pending', 'reversed', 'expired'][$index % 5];
            $rows[] = [
                'transaction_uuid' => (string) Str::uuid(),
                'provider_id' => $providerId,
                'provider_code' => 'mt_mtn_momo',
                'payment_reference' => $this->ref('MOMO', $index + 1),
                'provider_transaction_id' => 'PROV-'.$this->ref('MOMO', $index + 1),
                'invoice_id' => $invoice->id,
                'visit_id' => $invoice->visit_id,
                'patient_id' => $invoice->patient_id,
                'payer_name' => 'Manual Payer',
                'payer_phone' => '0550000000',
                'amount' => $invoice->total_amount,
                'currency' => 'GHS',
                'payment_method' => 'mtn_momo',
                'status' => $status,
                'provider_status' => strtoupper($status),
                'initiated_at' => now()->subDays($index),
                'metadata_snapshot' => $this->metadata(['money_movement' => false]),
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ];
        }
        $this->insert('payment_provider_transactions', $rows);
    }
}
