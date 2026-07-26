<?php

namespace App\Console\Commands;

use App\Services\Maternity\Testing\ObgynMaternityPilotDataService;
use Illuminate\Console\Command;

/**
 * Phase 14R.7 — seed isolated O&G/Maternity pilot data.
 *
 * Guarded exactly like the existing manual-data commands: blocked in
 * production, `--force` outside local/testing, and an explicit
 * `UHMS_ALLOW_MANUAL_TEST_SEED` opt-in. It touches no default seeder and
 * creates only records carrying the `MT-OBGYN-14R7-` marker.
 */
class SeedObgynMaternityPilotDataCommand extends Command
{
    protected $signature = 'maternity:seed-obgyn-pilot-data
        {--scenario=* : Scenario codes to seed (default: all)}
        {--department= : Existing consultation department id to use}
        {--fresh-manual : Clear previous MT-OBGYN-14R7 pilot batches first}
        {--json : Emit the manifest as JSON}
        {--output= : Write the manifest to this path as well}
        {--force : Required outside local/testing}';

    protected $description = 'Seed isolated O&G/Maternity pilot data (marker MT-OBGYN-14R7-). Never touches default seeders.';

    public function handle(ObgynMaternityPilotDataService $pilot): int
    {
        if (app()->environment('production')) {
            $this->error('O&G pilot seeding is disabled in production.');

            return self::FAILURE;
        }

        if (! app()->environment(['local', 'testing']) && ! $this->option('force')) {
            $this->error('Use --force outside local/testing environments.');

            return self::FAILURE;
        }

        if (! app()->environment('testing')
            && ! filter_var(env('UHMS_ALLOW_MANUAL_TEST_SEED', false), FILTER_VALIDATE_BOOL)) {
            $this->error('Set UHMS_ALLOW_MANUAL_TEST_SEED=true before running this command.');

            return self::FAILURE;
        }

        if ($this->option('fresh-manual')) {
            $cleared = $this->call('maternity:clear-obgyn-pilot-data', [
                '--all' => true,
                '--force' => true,
            ]);

            if ($cleared !== self::SUCCESS) {
                $this->error('Pilot cleanup failed; seeding aborted.');

                return self::FAILURE;
            }
        }

        $scenarios = $this->option('scenario') ?: null;

        $manifest = $pilot->seed(
            $scenarios,
            $this->option('department') ? (int) $this->option('department') : null,
        );

        $json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';

        if ($path = $this->option('output')) {
            file_put_contents($path, $json);
        }

        if ($this->option('json')) {
            $this->line($json);

            return self::SUCCESS;
        }

        $this->info('O&G pilot data seeded.');
        $this->line('Batch: '.$manifest['batch_id']);
        $this->line('Manifest: storage/app/'.ObgynMaternityPilotDataService::MANIFEST_DIR.'/'.$manifest['batch_id'].'.json');
        $this->newLine();

        $this->table(
            ['Scenario', 'Description', 'Patient', 'Consultation'],
            collect($manifest['scenarios'])->map(fn (array $row) => [
                $row['code'],
                $row['description'],
                $row['records']['patient_number'] ?? '—',
                $row['records']['consultation_route'] ?? '—',
            ])->values()->all()
        );

        return self::SUCCESS;
    }
}
