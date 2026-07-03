<?php

namespace App\Console\Commands;

use App\Services\Consultation\ConsultationBrowserFixtureService;
use Illuminate\Console\Command;
use Throwable;

class ConsultationE2EFixtureCommand extends Command
{
    protected $signature = 'consultation:e2e-fixture {--json : Output fixture metadata as JSON}';

    protected $description = 'Create or refresh the local/testing consultation workspace E2E fixture.';

    public function handle(ConsultationBrowserFixtureService $fixtures): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Consultation E2E fixture is only available locally or in testing.');

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

        $this->info('Consultation E2E fixture ready: '.$metadata['consultation_absolute_url']);
        $this->line('Login: '.$metadata['email'].' / '.$metadata['password']);
        $this->line('Metadata: '.$metadata['metadata_path']);

        return self::SUCCESS;
    }
}
