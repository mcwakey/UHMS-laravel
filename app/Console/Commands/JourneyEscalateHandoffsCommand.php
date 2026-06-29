<?php

namespace App\Console\Commands;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\JourneyHandoffAssignment;
use App\Services\ActivityLogService;
use App\Services\Journey\JourneyEscalationService;
use App\Services\Journey\JourneyHandoffAssignmentService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Phase 9.6 — scheduled, idempotent sweep over ACTIVE handoff assignments:
 *   - re-derives the current handoff, persists any risen escalation (notifies),
 *   - auto-dismisses assignments whose handoff is no longer active.
 *
 * Safe to run repeatedly. --dry-run mutates nothing. Bounded by --limit.
 */
class JourneyEscalateHandoffsCommand extends Command
{
    protected $signature = 'journey:handoffs:escalate
        {--dry-run : Report only; persist nothing}
        {--limit=500 : Max assignments to process}
        {--department= : Limit to a destination department id}
        {--cause= : Limit to a delay cause}
        {--critical-only : Only act on critical escalations}
        {--include-unassigned : Also notify near-breach+ handoffs that have no assignment row}';

    protected $description = 'Re-derive active handoff assignments, persist risen escalation, notify, and auto-dismiss stale rows.';

    public function handle(
        JourneyHandoffAssignmentService $assignments,
        JourneyEscalationService $escalation,
        ActivityLogService $activity,
        \App\Services\Journey\JourneyHandoffWorklistService $worklist,
        \App\Services\Journey\JourneyHandoffNotificationService $notifications,
    ): int {
        $dryRun = (bool) $this->option('dry-run');
        $limit = max(1, (int) ($this->option('limit') ?: 500));
        $criticalOnly = (bool) $this->option('critical-only');
        $runId = (string) Str::uuid();

        $checked = $escalated = $critical = $dismissed = $notified = $skipped = $unassigned = 0;

        $ids = JourneyHandoffAssignment::query()
            ->whereIn('status', JourneyHandoffAssignment::ACTIVE_STATUSES)
            ->when($this->option('department'), fn ($q, $d) => $q->where('to_department_id', (int) $d))
            ->when($this->option('cause'), fn ($q, $c) => $q->where('cause', $c))
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        foreach ($ids->chunk(100) as $chunk) {
            $rows = JourneyHandoffAssignment::with(['assignedTo', 'visit'])->whereIn('id', $chunk)->get();

            foreach ($rows as $assignment) {
                $checked++;
                $handoff = $assignments->activeHandoffFor($assignment->visit_id);

                // Stale → auto-dismiss (Task 6).
                if (! $assignments->matchesCurrentHandoff($assignment, $handoff)) {
                    $dismissed++;
                    if (! $dryRun) {
                        $assignment->loadMissing('assignedTo');
                        $assignments->dismissStale($assignment);
                        if ($assignment->assigned_to_user_id !== null) {
                            $notified++;
                        }
                    }
                    continue;
                }

                $level = $escalation->levelFor($handoff, $assignment);
                if ($criticalOnly && $level !== JourneyHandoffAssignment::ESCALATION_CRITICAL) {
                    $skipped++;
                    continue;
                }

                if ($escalation->shouldEscalate($handoff, $assignment)) {
                    $escalated++;
                    $notified++;
                    if ($level === JourneyHandoffAssignment::ESCALATION_CRITICAL) {
                        $critical++;
                    }
                    if (! $dryRun) {
                        $escalation->applyEscalation($handoff, $assignment, null);
                    }
                } else {
                    $skipped++;
                }
            }
        }

        // Optional sweep over near-breach+ handoffs that have NO assignment row.
        if ($this->option('include-unassigned')) {
            $handoffs = $worklist->unassignedBreachedHandoffs(
                $this->option('department') ? (int) $this->option('department') : null,
                $this->option('cause') ?: null,
                $limit,
            );
            foreach ($handoffs as $handoff) {
                if ($criticalOnly && $handoff->slaStatus !== 'critical_breach') {
                    continue;
                }
                $unassigned++;
                if (! $dryRun) {
                    $notified += $notifications->notifyCriticalUnassigned($handoff) > 0 ? 1 : 0;
                }
            }
        }

        $summary = compact('checked', 'escalated', 'critical', 'dismissed', 'notified', 'skipped', 'unassigned');

        if (! $dryRun) {
            $activity->log(LogModule::CLINICAL_TASKS, 'JOURNEY_HANDOFF_ESCALATION_RUN', [
                'severity' => LogSeverity::INFO,
                'command_run_id' => $runId,
                'dry_run' => false,
            ] + $summary, null, 'Journey handoff escalation sweep');
        }

        $this->line(($dryRun ? '[dry-run] ' : '').sprintf(
            'Checked: %d  Escalated: %d  Critical: %d  Dismissed stale: %d  Unassigned: %d  Notifications: %d  Skipped: %d',
            $checked, $escalated, $critical, $dismissed, $unassigned, $notified, $skipped
        ));

        return self::SUCCESS;
    }
}
