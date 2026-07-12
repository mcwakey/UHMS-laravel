<?php
namespace App\Console\Commands;
use App\Models\Visit;
use App\Services\Billing\VisitFinancialClearanceService;
use Illuminate\Console\Command;
class VisitFinancialClearanceBackfillCommand extends Command
{
 protected $signature='billing:visit-financial-clearance-backfill {--commit} {--dry-run} {--active-only} {--visit=}'; protected $description='Backfill visit financial clearances; dry-run by default';
 public function handle(VisitFinancialClearanceService $service): int { $q=Visit::query()->whereDoesntHave('financialClearance'); if($this->option('visit'))$q->whereKey($this->option('visit')); if($this->option('active-only'))$q->whereNotIn('status',['completed','discharged','cancelled','no_show','abandoned','deceased']); $count=$q->count(); $commit=(bool)$this->option('commit')&&!$this->option('dry-run'); if(!$commit){$this->info("Dry run: {$count} visit(s) would be assessed.");return self::SUCCESS;} $q->chunkById(100,fn($visits)=>$visits->each(fn($v)=>$service->assess($v))); $this->info("Assessed {$count} visit(s)."); return self::SUCCESS; }
}
