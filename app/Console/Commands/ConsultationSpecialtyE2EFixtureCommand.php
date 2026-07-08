<?php

namespace App\Console\Commands;

use App\Services\Consultation\Specialty\ConsultationSpecialtyBrowserFixtureService;
use Illuminate\Console\Command;
use Throwable;

class ConsultationSpecialtyE2EFixtureCommand extends Command
{
    protected $signature = 'consultation:specialty-e2e-fixture {--json : Output fixture metadata as JSON}';

    protected $description = 'Create or refresh local/testing specialist consultation workspace E2E fixtures.';

    public function handle(ConsultationSpecialtyBrowserFixtureService $fixtures): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Consultation specialty E2E fixture is only available locally or in testing.');

            return self::FAILURE;
        }

        try {
            $metadata = $fixtures->create();
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($this->option('json')) {
            $this->line(json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('Consultation specialty E2E fixtures ready.');
        $this->line('Admin login: '.$metadata['admin']['email'].' / '.$metadata['admin']['password']);
        $this->line('Profiles: '.count($metadata['profiles']));
        $this->line('Metadata: '.$metadata['metadata_path']);

        return self::SUCCESS;
    }
}
