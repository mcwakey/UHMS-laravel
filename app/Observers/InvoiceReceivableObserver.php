<?php

namespace App\Observers;

use App\Models\InvoiceReceivable;
use App\Services\Billing\VisitFinancialClearanceService;

class InvoiceReceivableObserver
{
    public $afterCommit = true;
    public function created(InvoiceReceivable $r): void { $this->stale($r, 'receivable_created'); }
    public function updated(InvoiceReceivable $r): void { $this->stale($r, 'receivable_updated'); }
    public function deleted(InvoiceReceivable $r): void { $this->stale($r, 'receivable_deleted'); }
    private function stale(InvoiceReceivable $r, string $reason): void { try { if ($r->visit) app(VisitFinancialClearanceService::class)->markStale($r->visit, $reason); } catch (\Throwable $e) { report($e); } }
}
