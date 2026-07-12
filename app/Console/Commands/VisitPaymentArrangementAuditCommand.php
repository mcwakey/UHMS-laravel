<?php

namespace App\Console\Commands;

use App\Enums\VisitPaymentArrangementStatus;
use App\Enums\VisitPaymentTimingPolicy;
use App\Models\VisitPaymentArrangement;
use App\Models\VisitPaymentPolicy;
use App\Services\Billing\VisitPaymentArrangementService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Read-only diagnostic audit of per-visit payment arrangements (Payment Timing
 * Policy Phase 7). Detects anomalies but performs NO writes/repair/activity logs
 * and emits no sensitive free text. Findings never fail the exit code — only a
 * technical failure does.
 */
class VisitPaymentArrangementAuditCommand extends Command
{
    protected $signature = 'billing:visit-payment-arrangement-audit
        {--visit= : Only this visit id}
        {--status= : Filter by status}
        {--pending : Only pending}
        {--approved : Only approved}
        {--expired : Only past-expiry approved}
        {--stale : Only approved/pending with stale risk context}
        {--problems-only : Only rows with findings}
        {--limit=5000 : Maximum records to scan}
        {--json : Emit machine-readable JSON}';

    protected $description = 'Audit visit payment arrangements for anomalies (read-only; no writes).';

    public function handle(VisitPaymentArrangementService $service): int
    {
        try {
            $limit = max(1, min((int) $this->option('limit'), 50000));
            $findings = [];

            // Cross-visit uniqueness violations.
            $multiPending = $this->duplicates(VisitPaymentArrangementStatus::PENDING);
            $multiApproved = $this->duplicates(VisitPaymentArrangementStatus::APPROVED);

            $query = VisitPaymentArrangement::query()->with('visit.patient');
            if ($v = $this->option('visit')) {
                $query->where('visit_id', $v);
            }
            if ($s = $this->option('status')) {
                $query->where('status', $s);
            }
            if ($this->option('pending')) {
                $query->pending();
            }
            if ($this->option('approved')) {
                $query->approved();
            }
            if ($this->option('expired')) {
                $query->whereNotNull('expires_at')->whereDate('expires_at', '<=', now());
            }

            $query->orderBy('id')->limit($limit)->chunkById(200, function ($rows) use (&$findings, $service, $multiPending, $multiApproved): void {
                foreach ($rows as $a) {
                    $problems = [];
                    $status = $a->status;

                    if (in_array($a->visit_id, $multiPending, true) && $status === VisitPaymentArrangementStatus::PENDING) {
                        $problems[] = 'multiple_pending';
                    }
                    if (in_array($a->visit_id, $multiApproved, true) && $status === VisitPaymentArrangementStatus::APPROVED) {
                        $problems[] = 'multiple_current_approved';
                    }
                    if ($status === VisitPaymentArrangementStatus::APPROVED && $a->getRawOriginal('approved_policy') === null) {
                        $problems[] = 'approved_without_policy';
                    }
                    if ($status === VisitPaymentArrangementStatus::PENDING && $a->getRawOriginal('approved_policy') !== null) {
                        $problems[] = 'pending_with_approved_policy';
                    }
                    if ($a->approved_by !== null && $a->requested_by !== null && $a->approved_by === $a->requested_by) {
                        $problems[] = 'requester_equals_approver';
                    }
                    if ($status === VisitPaymentArrangementStatus::APPROVED && $a->expires_at !== null && $a->expires_at->lt(now())) {
                        $problems[] = 'expired_still_current';
                    }
                    if (in_array($a->getRawOriginal('requested_policy'), [VisitPaymentTimingPolicy::INHERIT->value], true)
                        || $a->getRawOriginal('approved_policy') === VisitPaymentTimingPolicy::INHERIT->value) {
                        $problems[] = 'inherit_policy';
                    }
                    if ($a->effective_from && $a->expires_at && $a->expires_at->lt($a->effective_from)) {
                        $problems[] = 'expiry_before_effective';
                    }
                    if ($status->isTerminal() && $this->terminalTimestampMissing($a)) {
                        $problems[] = 'terminal_missing_timestamp';
                    }
                    if (in_array($status, [VisitPaymentArrangementStatus::PENDING, VisitPaymentArrangementStatus::APPROVED], true) && $service->riskIsStale($a)) {
                        $problems[] = 'stale_risk_context';
                    }
                    if ($status === VisitPaymentArrangementStatus::APPROVED && $a->visit_payment_policy_id !== null
                        && ! VisitPaymentPolicy::whereKey($a->visit_payment_policy_id)->exists()) {
                        $problems[] = 'missing_visit_policy_link';
                    }

                    if ($this->option('stale') && ! in_array('stale_risk_context', $problems, true)) {
                        continue;
                    }
                    if ($problems !== []) {
                        $findings[$a->id] = ['visit_id' => $a->visit_id, 'findings' => $problems];
                    }
                }
            }, 'id');

            return $this->render($findings);
        } catch (Throwable $e) {
            $this->error('Arrangement audit failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /** @return array<int, int> visit ids with more than one row in the given status */
    private function duplicates(VisitPaymentArrangementStatus $status): array
    {
        return VisitPaymentArrangement::query()
            ->where('status', $status->value)
            ->select('visit_id', DB::raw('COUNT(*) as c'))
            ->groupBy('visit_id')->having('c', '>', 1)
            ->pluck('visit_id')->all();
    }

    private function terminalTimestampMissing(VisitPaymentArrangement $a): bool
    {
        return match ($a->status) {
            VisitPaymentArrangementStatus::REJECTED => $a->reviewed_at === null,
            VisitPaymentArrangementStatus::WITHDRAWN => $a->withdrawn_at === null,
            VisitPaymentArrangementStatus::REVOKED => $a->revoked_at === null,
            default => false,
        };
    }

    /**
     * @param  array<int, array{visit_id:int, findings:array<int,string>}>  $findings
     */
    private function render(array $findings): int
    {
        if ($this->option('json')) {
            $this->line((string) json_encode([
                'records_with_findings' => count($findings),
                'total_findings' => collect($findings)->pluck('findings')->flatten()->count(),
                'findings' => collect($findings)->map(fn ($f, $id) => array_merge(['arrangement_id' => $id], $f))->values(),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('Visit payment arrangement audit (read-only)');
        $this->info('Records with findings: '.count($findings));
        if ($findings !== []) {
            $this->table(['Arrangement', 'Visit', 'Findings'], collect($findings)->map(fn ($f, $id) => [$id, $f['visit_id'], implode(', ', $f['findings'])])->all());
        } elseif (! $this->option('problems-only')) {
            $this->line('No anomalies detected for the selected filters.');
        }
        $this->line('Findings are advisory; no data was modified.');

        return self::SUCCESS;
    }
}
