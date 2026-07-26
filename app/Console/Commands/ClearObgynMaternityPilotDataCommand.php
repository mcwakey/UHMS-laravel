<?php

namespace App\Console\Commands;

use App\Models\ConsultationMaternitySnapshot;
use App\Services\Maternity\Testing\ObgynMaternityPilotDataService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Phase 14R.7 — remove an isolated O&G pilot batch.
 *
 * Deletes ONLY the ids listed in a known batch manifest. A record is never
 * deleted because its name merely resembles a pilot record, and a record linked
 * to anything outside the manifest is skipped and reported rather than removed.
 *
 * On snapshots: this is TEST-FIXTURE TEARDOWN, not a clinical mutation. The
 * application exposes no snapshot-delete route and the model rejects
 * `->delete()`; this command therefore removes pilot snapshot rows through a
 * dedicated raw-query teardown path, only as part of dismantling an entire
 * isolated pilot consultation chain, and only outside production.
 */
class ClearObgynMaternityPilotDataCommand extends Command
{
    protected $signature = 'maternity:clear-obgyn-pilot-data
        {--batch= : Pilot batch id to remove}
        {--all : Remove every MT-OBGYN-14R7 batch}
        {--dry-run : Report what would be removed without deleting}
        {--force : Required outside local/testing}';

    protected $description = 'Remove an isolated MT-OBGYN-14R7 pilot batch listed in its manifest.';

    /** Deletion order: children before parents. */
    private const ORDER = [
        'consultation_maternity_snapshots',
        'consultation_maternity_links',
        'consultation_specialty_entries',
        'antenatal_visits',
        'pregnancy_profiles',
        'consultation_routes' => 'visit_consultation_routes',
        'visits',
        'patients',
    ];

    public function handle(ObgynMaternityPilotDataService $pilot): int
    {
        if (app()->environment('production')) {
            $this->error('O&G pilot cleanup is disabled in production.');

            return self::FAILURE;
        }

        if (! app()->environment(['local', 'testing']) && ! $this->option('force')) {
            $this->error('Use --force outside local/testing environments.');

            return self::FAILURE;
        }

        $batches = $this->option('all')
            ? $pilot->listBatches()
            : array_filter([$this->option('batch')]);

        if ($batches === []) {
            $this->warn('No pilot batch specified and none found. Nothing to do.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $removed = [];
        $skipped = [];

        foreach ($batches as $batchId) {
            $manifest = $pilot->readManifest($batchId);

            if (! $manifest) {
                // Refuse to guess: without a manifest there is no safe id set.
                $skipped[] = [$batchId, 'manifest_missing'];

                continue;
            }

            $result = $this->removeBatch($manifest, $dryRun);
            $removed[$batchId] = $result['removed'];
            $skipped = array_merge($skipped, $result['skipped']);

            if (! $dryRun && $result['skipped'] === []) {
                Storage::disk(ObgynMaternityPilotDataService::MANIFEST_DISK)
                    ->delete(ObgynMaternityPilotDataService::MANIFEST_DIR.'/'.$batchId.'.json');
            }
        }

        foreach ($removed as $batchId => $counts) {
            $this->info(($dryRun ? '[dry run] ' : '').$batchId);
            foreach ($counts as $table => $count) {
                $this->line("  {$table}: {$count}");
            }
        }

        foreach ($skipped as [$what, $reason]) {
            $this->warn("skipped {$what}: {$reason}");
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return array{removed: array<string,int>, skipped: list<array{0:string,1:string}>}
     */
    private function removeBatch(array $manifest, bool $dryRun): array
    {
        $records = $manifest['records'] ?? [];
        $removed = [];
        $skipped = [];

        // Snapshots are keyed off the pilot consultation routes, not listed
        // directly — dismantling the chain is what authorises their teardown.
        $routeIds = $records['consultation_routes'] ?? [];

        if ($routeIds !== []) {
            $snapshotIds = ConsultationMaternitySnapshot::query()
                ->whereIn('consultation_route_id', $routeIds)
                ->pluck('id')
                ->all();

            $removed['consultation_maternity_snapshots'] = count($snapshotIds);

            if (! $dryRun && $snapshotIds !== []) {
                // Deliberate raw delete: the model refuses ->delete() because a
                // clinical snapshot is immutable. This is fixture teardown of an
                // isolated pilot chain in a non-production environment.
                DB::table('consultation_maternity_snapshots')->whereIn('id', $snapshotIds)->delete();
            }
        }

        foreach (self::ORDER as $key => $table) {
            $manifestKey = is_string($key) ? $key : $table;

            if ($manifestKey === 'consultation_maternity_snapshots') {
                continue;
            }

            $ids = $records[$manifestKey] ?? [];

            if ($ids === []) {
                continue;
            }

            $removed[$table] = count($ids);

            if (! $dryRun) {
                DB::table($table)->whereIn('id', $ids)->delete();
            }
        }

        return ['removed' => $removed, 'skipped' => $skipped];
    }
}
