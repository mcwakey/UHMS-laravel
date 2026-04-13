<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class StatementService
{
    /**
     * Generate a financial statement for a patient.
     */
    public function generate(Patient $patient, array $filters = []): array
    {
        $invoiceQuery = Invoice::where('patient_id', $patient->id);
        $paymentQuery = Payment::where('patient_id', $patient->id);

        if (!empty($filters['date_from'])) {
            $invoiceQuery->whereDate('created_at', '>=', $filters['date_from']);
            $paymentQuery->whereDate('paid_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $invoiceQuery->whereDate('created_at', '<=', $filters['date_to']);
            $paymentQuery->whereDate('paid_at', '<=', $filters['date_to']);
        }

        $invoices = (clone $invoiceQuery)
            ->with(['visit', 'items.serviceCatalog'])
            ->orderBy('created_at')
            ->get();

        $payments = (clone $paymentQuery)
            ->with(['invoice', 'receivedBy'])
            ->orderBy('paid_at')
            ->get();

        // Build transaction ledger (chronological mix of charges & payments)
        $ledger = collect();
        $runningBalance = 0;

        foreach ($invoices as $invoice) {
            $runningBalance += $invoice->total_amount;
            $ledger->push([
                'date'        => $invoice->created_at,
                'type'        => 'charge',
                'reference'   => $invoice->invoice_number,
                'description' => 'Invoice — ' . ($invoice->visit?->visit_number ?? 'Direct'),
                'charges'     => $invoice->total_amount,
                'payments'    => 0,
                'balance'     => $runningBalance,
                'invoice'     => $invoice,
            ]);
        }

        foreach ($payments as $payment) {
            $runningBalance -= $payment->amount;
            $ledger->push([
                'date'        => $payment->paid_at,
                'type'        => 'payment',
                'reference'   => $payment->payment_number,
                'description' => 'Payment (' . ($payment->payment_method->label() ?? $payment->payment_method) . ')',
                'charges'     => 0,
                'payments'    => $payment->amount,
                'balance'     => $runningBalance,
                'payment'     => $payment,
            ]);
        }

        $ledger = $ledger->sortBy('date')->values();

        // Recalculate running balance in sorted order
        $runningBalance = 0;
        $ledger = $ledger->map(function ($entry) use (&$runningBalance) {
            $runningBalance += $entry['charges'] - $entry['payments'];
            $entry['balance'] = $runningBalance;
            return $entry;
        });

        $totalCharges  = $invoices->sum('total_amount');
        $totalPayments = $payments->sum('amount');

        $summary = [
            'total_charges'  => $totalCharges,
            'total_payments' => $totalPayments,
            'balance_due'    => $totalCharges - $totalPayments,
            'invoice_count'  => $invoices->count(),
            'payment_count'  => $payments->count(),
        ];

        return compact('patient', 'invoices', 'payments', 'ledger', 'summary');
    }
}
