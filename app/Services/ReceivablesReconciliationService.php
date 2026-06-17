<?php

namespace App\Services;

use App\Models\InvoiceReceivable;
use App\Models\ReceivableCaseItem;
use App\Models\ReceivableCreditnoteRecommendation;
use App\Models\ReceivableDispute;
use App\Models\ReceivablePromise;
use App\Models\ReceivableWriteoffRecommendation;
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
            $operationalClassification = $this->operationalClassification($row);
            if ($operationalClassification) {
                $classification = $operationalClassification;
            }
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

    private function operationalClassification(InvoiceReceivable $receivable): ?string
    {
        $caseIds = ReceivableCaseItem::query()
            ->where('source_type', InvoiceReceivable::class)
            ->where('source_id', $receivable->id)
            ->pluck('receivable_case_id');

        if ($caseIds->isNotEmpty()) {
            if (ReceivableDispute::whereIn('receivable_case_id', $caseIds)->whereIn('status', ['open', 'under_review'])->exists()) {
                return 'disputed_receivable';
            }
            if (ReceivablePromise::whereIn('receivable_case_id', $caseIds)->where('status', 'broken')->exists()) {
                return 'payment_promise_broken';
            }
            if (ReceivablePromise::whereIn('receivable_case_id', $caseIds)->where('status', 'active')->exists()) {
                return 'payment_promise_active';
            }
            if (ReceivableWriteoffRecommendation::whereIn('receivable_case_id', $caseIds)->whereIn('status', ['recommended', 'approved'])->exists()) {
                return 'writeoff_recommended';
            }
            if (ReceivableCreditnoteRecommendation::whereIn('receivable_case_id', $caseIds)->whereIn('status', ['recommended', 'approved'])->exists()) {
                return 'creditnote_recommended';
            }
        }

        if ($receivable->claim_id) {
            return 'claim_pending';
        }
        if ($receivable->payer_type === InvoiceReceivable::PAYER_SPONSOR) {
            return 'sponsor_pending';
        }
        if ($receivable->payer_type === InvoiceReceivable::PAYER_CORPORATE) {
            return 'corporate_pending';
        }

        return null;
    }
}
