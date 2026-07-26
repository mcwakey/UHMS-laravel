<?php

namespace Tests\Feature;

use App\Models\ConsultationMaternityLink;
use App\Models\ConsultationSpecialtyEntry;
use App\Models\Patient;
use App\Models\PregnancyProfile;
use App\Services\Maternity\Reconciliation\ObgynEntryReconciliationService as Reconciler;
use App\Services\Maternity\Testing\ObgynMaternityPilotDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 14R.7 — pilot data, cleanup and SYNTHETIC classifier validation.
 *
 * The synthetic counts proved here are explicitly NOT environment
 * reconciliation counts; they exist only to show all five classifications can
 * be produced by the classifier.
 */
class ObgynMaternityPilotDataPhase14R7Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['audit_streaming.async_writes' => false]);
    }

    /* ── Seeding ───────────────────────────────────────────────────────── */

    public function test_pilot_seed_creates_marked_records_and_a_manifest(): void
    {
        $manifest = app(ObgynMaternityPilotDataService::class)->seed();

        $this->assertStringStartsWith(ObgynMaternityPilotDataService::MARKER, $manifest['batch_id']);
        $this->assertNotEmpty($manifest['scenarios']);

        // Every pilot patient carries the marker.
        $patients = Patient::query()->whereIn('id', $manifest['records']['patients'])->get();
        $this->assertGreaterThan(0, $patients->count());
        foreach ($patients as $patient) {
            $this->assertStringStartsWith(ObgynMaternityPilotDataService::MARKER, $patient->patient_number);
        }

        // The manifest is on disk and carries identifiers only.
        $path = ObgynMaternityPilotDataService::MANIFEST_DIR.'/'.$manifest['batch_id'].'.json';
        $this->assertTrue(Storage::disk('local')->exists($path));

        $raw = Storage::disk('local')->get($path);
        $this->assertStringNotContainsString('assessment', $raw);
        $this->assertStringNotContainsString('password', $raw);
    }

    public function test_all_twelve_scenarios_are_produced(): void
    {
        $manifest = app(ObgynMaternityPilotDataService::class)->seed();

        $this->assertSame(
            array_keys(ObgynMaternityPilotDataService::SCENARIOS),
            array_keys($manifest['scenarios'])
        );
    }

    public function test_scenario_o3_produces_a_genuinely_ambiguous_context(): void
    {
        $manifest = app(ObgynMaternityPilotDataService::class)->seed(['O3']);
        $routeId = $manifest['scenarios']['O3']['records']['consultation_route'];

        $context = app(\App\Services\Consultation\Maternity\ConsultationMaternityContextResolver::class)
            ->resolve(\App\Models\VisitConsultationRoute::findOrFail($routeId));

        $this->assertTrue($context->isAmbiguous());
    }

    public function test_scenario_g2_creates_no_pregnancy_profile(): void
    {
        $manifest = app(ObgynMaternityPilotDataService::class)->seed(['G2']);
        $patientId = $manifest['scenarios']['G2']['records']['patient'];

        $this->assertSame(0, PregnancyProfile::query()->where('patient_id', $patientId)->count());
        $this->assertSame(0, ConsultationMaternityLink::query()
            ->where('consultation_route_id', $manifest['scenarios']['G2']['records']['consultation_route'])
            ->count());
    }

    /* ── SYNTHETIC classifier validation ───────────────────────────────── */

    public function test_synthetic_pilot_data_produces_all_five_classifications(): void
    {
        // SYNTHETIC PILOT DATA — NOT ENVIRONMENT RECONCILIATION COUNTS.
        app(ObgynMaternityPilotDataService::class)->seed(['R1', 'R2', 'R3', 'R4', 'R5']);

        $rows = app(Reconciler::class)->scan();
        $counts = app(Reconciler::class)->summarise($rows);

        foreach ([
            Reconciler::SAFE_TO_LINK,
            Reconciler::SAFE_TO_MIGRATE,
            Reconciler::CONFLICT_REQUIRES_REVIEW,
            Reconciler::HISTORICAL_ONLY,
            Reconciler::INSUFFICIENT_CONTEXT,
        ] as $classification) {
            $this->assertGreaterThanOrEqual(
                1,
                $counts[$classification],
                "synthetic pilot data must produce at least one {$classification}"
            );
        }
    }

    public function test_the_reconciliation_report_stays_deterministic_over_pilot_data(): void
    {
        app(ObgynMaternityPilotDataService::class)->seed(['R1', 'R2', 'R3']);

        $first = app(Reconciler::class)->scan()->toJson();
        $second = app(Reconciler::class)->scan()->toJson();

        $this->assertSame($first, $second);
    }

    /* ── Cleanup ───────────────────────────────────────────────────────── */

    public function test_cleanup_removes_only_the_manifest_batch(): void
    {
        $manifest = app(ObgynMaternityPilotDataService::class)->seed(['O2', 'R1']);
        $pilotPatients = $manifest['records']['patients'];

        // A non-pilot patient that must survive untouched. Created after the
        // seed so the pilot user exists for the registered_by foreign key.
        $bystander = Patient::factory()->create([
            'patient_number' => 'REAL-0001',
            'registered_by' => \App\Models\User::query()->value('id'),
        ]);

        $this->artisan('maternity:clear-obgyn-pilot-data', [
            '--batch' => $manifest['batch_id'],
            '--force' => true,
        ])->assertExitCode(0);

        $this->assertSame(0, Patient::query()->whereIn('id', $pilotPatients)->count());
        $this->assertNotNull($bystander->fresh(), 'a non-pilot patient must never be removed');
        $this->assertSame(0, ConsultationSpecialtyEntry::query()
            ->whereIn('id', $manifest['records']['consultation_specialty_entries'] ?? [])->count());
    }

    public function test_cleanup_refuses_to_guess_without_a_manifest(): void
    {
        $manifest = app(ObgynMaternityPilotDataService::class)->seed(['O1']);
        $patientId = $manifest['records']['patients'][0];

        $this->artisan('maternity:clear-obgyn-pilot-data', [
            '--batch' => 'MT-OBGYN-14R7-does-not-exist',
            '--force' => true,
        ])->assertExitCode(0);

        // Nothing was removed: no manifest, no deletion.
        $this->assertNotNull(Patient::query()->find($patientId));
    }

    public function test_cleanup_dry_run_deletes_nothing(): void
    {
        $manifest = app(ObgynMaternityPilotDataService::class)->seed(['O2']);
        $before = Patient::query()->count();

        $this->artisan('maternity:clear-obgyn-pilot-data', [
            '--batch' => $manifest['batch_id'],
            '--dry-run' => true,
            '--force' => true,
        ])->assertExitCode(0);

        $this->assertSame($before, Patient::query()->count());
        $this->assertTrue(Storage::disk('local')->exists(
            ObgynMaternityPilotDataService::MANIFEST_DIR.'/'.$manifest['batch_id'].'.json'
        ));
    }

    /* ── Production guard ──────────────────────────────────────────────── */

    public function test_seeding_is_blocked_in_production(): void
    {
        app()->detectEnvironment(fn () => 'production');

        $this->artisan('maternity:seed-obgyn-pilot-data', ['--force' => true])
            ->assertExitCode(1);

        $this->artisan('maternity:clear-obgyn-pilot-data', ['--all' => true, '--force' => true])
            ->assertExitCode(1);
    }
}
