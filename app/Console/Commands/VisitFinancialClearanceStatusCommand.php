<?php
namespace App\Console\Commands;
use App\Models\VisitFinancialClearance;
use App\Services\Billing\VisitFinancialClearanceConfigurationService;
use Illuminate\Console\Command;
class VisitFinancialClearanceStatusCommand extends Command
{
 protected $signature='billing:visit-financial-clearance-status {--visit=} {--status=}'; protected $description='Show visit financial-clearance status (read-only)';
 public function handle(VisitFinancialClearanceConfigurationService $config): int { $this->line('Configured mode: '.$config->configuredMode()->value); $this->line('Effective mode: '.$config->effectiveMode()->value); $this->line('Force disabled: '.($config->forceDisabled()?'yes':'no')); $q=VisitFinancialClearance::query(); if($this->option('visit'))$q->where('visit_id',$this->option('visit')); if($this->option('status'))$q->where('status',$this->option('status')); $this->table(['Visit','Status','Basis','Outstanding'], $q->get()->map(fn($c)=>[$c->visit_id,$c->status->value,$c->basis?->value,(string)$c->patient_outstanding_snapshot])); return self::SUCCESS; }
}
