<?php

namespace App\Console\Commands;

use App\Models\ConsultationMaternitySnapshot;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\ConsultationSpecialtyProfileMapping;
use App\Data\Consultation\Maternity\ConsultationMaternitySummaryProjection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

/**
 * Phase 14R.7 — READ-ONLY O&G/Maternity pilot preflight.
 *
 * Performs zero clinical writes and changes no flag. It reports what an
 * environment actually looks like so a human can decide whether to start a
 * pilot — it never grants approval itself, and it never claims clinician
 * acceptance.
 *
 * Verdicts: PASS · WARNING · BLOCKED.
 */
class ObgynMaternityPilotPreflightCommand extends Command
{
    protected $signature = 'maternity:obgyn-pilot-preflight
        {--format=table : table|json}
        {--output= : Write the report to this path}
        {--strict : Treat WARNING as a non-zero exit}
        {--environment-reconciliation= : Path to a reconciliation dry-run JSON report}
        {--batch= : Pilot batch id to verify}';

    protected $description = 'Read-only O&G/Maternity pilot readiness preflight. Makes no writes and changes no flag.';

    private const PASS = 'PASS';
    private const WARNING = 'WARNING';
    private const BLOCKED = 'BLOCKED';

    /** @var list<array{area:string,check:string,status:string,detail:string}> */
    private array $findings = [];

    public function handle(): int
    {
        $this->checkSchema();
        $this->checkFlags();
        $this->checkProfilesAndMappings();
        $this->checkPermissions();
        $this->checkReconciliation();
        $this->checkSnapshots();
        $this->checkCompletionIdentity();
        $this->checkBilling();
        $this->checkReturnContextRoutes();

        $verdict = $this->verdict();
        $report = [
            'verdict' => $verdict,
            'readiness' => $this->readiness($verdict),
            'clinician_acceptance' => 'NOT_ASSESSED — this command cannot evaluate clinical usability',
            'generated_at' => now()->toIso8601String(),
            'environment' => app()->environment(),
            'findings' => $this->findings,
        ];

        $this->emit($report);

        if ($verdict === self::BLOCKED) {
            return self::FAILURE;
        }

        return ($verdict === self::WARNING && $this->option('strict')) ? self::FAILURE : self::SUCCESS;
    }

    /* ── Checks ────────────────────────────────────────────────────────── */

    private function checkSchema(): void
    {
        $tables = [
            'consultation_maternity_links',
            'emergency_maternity_links',
            'admission_request_maternity_links',
            'admission_maternity_links',
            'consultation_maternity_snapshots',
        ];

        foreach ($tables as $table) {
            $this->add('schema', $table, Schema::hasTable($table) ? self::PASS : self::BLOCKED,
                Schema::hasTable($table) ? 'present' : 'MISSING — run migrations');
        }

        // The append-only guarantee is visible in the schema itself.
        if (Schema::hasTable('consultation_maternity_snapshots')) {
            $hasUpdatedAt = Schema::hasColumn('consultation_maternity_snapshots', 'updated_at');
            $hasDeletedAt = Schema::hasColumn('consultation_maternity_snapshots', 'deleted_at');

            $this->add('schema', 'snapshots append-only',
                (! $hasUpdatedAt && ! $hasDeletedAt) ? self::PASS : self::BLOCKED,
                (! $hasUpdatedAt && ! $hasDeletedAt)
                    ? 'no updated_at, no soft deletes'
                    : 'snapshot table has mutable/soft-delete columns');
        }

        $this->add('schema', 'snapshot schema version', self::PASS,
            'supported: '.ConsultationMaternitySummaryProjection::SCHEMA_VERSION);
    }

    private function checkFlags(): void
    {
        $flags = [
            'obstetrics_workspace' => 'consultation.maternity_context.obstetrics_workspace_enabled',
            'obstetrics_write_guard' => 'consultation.maternity_context.obstetrics_write_guard_enabled',
            'gynaecology_context' => 'consultation.maternity_context.gynaecology_context_enabled',
            'gynaecology_write_guard' => 'consultation.maternity_context.gynaecology_write_guard_enabled',
            'consultation_handoffs' => 'maternity.integration.consultation_handoffs_enabled',
            'emergency_context' => 'maternity.integration.emergency_context_enabled',
            'admission_context' => 'maternity.integration.admission_context_enabled',
            'emergency_handoffs' => 'maternity.integration.emergency_handoffs_enabled',
            'readiness' => 'consultation.maternity_context.readiness_enabled',
            'summary' => 'consultation.maternity_context.summary_projection_enabled',
            'completion_snapshot' => 'consultation.maternity_context.completion_snapshot_enabled',
            'billing_dedup_policy' => 'billing.maternity_billing.deduplication_policy_enabled',
        ];

        $values = [];
        foreach ($flags as $label => $key) {
            $values[$label] = (bool) config($key, false);
        }

        $this->add('flags', 'current values', self::PASS,
            collect($values)->map(fn (bool $v, string $k) => $k.'='.($v ? 'true' : 'false'))->implode(', '));

        // A write guard without its context flag would be an inert but
        // misleading configuration.
        if ($values['obstetrics_write_guard'] && ! $values['obstetrics_workspace']) {
            $this->add('flags', 'obstetrics guard', self::WARNING,
                'write guard enabled without the workspace flag — it is inert');
        }

        if ($values['gynaecology_write_guard'] && ! $values['gynaecology_context']) {
            $this->add('flags', 'gynaecology guard', self::WARNING,
                'write guard enabled without the context flag — it is inert');
        }

        if ($values['obstetrics_write_guard'] || $values['gynaecology_write_guard']) {
            $this->add('flags', 'write guards', self::WARNING,
                'a write guard is enabled — confirm the environment reconciliation review happened first');
        }
    }

    private function checkProfilesAndMappings(): void
    {
        foreach (['obstetrics', 'gynecology'] as $code) {
            $profile = ConsultationSpecialtyProfile::query()->where('code', $code)->first();

            $this->add('profiles', $code,
                $profile && $profile->is_active ? self::PASS : self::WARNING,
                $profile ? ($profile->is_active ? 'active' : 'INACTIVE') : 'missing');
        }

        $obstetricsMapping = ConsultationSpecialtyProfileMapping::query()
            ->active()
            ->whereNotNull('department_id')
            ->whereHas('profile', fn ($q) => $q->where('code', 'obstetrics'))
            ->exists();

        // K1 from 14R.5: without this mapping the Gynaecology → Obstetrics
        // referral correctly reports unavailable and offers the standard flow.
        $this->checkOrderSets();

        $this->add('profiles', 'obstetrics department mapping',
            $obstetricsMapping ? self::PASS : self::WARNING,
            $obstetricsMapping
                ? 'present'
                : 'absent — Gynaecology referral will use the K1 standard-flow fallback');
    }

    /**
     * The 14R.4 retargeting seeder converts the two known maternity-shaped
     * order-set items to `maternity_context_action`. It is environment-level
     * data, so an environment that has not run it still carries
     * `patch_specialty_entry` items. The service-boundary guard blocks those at
     * runtime, so this is a WARNING (stale configuration) not a BLOCKED
     * (unsafe) finding.
     */
    private function checkOrderSets(): void
    {
        if (! Schema::hasTable('consultation_specialty_order_set_items')) {
            return;
        }

        $maternitySections = [
            'obstetric_history', 'lmp_edd_gestational_age', 'current_pregnancy',
            'antenatal_vitals', 'fetal_assessment', 'risk_assessment', 'birth_plan',
        ];

        $unretargeted = \Illuminate\Support\Facades\DB::table('consultation_specialty_order_set_items')
            ->where('apply_mode', 'patch_specialty_entry')
            ->whereIn('target_section', $maternitySections)
            ->count();

        $retargeted = \Illuminate\Support\Facades\DB::table('consultation_specialty_order_set_items')
            ->where('apply_mode', 'maternity_context_action')
            ->count();

        $this->add('order_sets', 'maternity-shaped patch items',
            $unretargeted === 0 ? self::PASS : self::WARNING,
            $unretargeted === 0
                ? 'none remain'
                : $unretargeted.' item(s) still patch_specialty_entry — run ConsultationSpecialtyOrderSetMaternityReconciliationSeeder. '
                    .'The service-boundary guard blocks these at runtime, so this is stale config, not an unsafe write path.');

        $this->add('order_sets', 'retargeted actions', self::PASS,
            $retargeted.' maternity_context_action item(s)');
    }

    private function checkPermissions(): void
    {
        $required = [
            'consultation.maternity_context.view',
            'consultation.maternity_context.summary.view',
            'consultation.maternity_context.readiness.view',
            'consultation.maternity_context.create_admission_request',
            'emergency.maternity_context.view',
            'admission.maternity_context.view',
            'maternity.emergency_handoff.create',
        ];

        $missing = collect($required)->reject(
            fn (string $name) => Permission::query()->where('name', $name)->where('guard_name', 'web')->exists()
        )->values();

        $this->add('permissions', 'bridge permissions',
            $missing->isEmpty() ? self::PASS : self::BLOCKED,
            $missing->isEmpty() ? count($required).' present' : 'missing: '.$missing->implode(', '));

        // Every bridge permission is paired with a target permission in the
        // controllers; report the pairs an operator must also grant.
        $this->add('permissions', 'target pairing', self::PASS,
            'bridge actions additionally require maternity.*/admission.*/emergency.* permissions (enforced in controllers)');
    }

    private function checkReconciliation(): void
    {
        $path = $this->option('environment-reconciliation');

        if (! $path) {
            $this->add('reconciliation', 'environment report', self::WARNING,
                'not supplied — run maternity:reconcile-obgyn-entries --format=json --output=<path> and pass it here');

            return;
        }

        if (! is_file($path)) {
            $this->add('reconciliation', 'environment report', self::BLOCKED, 'file not found: '.$path);

            return;
        }

        $report = json_decode((string) file_get_contents($path), true);

        if (! is_array($report) || ($report['mode'] ?? null) !== 'dry_run') {
            $this->add('reconciliation', 'environment report', self::BLOCKED,
                'report is not a recognised dry-run output');

            return;
        }

        $counts = $report['counts'] ?? [];
        $this->add('reconciliation', 'counts', self::PASS,
            collect($counts)->map(fn ($v, $k) => "$k=$v")->implode(', '));

        $needsReview = (int) ($counts['conflict_requires_review'] ?? 0)
            + (int) ($counts['insufficient_context'] ?? 0);

        // Unresolved rows must never be silently approved.
        $this->add('reconciliation', 'unresolved rows',
            $needsReview === 0 ? self::PASS : self::WARNING,
            $needsReview === 0
                ? 'none'
                : $needsReview.' row(s) require human review before any write guard is enabled');
    }

    private function checkSnapshots(): void
    {
        if (! Schema::hasTable('consultation_maternity_snapshots')) {
            return;
        }

        $count = ConsultationMaternitySnapshot::query()->count();
        $this->add('snapshots', 'existing rows', self::PASS, (string) $count);

        // Verify a small sample rather than the whole table.
        $sample = ConsultationMaternitySnapshot::query()->latest('id')->limit(5)->get();
        $mismatched = $sample->reject(fn ($s) => $s->verifyPayloadHash())->count();

        $this->add('snapshots', 'hash sample',
            $mismatched === 0 ? self::PASS : self::BLOCKED,
            $mismatched === 0
                ? $sample->count().' sampled, all verified'
                : $mismatched.' of '.$sample->count().' sampled rows FAILED hash verification');

        // Structural proof that no mutation route exists.
        $mutating = collect(RouteFacade::getRoutes()->getRoutes())
            ->filter(fn ($r) => str_contains((string) $r->getName(), 'maternity-summary')
                && array_intersect($r->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']))
            ->count();

        $this->add('snapshots', 'mutation routes',
            $mutating === 0 ? self::PASS : self::BLOCKED,
            $mutating === 0 ? 'none (GET only)' : $mutating.' mutating route(s) found');
    }

    /**
     * Phase 14R.8 — risk P2: same-second completion identity.
     *
     * Before 14R.8, snapshot identity was `route:{id}@{completed_at}` — second
     * granular — so a reopen-and-recompletion inside one second was treated as
     * the same occurrence and no v2 was written. This check verifies the
     * INSTALLED capability that closes it. It is read-only: it inspects schema,
     * indexes and existing rows, and performs no clinical write.
     */
    private function checkCompletionIdentity(): void
    {
        $table = 'consultation_completion_occurrences';

        if (! Schema::hasTable($table)) {
            $this->add('completion identity', 'occurrence ledger', self::BLOCKED,
                'MISSING — risk P2 (same-second recompletion) is NOT closed; run migrations');

            return;
        }

        // Presence of the structure, not proof that P2 is closed. This command
        // performs no clinical write and therefore cannot execute a completion
        // cycle; closure is proven by the focused suite, not by this row.
        $this->add('completion identity', 'occurrence ledger', self::PASS,
            'present — Phase 14R.8 identity structure installed (closure is proven by the focused suite, not by this check)');

        // The identity column must exist and be a generated identifier, not a
        // timestamp derivative.
        $hasUid = Schema::hasColumn($table, 'occurrence_uid');
        $hasNumber = Schema::hasColumn($table, 'occurrence_number');

        $this->add('completion identity', 'occurrence identity columns',
            ($hasUid && $hasNumber) ? self::PASS : self::BLOCKED,
            ($hasUid && $hasNumber)
                ? 'occurrence_uid + occurrence_number present'
                : 'occurrence identity columns missing');

        // The uniqueness guarantee is what makes retries idempotent and
        // concurrent completions safe. Without it the ledger is decorative.
        $this->add('completion identity', 'uniqueness constraints',
            $this->hasUniqueIndexes($table, ['cco_uid_unique', 'cco_route_number_unique'])
                ? self::PASS
                : self::BLOCKED,
            $this->hasUniqueIndexes($table, ['cco_uid_unique', 'cco_route_number_unique'])
                ? 'cco_uid_unique + cco_route_number_unique installed'
                : 'required unique index missing — duplicate occurrences possible');

        // Append-only, exactly as the snapshot table is.
        $mutable = Schema::hasColumn($table, 'updated_at') || Schema::hasColumn($table, 'deleted_at');
        $this->add('completion identity', 'ledger append-only',
            $mutable ? self::BLOCKED : self::PASS,
            $mutable ? 'ledger has mutable/soft-delete columns' : 'no updated_at, no soft deletes');

        // Legacy compatibility: pre-14R.8 snapshots keep their timestamp
        // reference and are deliberately NOT backfilled. Their presence is
        // normal and must never be reported as a defect.
        if (Schema::hasTable('consultation_maternity_snapshots')) {
            $linkable = Schema::hasColumn('consultation_maternity_snapshots', 'completion_occurrence_id');

            $this->add('completion identity', 'snapshot lineage column',
                $linkable ? self::PASS : self::BLOCKED,
                $linkable ? 'completion_occurrence_id present (nullable)' : 'MISSING — snapshots cannot bind to an occurrence');

            if ($linkable) {
                $legacy = ConsultationMaternitySnapshot::query()
                    ->whereNull('completion_occurrence_id')->count();

                $this->add('completion identity', 'legacy snapshot compatibility', self::PASS,
                    $legacy === 0
                        ? 'no pre-14R.8 snapshots'
                        : $legacy.' pre-14R.8 snapshot(s) retained with legacy references — not backfilled by design');
            }
        }

        // The focused verification marker: P2 closure is claimed only when the
        // suite that proves it is actually installed.
        $suite = base_path('tests/Feature/ConsultationSnapshotCompletionIdentityPhase14R8Test.php');
        $this->add('completion identity', 'P2 verification suite',
            is_file($suite) ? self::PASS : self::WARNING,
            is_file($suite)
                ? 'ConsultationSnapshotCompletionIdentityPhase14R8Test present on disk — RUN IT to confirm closure; this check does not execute it'
                : 'focused P2 suite not found — closure is unverified in this checkout');
    }

    /** Read-only index inspection that works on both MariaDB/MySQL and SQLite. */
    private function hasUniqueIndexes(string $table, array $names): bool
    {
        try {
            $existing = collect(Schema::getIndexes($table))
                ->filter(fn ($index) => (bool) ($index['unique'] ?? false))
                ->pluck('name')
                ->map(fn ($name) => strtolower((string) $name))
                ->all();
        } catch (\Throwable) {
            // An unsupported driver must not fabricate a PASS.
            return false;
        }

        foreach ($names as $name) {
            if (! in_array(strtolower($name), $existing, true)) {
                return false;
            }
        }

        return true;
    }

    private function checkBilling(): void
    {
        $enabled = (bool) config('billing.maternity_billing.enabled', false);
        $autoPost = (bool) config('billing.maternity_billing.auto_post', false);

        $this->add('billing', 'MATERNITY_BILLING_ENABLED',
            $enabled ? self::BLOCKED : self::PASS, $enabled ? 'TRUE — posting must stay disabled' : 'false');

        $this->add('billing', 'MATERNITY_BILLING_AUTO_POST',
            $autoPost ? self::BLOCKED : self::PASS, $autoPost ? 'TRUE — must stay disabled' : 'false');

        $this->add('billing', 'de-duplication policy', self::PASS, collect([
            'policy' => config('billing.maternity_billing.deduplication_policy_enabled', false),
            'allow_both' => config('billing.maternity_billing.allow_both_when_configured', false),
            'allow_manual' => config('billing.maternity_billing.allow_manual_selection', false),
        ])->map(fn ($v, $k) => $k.'='.($v ? 'true' : 'false'))->implode(', '));
    }

    private function checkReturnContextRoutes(): void
    {
        $routes = [
            'admin.consultations.show',
            'admin.emergency.cases.show',
            'admin.admissions.show',
            'admin.maternity.labor.show',
            'admin.maternity.postnatal.show',
        ];

        $missing = collect($routes)->reject(fn (string $name) => RouteFacade::has($name))->values();

        $this->add('handoffs', 'return-context routes',
            $missing->isEmpty() ? self::PASS : self::WARNING,
            $missing->isEmpty() ? count($routes).' resolvable' : 'missing: '.$missing->implode(', '));
    }

    /* ── Output ────────────────────────────────────────────────────────── */

    private function add(string $area, string $check, string $status, string $detail): void
    {
        $this->findings[] = compact('area', 'check', 'status', 'detail');
    }

    private function verdict(): string
    {
        $statuses = array_column($this->findings, 'status');

        if (in_array(self::BLOCKED, $statuses, true)) {
            return self::BLOCKED;
        }

        return in_array(self::WARNING, $statuses, true) ? self::WARNING : self::PASS;
    }

    private function readiness(string $verdict): string
    {
        return match ($verdict) {
            self::BLOCKED => 'NOT_READY',
            self::WARNING => 'READY_FOR_TECHNICAL_PILOT_WITH_WARNINGS',
            default => 'READY_FOR_TECHNICAL_PILOT',
        };
    }

    /** @param array<string, mixed> $report */
    private function emit(array $report): void
    {
        $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';

        if ($path = $this->option('output')) {
            file_put_contents($path, $json);
        }

        if ($this->option('format') === 'json') {
            $this->line($json);

            return;
        }

        $this->table(['Area', 'Check', 'Status', 'Detail'], array_map(
            fn (array $f) => [$f['area'], $f['check'], $f['status'], $f['detail']],
            $this->findings
        ));

        $this->newLine();
        $this->line('Verdict:   '.$report['verdict']);
        $this->line('Readiness: '.$report['readiness']);
        $this->warn('Clinician acceptance: '.$report['clinician_acceptance']);
    }
}
