<?php

namespace App\Observers;

use App\Models\InvoiceItem;
use App\Services\Billing\VisitFinancialClearanceService;

class InvoiceItemObserver
{
    public $afterCommit = true;
    public function created(InvoiceItem $item): void { $this->stale($item, 'invoice_item_created'); }
    public function updated(InvoiceItem $item): void { $this->stale($item, 'invoice_item_updated'); }
    public function deleted(InvoiceItem $item): void { $this->stale($item, 'invoice_item_deleted'); }
    private function stale(InvoiceItem $item, string $reason): void { try { if ($item->visit) app(VisitFinancialClearanceService::class)->markStale($item->visit, $reason); } catch (\Throwable $e) { report($e); } }
}
