<?php

namespace App\Console\Commands;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\BasicAccountingBackfillService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class PostBasicAccountingEntriesToGl extends Command
{
    protected $signature = 'accounting:basic-entries-post-to-gl
        {--dry-run : Preview without creating journals or posting attempts}
        {--from= : Entry date from YYYY-MM-DD}
        {--to= : Entry date to YYYY-MM-DD}
        {--chunk=100 : Batch size}
        {--entry-type= : income or expense}
        {--category-id= : Account category ID}
        {--entry-id= : One financial entry ID}
        {--resume-from= : Resume from financial entry ID}
        {--approved-batch-id= : Required execution approval/reference identifier}';

    protected $description = 'Preview or post approved Basic Accounting entries to the Advanced Accounting general ledger.';

    public function handle(BasicAccountingBackfillService $backfill): int
    {
        $filters = array_filter([
            'from' => $this->option('from'),
            'to' => $this->option('to'),
            'entry_type' => $this->option('entry-type'),
            'category_id' => $this->option('category-id'),
            'entry_id' => $this->option('entry-id'),
            'resume_from' => $this->option('resume-from'),
        ], fn ($value) => $value !== null && $value !== '');
        $chunk = max(1, (int) $this->option('chunk'));

        if ($this->option('dry-run')) {
            $preview = $backfill->preview($filters, $chunk);
            foreach ($preview['rows'] as $row) {
                $entry = $row['entry'];
                $result = $row['result'];
                $this->line(sprintf(
                    '#%d %s %s GHS %0.2f - %s',
                    $entry->id,
                    $entry->entry_number,
                    $entry->entry_date->toDateString(),
                    $entry->amount,
                    $result['eligible'] ? 'ELIGIBLE' : 'BLOCKED: '.implode('; ', $result['reasons']),
                ));
            }
            $this->info("Dry run complete: {$preview['eligible']} eligible, {$preview['ineligible']} blocked.");
            return self::SUCCESS;
        }

        if (! $this->option('approved-batch-id')) {
            $this->error('Execution requires --approved-batch-id.');
            return self::INVALID;
        }

        $actor = User::query()->whereHas('roles', fn ($q) => $q->whereIn('name', ['Super Admin', 'Admin', 'Finance Manager', 'Accountant']))
            ->first() ?? User::query()->first();
        if (! $actor) {
            $this->error('No user is available to own the posting audit trail.');
            return self::FAILURE;
        }
        Auth::login($actor);

        $totals = $backfill->execute($filters, $actor, $chunk, function ($entry, $result) {
            $this->line(sprintf('#%d %s - %s', $entry->id, $entry->entry_number, $result['success'] ? 'POSTED' : 'FAILED: '.$result['error']));
        });
        app(ActivityLogService::class)->log(LogModule::ACCOUNTING, 'BASIC_ENTRY_POSTING_BATCH_EXECUTED', [
            'severity' => LogSeverity::WARNING,
            'causer' => $actor,
            'metadata' => array_merge($totals, [
                'approved_batch_id' => $this->option('approved-batch-id'),
                'filters' => $filters,
            ]),
        ], null, 'Basic Accounting posting command batch executed');
        $this->info(sprintf(
            'Execution complete: %d selected, %d posted, %d failed, %d skipped. Batch: %s',
            $totals['selected'],
            $totals['posted'],
            $totals['failed'],
            $totals['skipped'],
            $this->option('approved-batch-id'),
        ));

        return $totals['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
