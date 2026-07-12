<?php
namespace App\Console\Commands;
use App\Enums\VisitFinancialClearanceStatus as Status;
use App\Models\VisitFinancialClearance;
use Illuminate\Console\Command;
class VisitFinancialClearanceAuditCommand extends Command
{
 protected $signature='billing:visit-financial-clearance-audit'; protected $description='Audit financial-clearance invariants without changing data';
 public function handle(): int { $findings=[]; VisitFinancialClearance::with('currentException')->chunk(200,function($rows)use(&$findings){foreach($rows as$c){if($c->status===Status::CONDITIONALLY_CLEARED&&!$c->currentException)$findings[]=[$c->visit_id,'conditional_without_exception']; if($c->status===Status::FINANCIALLY_CLOSED&&(float)$c->patient_outstanding_snapshot>0&&!$c->currentException)$findings[]=[$c->visit_id,'closed_debt_without_exception']; if($c->currentException&&!in_array($c->currentException->status->value,['approved'],true))$findings[]=[$c->visit_id,'terminal_exception_linked'];}}); $this->table(['Visit','Finding'],$findings); $this->info(count($findings).' finding(s).'); return self::SUCCESS; }
}
