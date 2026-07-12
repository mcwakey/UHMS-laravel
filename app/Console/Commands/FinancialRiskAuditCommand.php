<?php

namespace App\Console\Commands;

use App\Enums\PatientFinancialRiskReason;
use App\Enums\PatientFinancialRiskStatus;
use App\Models\PatientFinancialRiskProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Read-only diagnostic audit of patient financial-risk profiles (Payment Timing
 * Policy Phase 5). It detects anomalies but performs NO writes, NO fixes, NO
 * activity logs, and prints no sensitive free-text. Findings never cause a
 * non-zero exit code — only a technical command failure does.
 */
class FinancialRiskAuditCommand extends Command
{
    protected $signature = 'billing:financial-risk-audit
        {--patient= : Filter by patient id}
        {--status= : Filter by status}
        {--level= : Filter by risk level}
        {--review-due : Only profiles due for review}
        {--expired : Only past-expiry profiles}
        {--problems-only : Only rows with findings}
        {--json : Emit machine-readable JSON}';

    protected $description = 'Audit patient financial-risk profiles for anomalies (read-only; no writes).';

    public function handle(): int
    {
        try {
            $findings = [];

            // Cross-patient: more than one active-slot profile per patient.
            $duplicatePatients = PatientFinancialRiskProfile::query()
                ->active()
                ->select('patient_id', DB::raw('COUNT(*) as c'))
                ->groupBy('patient_id')
                ->having('c', '>', 1)
                ->pluck('patient_id')
                ->all();

            $query = PatientFinancialRiskProfile::query();
            if ($p = $this->option('patient')) {
                $query->where('patient_id', $p);
            }
            if ($s = $this->option('status')) {
                $query->where('status', $s);
            }
            if ($l = $this->option('level')) {
                $query->where('risk_level', $l);
            }
            if ($this->option('review-due')) {
                $query->dueForReview();
            }
            if ($this->option('expired')) {
                $query->whereNotNull('expires_at')->whereDate('expires_at', '<=', now());
            }

            $query->orderBy('id')->each(function (PatientFinancialRiskProfile $profile) use (&$findings, $duplicatePatients): void {
                $problems = [];

                if (in_array($profile->patient_id, $duplicatePatients, true)) {
                    $problems[] = 'multiple_active_profiles';
                }
                if ($profile->occupiesActiveSlot() && $profile->isPastExpiry()) {
                    $problems[] = 'active_beyond_expiry';
                }
                if ($profile->review_due_at && $profile->effective_from && $profile->review_due_at->lt($profile->effective_from)) {
                    $problems[] = 'review_before_effective';
                }
                if ($profile->expires_at && $profile->effective_from && $profile->expires_at->lt($profile->effective_from)) {
                    $problems[] = 'expiry_before_effective';
                }
                if ($profile->set_by === null) {
                    $problems[] = 'missing_setter';
                }
                if ($profile->primary_reason === PatientFinancialRiskReason::OTHER && blank($profile->reason_details)) {
                    $problems[] = 'missing_reason_details';
                }
                if ($profile->status === PatientFinancialRiskStatus::CLEARED && blank($profile->clearance_reason)) {
                    $problems[] = 'cleared_without_reason';
                }
                if ($profile->cleared_at !== null && $profile->status !== PatientFinancialRiskStatus::CLEARED) {
                    $problems[] = 'cleared_timestamp_but_not_cleared';
                }
                if ($profile->isReviewOverdue()) {
                    $problems[] = 'review_overdue';
                }

                if ($problems !== []) {
                    $findings[$profile->id] = ['patient_id' => $profile->patient_id, 'findings' => $problems];
                }
            });

            return $this->render($findings);
        } catch (Throwable $e) {
            $this->error('Financial-risk audit failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * @param  array<int, array{patient_id:int, findings:array<int,string>}>  $findings
     */
    private function render(array $findings): int
    {
        if ($this->option('json')) {
            $this->line((string) json_encode([
                'profiles_with_findings' => count($findings),
                'total_findings' => collect($findings)->pluck('findings')->flatten()->count(),
                'findings' => collect($findings)->map(fn ($f, $id) => array_merge(['profile_id' => $id], $f))->values(),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('Patient financial-risk audit (read-only)');
        $this->info('Profiles with findings: '.count($findings));

        if ($findings === [] && ! $this->option('problems-only')) {
            $this->line('No anomalies detected for the selected filters.');
        } else {
            $this->table(['Profile', 'Patient', 'Findings'], collect($findings)->map(fn ($f, $id) => [
                $id, $f['patient_id'], implode(', ', $f['findings']),
            ])->all());
        }
        $this->line('Findings are advisory; no data was modified.');

        return self::SUCCESS;
    }
}
