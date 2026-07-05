<?php

namespace App\Console\Commands;

use App\Enums\LogModule;
use App\Services\ActivityLogService;
use App\Services\Maternity\MaternityManualTestDataService;
use Illuminate\Console\Command;

class SeedMaternityManualTestDataCommand extends Command
{
    protected $signature = 'maternity:seed-manual-test-data
        {--count=1 : Number of maternity journeys to create}
        {--fresh-manual : Clear only MT-MAT manual maternity records before seeding}
        {--department= : Existing maternity department id to use}
        {--force : Required outside local/testing}';

    protected $description = 'Seed explicit maternity manual test data without touching default seeders.';

    public function handle(MaternityManualTestDataService $manualData, ActivityLogService $logger): int
    {
        if (app()->environment('production')) {
            $this->error('Maternity manual test seeding is disabled in production.');

            return self::FAILURE;
        }

        if (! app()->environment(['local', 'testing']) && ! $this->option('force')) {
            $this->error('Use --force outside local/testing environments.');

            return self::FAILURE;
        }

        if (! app()->environment('testing') && ! filter_var(env('UHMS_ALLOW_MANUAL_TEST_SEED', false), FILTER_VALIDATE_BOOL)) {
            $this->error('Set UHMS_ALLOW_MANUAL_TEST_SEED=true before running this command.');

            return self::FAILURE;
        }

        $count = max(1, (int) $this->option('count'));
        $created = $manualData->seed(
            $count,
            (bool) $this->option('fresh-manual'),
            $this->option('department') ? (int) $this->option('department') : null,
        );

        $logger->log(LogModule::MATERNITY, 'MATERNITY_MANUAL_TEST_DATA_SEEDED', [
            'metadata' => [
                'count' => $count,
                'fresh_manual' => (bool) $this->option('fresh-manual'),
                'created' => $created,
            ],
        ], null, 'Maternity manual test data seeded');

        $this->info('Maternity manual test data seeded.');
        foreach ($created as $key => $value) {
            $this->line($key.': '.$value);
        }

        return self::SUCCESS;
    }
}
