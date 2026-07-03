<?php

namespace Database\Seeders\Testing;

use App\Services\Consultation\ConsultationBrowserFixtureService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class ConsultationWorkspaceE2ESeeder extends Seeder
{
    public const USER_EMAIL = ConsultationBrowserFixtureService::USER_EMAIL;

    public const USER_PASSWORD = ConsultationBrowserFixtureService::USER_PASSWORD;

    public function run(): void
    {
        abort_unless(App::environment(['local', 'testing']), 403, 'Consultation E2E fixture is only available locally or in testing.');

        $metadata = app(ConsultationBrowserFixtureService::class)->create();

        $this->command?->info('Consultation E2E fixture ready: '.$metadata['consultation_absolute_url']);
        $this->command?->info('Login: '.self::USER_EMAIL.' / '.self::USER_PASSWORD);
        $this->command?->info('Metadata: '.$metadata['metadata_path']);
    }
}
