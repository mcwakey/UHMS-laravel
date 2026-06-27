<?php

namespace Tests\Feature;

use App\Console\Commands\ClearManualTestDataCommand;
use App\Console\Commands\SeedManualTestDataCommand;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ManualTestingSeeder;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ManualTestingSeederArchitectureTest extends TestCase
{
    public function test_manual_testing_seeder_is_not_called_by_default_database_seeder(): void
    {
        $contents = File::get(base_path('database/seeders/DatabaseSeeder.php'));

        $this->assertStringNotContainsString('ManualTestingSeeder', $contents);
        $this->assertTrue(class_exists(DatabaseSeeder::class));
        $this->assertTrue(class_exists(ManualTestingSeeder::class));
    }

    public function test_manual_seed_commands_exist(): void
    {
        $this->assertTrue(class_exists(SeedManualTestDataCommand::class));
        $this->assertTrue(class_exists(ClearManualTestDataCommand::class));
    }
}
