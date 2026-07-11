<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PaymentGateCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_coverage_command_lists_wired_and_unwired_operations_as_valid_json_without_writes(): void
    {
        $before = ActivityLog::query()->count();
        $exit = Artisan::call('billing:payment-gate-coverage', ['--json' => true]);
        $payload = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(0, $exit);
        $this->assertGreaterThan(0, $payload['production_wired']);
        $this->assertGreaterThan(0, $payload['missing_production_wiring']);
        $this->assertContains('consultation.route.complete', array_column($payload['operations'], 'operation'));
        $this->assertContains('laboratory.result.enter', array_column($payload['operations'], 'operation'));
        $this->assertContains('pharmacy.item.dispense', array_column($payload['operations'], 'operation'));
        $this->assertSame($before, ActivityLog::query()->count());
    }
}
