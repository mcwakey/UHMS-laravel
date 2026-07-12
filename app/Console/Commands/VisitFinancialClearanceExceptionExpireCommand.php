<?php
namespace App\Console\Commands;
use App\Services\Billing\VisitFinancialClearanceExceptionService;
use Illuminate\Console\Command;
class VisitFinancialClearanceExceptionExpireCommand extends Command
{
 protected $signature='billing:visit-financial-clearance-exception-expire {--commit} {--dry-run}'; protected $description='Expire due financial-clearance exceptions; dry-run by default';
 public function handle(VisitFinancialClearanceExceptionService $service): int { $commit=(bool)$this->option('commit')&&!$this->option('dry-run'); $count=$service->expireDue($commit); $this->info(($commit?'Expired ':'Dry run: would expire ').$count.' exception(s).'); return self::SUCCESS; }
}
