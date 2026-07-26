<?php

namespace App\Console\Commands;

use App\Services\Maternity\Reconciliation\ObgynEntryReconciliationService as Reconciler;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Phase 14R.6 — DRY-RUN ONLY historical O&G reconciliation report.
 *
 * Makes zero writes: no link, no pregnancy profile, no ANC visit, no specialty
 * entry change, no status change and no activity log. `--apply` is deliberately
 * refused in this phase and exits non-zero without touching anything.
 *
 * Console output shows aggregate counts by default. Detailed output carries
 * identifiers and structured field values only — never patient names or
 * clinical narrative.
 */
class ObgynEntryReconciliationCommand extends Command
{
    protected $signature = 'maternity:reconcile-obgyn-entries
        {--patient= : Restrict to one patient id}
        {--consultation= : Restrict to one consultation route id}
        {--profile= : Restrict to one pregnancy profile id}
        {--format=table : table|json|csv}
        {--output= : Write the report to this file instead of stdout}
        {--include-historical : Include entries classified historical_only in detail output}
        {--limit= : Maximum entries to scan}
        {--detailed : Show per-entry rows instead of aggregate counts}
        {--apply : NOT AVAILABLE in Phase 14R.6 — exits non-zero}';

    protected $description = 'Dry-run audit of historical O&G specialty entries against Maternity records (read-only).';

    public function handle(Reconciler $reconciler): int
    {
        if ($this->option('apply')) {
            $this->error(__('maternity_reconciliation.apply_mode_unavailable'));
            $this->line(__('maternity_reconciliation.no_database_changes'));

            // Refuse loudly and make no writes whatsoever.
            return self::FAILURE;
        }

        $rows = $reconciler->scan([
            'patient' => $this->option('patient') ? (int) $this->option('patient') : null,
            'consultation' => $this->option('consultation') ? (int) $this->option('consultation') : null,
            'profile' => $this->option('profile') ? (int) $this->option('profile') : null,
            'limit' => $this->option('limit') ? (int) $this->option('limit') : null,
        ]);

        if (! $this->option('include-historical')) {
            $detailRows = $rows->reject(fn (array $row) => $row['classification'] === Reconciler::HISTORICAL_ONLY);
        } else {
            $detailRows = $rows;
        }

        $counts = $reconciler->summarise($rows);
        $format = (string) $this->option('format');

        $payload = match ($format) {
            'json' => $this->json($counts, $detailRows),
            'csv' => $this->csv($detailRows),
            default => null,
        };

        if ($payload !== null) {
            $this->emit($payload);

            return self::SUCCESS;
        }

        $this->renderTable($counts, $detailRows);

        return self::SUCCESS;
    }

    /* ── Output ────────────────────────────────────────────────────────── */

    /** @param Collection<int, array<string, mixed>> $rows */
    private function renderTable(array $counts, Collection $rows): void
    {
        $this->info(__('maternity_reconciliation.dry_run_only'));
        $this->line(__('maternity_reconciliation.no_database_changes'));
        $this->newLine();

        $this->table(
            [__('maternity_reconciliation.classification'), __('maternity_reconciliation.count')],
            collect($counts)->map(fn (int $count, string $key) => [
                __('maternity_reconciliation.classifications.'.$key),
                $count,
            ])->values()->all()
        );

        if (! $this->option('detailed')) {
            return;
        }

        // Identifiers and codes only — no names, no narrative.
        $this->newLine();
        $this->table(
            ['route', 'patient', 'profile', 'section', 'entry', 'linked_profile', 'classification', 'reason'],
            $rows->map(fn (array $row) => [
                $row['consultation_route_id'],
                $row['patient_id'],
                $row['specialty_profile'],
                $row['section_key'],
                $row['entry_id'],
                $row['linked_pregnancy_profile_id'] ?? '—',
                $row['classification'],
                $row['reason_code'],
            ])->all()
        );
    }

    /** @param Collection<int, array<string, mixed>> $rows */
    private function json(array $counts, Collection $rows): string
    {
        return json_encode([
            'mode' => 'dry_run',
            'writes_performed' => 0,
            'generated_at' => now()->toIso8601String(),
            'counts' => $counts,
            'entries' => $rows->values()->all(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
    }

    /** @param Collection<int, array<string, mixed>> $rows */
    private function csv(Collection $rows): string
    {
        $headers = [
            'consultation_route_id', 'patient_id', 'specialty_profile', 'section_key',
            'entry_id', 'entry_recorded_at', 'linked_pregnancy_profile_id',
            'candidate_target_type', 'candidate_target_id', 'classification',
            'reason_code', 'recommended_action', 'parser_warnings', 'entry_hash',
        ];

        $lines = [implode(',', $headers)];

        foreach ($rows as $row) {
            $lines[] = implode(',', array_map(function (string $header) use ($row) {
                $value = $row[$header] ?? '';

                if (is_array($value)) {
                    $value = implode('|', $value);
                }

                return '"'.str_replace('"', '""', (string) $value).'"';
            }, $headers));
        }

        return implode("\n", $lines);
    }

    private function emit(string $payload): void
    {
        $path = $this->option('output');

        if ($path) {
            file_put_contents($path, $payload);
            $this->info(__('maternity_reconciliation.report_written', ['path' => $path]));

            return;
        }

        $this->line($payload);
    }
}
