<?php
namespace App\Console\Commands;
use App\Models\VisitFinancialClearance;
use App\Services\Billing\VisitFinancialClearanceService;
use Illuminate\Console\Command;
class VisitFinancialClearanceRefreshCommand extends Command
{
 protected $signature='billing:visit-financial-clearance-refresh {--commit} {--visit=} {--stale-only}'; protected $description='Refresh existing clearances; dry-run by default';
 public function handle(VisitFinancialClearanceService $service): int { $q=VisitFinancialClearance::with('visit'); if($this->option('visit'))$q->where('visit_id',$this->option('visit')); if($this->option('stale-only'))$q->stale(); $count=$q->count(); if(!$this->option('commit')){$this->info("Dry run: {$count} clearance(s) would be refreshed.");return self::SUCCESS;} $q->chunkById(100,fn($rows)=>$rows->each(fn($c)=>$service->refresh($c->visit))); $this->info("Refreshed {$count} clearance(s).");return self::SUCCESS; }
}
