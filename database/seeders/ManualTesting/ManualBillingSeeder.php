<?php

namespace Database\Seeders\ManualTesting;

use Illuminate\Support\Facades\DB;

class ManualBillingSeeder extends ManualTestingSeederBase
{
    public function run(): void
    {
        if (! $this->hasTable('invoices')) {
            return;
        }

        $existing = $this->countManual('invoices', 'invoice_number');
        $target = $this->target('invoices');
        if ($existing >= $target) {
            return;
        }

        $visits = DB::table('visits')->where('visit_number', 'like', 'MT-VIS-%')->select('id', 'patient_id', 'current_department_id', 'created_by', 'created_at')->limit($target)->get()->values();
        $services = DB::table('service_catalog')->pluck('id')->values();
        if ($visits->isEmpty()) {
            return;
        }

        $invoiceRows = [];
        $itemRows = [];
        $paymentRows = [];
        $statuses = ['pending', 'partially_paid', 'paid', 'cancelled', 'refunded'];

        for ($i = $existing + 1; $i <= $target; $i++) {
            $visit = $visits[($i - 1) % $visits->count()];
            $total = 30 + (($i % 12) * 25);
            $status = $statuses[$i % count($statuses)];
            $paid = match ($status) {
                'paid' => $total,
                'partially_paid' => round($total / 2, 2),
                'refunded' => $total,
                default => 0,
            };
            $invoiceRows[] = [
                'invoice_number' => $this->ref('BILL', $i),
                'visit_id' => $visit->id,
                'patient_id' => $visit->patient_id,
                'billing_type' => $i % 5 === 0 ? 'insurance' : ($i % 7 === 0 ? 'corporate' : 'cash'),
                'subtotal' => $total,
                'tax_amount' => 0,
                'discount_amount' => $i % 9 === 0 ? 5 : 0,
                'adjustment_amount' => $i % 13 === 0 ? -10 : 0,
                'total_amount' => $total,
                'amount_paid' => $paid,
                'balance' => max(0, $total - $paid),
                'status' => $status,
                'due_date' => today()->addDays(30 - ($i % 90)),
                'notes' => $this->metadata(['billing_scenario' => $status]),
                'created_by' => $visit->created_by,
                'created_at' => $visit->created_at,
                'updated_at' => $this->now(),
            ];
        }

        $this->insert('invoices', $invoiceRows);

        $invoices = DB::table('invoices')->where('invoice_number', 'like', 'MT-BILL-%')->select('id', 'visit_id', 'patient_id', 'total_amount', 'amount_paid', 'created_by')->get();
        foreach ($invoices as $index => $invoice) {
            $serviceId = $services->isNotEmpty() ? $services[$index % $services->count()] : null;
            $itemRows[] = [
                'invoice_id' => $invoice->id,
                'visit_id' => $invoice->visit_id,
                'patient_id' => $invoice->patient_id,
                'service_catalog_id' => $serviceId,
                'department_id' => null,
                'source_type' => ['consultation_service', 'investigation_service', 'pharmacy_product', 'procedure_service'][$index % 4],
                'description' => $this->ref('LINE', $index + 1).' Manual billable service line',
                'quantity' => 1,
                'unit_price' => $invoice->total_amount,
                'cash_price' => $invoice->total_amount,
                'selected_price' => $invoice->total_amount,
                'patient_payable' => $invoice->total_amount,
                'paid_amount' => $invoice->amount_paid,
                'balance' => max(0, $invoice->total_amount - $invoice->amount_paid),
                'payment_status' => $invoice->amount_paid >= $invoice->total_amount ? 'paid' : ($invoice->amount_paid > 0 ? 'partially_paid' : 'unpaid'),
                'total_price' => $invoice->total_amount,
                'created_by' => $invoice->created_by,
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ];

            if ((float) $invoice->amount_paid > 0) {
                $paymentRows[] = [
                    'payment_number' => $this->ref('PAY', $index + 1),
                    'invoice_id' => $invoice->id,
                    'patient_id' => $invoice->patient_id,
                    'amount' => $invoice->amount_paid,
                    'payment_method' => ['cash', 'mtn_momo', 'bank_transfer', 'card'][$index % 4],
                    'reference_number' => $this->ref('REF', $index + 1),
                    'received_by' => $invoice->created_by,
                    'notes' => $this->metadata(['payment' => true]),
                    'paid_at' => now()->subDays($index % 90),
                    'created_at' => $this->now(),
                    'updated_at' => $this->now(),
                ];
            }
        }

        if ($this->countManual('invoice_items', 'description') === 0) {
            $this->insert('invoice_items', $itemRows);
        }
        $this->insert('payments', array_filter($paymentRows, fn ($row) => ! DB::table('payments')->where('payment_number', $row['payment_number'])->exists()));
    }
}
