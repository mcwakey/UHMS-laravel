<?php

namespace App\Console\Commands;

use Database\Seeders\ManualTestingSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;

class SeedManualTestDataCommand extends Command
{
    protected $signature = 'uhms:seed-manual-test {--scale=small : small, medium, or large} {--force : Required in non-local environments}';

    protected $description = 'Seed explicit manual/stress-test data without touching the default launch seeder.';

    public function handle(): int
    {
        $scale = (string) $this->option('scale');
        if (! in_array($scale, ['small', 'medium', 'large'], true)) {
            $this->error('Invalid scale. Use small, medium, or large.');

            return self::FAILURE;
        }

        if (app()->environment('production')) {
            $this->error('Manual test seeding is disabled in production.');

            return self::FAILURE;
        }

        $allowed = filter_var(env('UHMS_ALLOW_MANUAL_TEST_SEED', false), FILTER_VALIDATE_BOOL);
        if (! $allowed) {
            $this->error('Set UHMS_ALLOW_MANUAL_TEST_SEED=true before running this command.');

            return self::FAILURE;
        }

        if (! app()->environment(['local', 'testing']) && ! $this->option('force')) {
            $this->error('Use --force outside local/testing environments.');

            return self::FAILURE;
        }

        Config::set('uhms.manual_testing.allow', true);
        Config::set('uhms.manual_testing.scale', $scale);

        $this->info("Seeding UHMS manual test data ({$scale})...");

        Artisan::call('db:seed', [
            '--class' => ManualTestingSeeder::class,
            '--force' => true,
        ], $this->output);

        $this->info('Manual test seed completed.');

        return self::SUCCESS;
    }
}
