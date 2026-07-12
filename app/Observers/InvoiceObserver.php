<?php

namespace App\Observers;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\Invoice;
use App\Services\ActivityLogService;

class InvoiceObserver
{
    public $afterCommit = true;
    public function __construct(protected ActivityLogService $logger) {}

    public function created(Invoice $invoice): void
    {
        $this->logger->log(LogModule::BILLING, 'INVOICE_CREATED', [
            'invoice_id' => $invoice->id,
            'patient_id' => $invoice->patient_id,
            'visit_id' => $invoice->visit_id,
            'metadata' => [
                'invoice_number' => $invoice->invoice_number,
                'total_amount' => $invoice->total_amount,
                'status' => (string) ($invoice->status?->value ?? $invoice->status),
            ],
        ], $invoice, 'Invoice generated');
        $this->markStale($invoice, 'invoice_created');
    }

    public function updated(Invoice $invoice): void
    {
        $changed = $invoice->getChanges();
        $original = $invoice->getOriginal();

        if (array_key_exists('status', $changed)) {
            $from = $original['status'] ?? null;
            $to = $changed['status'] ?? null;
            $from = is_object($from) ? ($from->value ?? (string) $from) : (string) $from;
            $to = is_object($to) ? ($to->value ?? (string) $to) : (string) $to;
            $severity = in_array($to, ['cancelled', 'refunded', 'voided'], true)
                ? LogSeverity::WARNING
                : LogSeverity::INFO;

            $this->logger->log(LogModule::BILLING, 'INVOICE_STATUS_CHANGED', [
                'invoice_id' => $invoice->id,
                'patient_id' => $invoice->patient_id,
                'severity' => $severity,
                'metadata' => ['from' => $from, 'to' => $to, 'invoice_number' => $invoice->invoice_number],
            ], $invoice, "Invoice status: {$from} → {$to}");
        }
        $this->markStale($invoice, 'invoice_updated');
    }

    public function deleted(Invoice $invoice): void
    {
        $this->logger->logDeleted($invoice, LogModule::BILLING, 'Invoice deleted', [
            'invoice_id' => $invoice->id,
            'patient_id' => $invoice->patient_id,
            'severity' => LogSeverity::WARNING,
        ]);
        $this->markStale($invoice, 'invoice_deleted');
    }

    private function markStale(Invoice $invoice, string $reason): void
    {
        try { if ($invoice->visit) app(\App\Services\Billing\VisitFinancialClearanceService::class)->markStale($invoice->visit, $reason); } catch (\Throwable $e) { report($e); }
    }
}
