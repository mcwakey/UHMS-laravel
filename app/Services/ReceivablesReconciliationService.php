<?php

namespace App\Services;

use App\Models\InvoiceReceivable;
use Carbon\Carbon;

class ReceivablesReconciliationService extends AbstractReconciliationDomainService
{
    private const SETTINGS = [
        InvoiceReceivable::PAYER_PATIENT => 'patient_receivable_account_id',
        InvoiceReceivable::PAYER_INSURANCE => 'insurance_receivable_account_id',
        InvoiceReceivable::PAYER_SPONSOR => 'sponsor_receivable_account_id',
        InvoiceReceivable::PAYER_CORPORATE => 'corporate_receivable_account_id',
    ];

    public function calculate(Carbon $periodStart, Carbon $periodEnd, Carbon $asOf): array
    {
        $rows = InvoiceReceivable::query()
            ->with(['invoice', 'patient', 'insuranceProvider', 'sponsor', 'corporateClient'])
            ->open()
            ->whereDate('aging_start_date', '<=', $asOf)
            ->get();
        $accounts = collect(self::SETTINGS)->map(fn ($setting) => $this->account($setting))->filter()->unique('id')->values();
        $items = [];
        $sourceGroups = [];
        $glGroups = [];

        foreach (self::SETTINGS as $payerType => $setting) {
            $account = $this->account($setting);
            $source = round((float) $rows->where('payer_type', $payerType)->sum('balance'), 2);
            $gl = $this->glBalance($account, $asOf);
            $sourceGroups[$payerType] = $source;
            $glGroups[$payerType] = ['account_id' => $account?->id, 'balance' => $gl];
            $classification = ! $account ? 'mapping_issue' : (abs($source - $gl) < 0.01 ? 'balanced' : 'unknown_difference');
            $items[] = $this->item('receivable_control', null, $payerType, ucfirst($payerType).' receivables control', $account, $source, $gl, $classification);
        }

        foreach ($rows as $row) {
            $classification = $this->sourceClassification(InvoiceReceivable::class, $row->id, $row->accounting_status, $row->journal_entry_id);
            $account = $this->account(self::SETTINGS[$row->payer_type] ?? 'patient_receivable_account_id');
            $attempt = $this->failedAttempt(InvoiceReceivable::class, $row->id);
            $attributedGl = $classification === 'balanced' ? (float) $row->balance : 0.0;
            $items[] = $this->item(
                InvoiceReceivable::class,
                $row->id,
                $row->invoice?->invoice_number ?? 'AR-'.$row->id,
                $row->payerName(),
                $account,
                (float) $row->balance,
                $attributedGl,
                $classification,
                [
                    'payer_type' => $row->payer_type,
                    'payer_id' => $row->payer_id,
                    'patient_id' => $row->patient_id,
                    'visit_id' => $row->visit_id,
                    'aging_start_date' => $row->aging_start_date?->toDateString(),
                    'due_date' => $row->due_date?->toDateString(),
                    'posting_attempt_id' => $attempt?->id,
                    'comparison_basis' => $classification === 'balanced'
                        ? 'Posted source balance attributed to its mapped control account; the control summary row holds the authoritative GL comparison.'
                        : 'Source exception awaiting posting or resolution.',
                ],
            );
        }

        $items = array_merge($items, $this->manualJournalItems($accounts, $asOf));

        return $this->result(
            'available',
            (float) $rows->sum('balance'),
            (float) collect($glGroups)->sum('balance'),
            $items,
            ['record_count' => $rows->count(), 'by_payer_type' => $sourceGroups],
            ['accounts' => $glGroups],
        );
    }
}
