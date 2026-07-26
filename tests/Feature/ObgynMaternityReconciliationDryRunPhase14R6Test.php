<?php

namespace Tests\Feature;

use App\Models\AntenatalVisit;
use App\Models\ConsultationMaternityLink;
use App\Models\ConsultationSpecialtyEntry;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\PregnancyProfile;
use App\Models\VisitConsultationRoute;
use App\Services\Consultation\Maternity\ConsultationMaternityLinkService;
use App\Services\Maternity\Reconciliation\ObgynEntryReconciliationService as Reconciler;
use App\Services\Maternity\Reconciliation\ObgynEntryValueParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\BuildsMaternityHandoffFixtures;
use Tests\TestCase;

/**
 * Phase 14R.6 — DRY-RUN reconciliation. The load-bearing guarantee is that the
 * command and service make ZERO writes.
 */
class ObgynMaternityReconciliationDryRunPhase14R6Test extends TestCase
{
    use BuildsMaternityHandoffFixtures;
    use RefreshDatabase;

    private VisitConsultationRoute $route;

    protected function setUp(): void
    {
        parent::setUp();

        config(['audit_streaming.async_writes' => false]);
        $this->buildMaternityFixture();
        $this->route = $this->consultationRoute();
    }

    /* ── Zero writes ───────────────────────────────────────────────────── */

    public function test_command_defaults_to_dry_run_and_writes_nothing(): void
    {
        $this->entry('obstetric_history', ['gravida' => 2, 'para' => 1]);
        $before = $this->snapshotCounts();

        $writes = 0;
        DB::listen(function ($query) use (&$writes) {
            if (preg_match('/^\s*(insert|update|delete)/i', $query->sql)) {
                $writes++;
            }
        });

        $this->artisan('maternity:reconcile-obgyn-entries')->assertExitCode(0);

        $this->assertSame(0, $writes, 'The dry run must issue no write statement.');
        $this->assertSame($before, $this->snapshotCounts());
    }

    public function test_apply_mode_exits_non_zero_and_writes_nothing(): void
    {
        $this->entry('obstetric_history', ['gravida' => 2]);
        $before = $this->snapshotCounts();

        $this->artisan('maternity:reconcile-obgyn-entries --apply')->assertExitCode(1);

        $this->assertSame($before, $this->snapshotCounts());
    }

    public function test_specialty_entries_are_never_modified(): void
    {
        $entry = $this->entry('obstetric_history', ['gravida' => 2, 'para' => 1]);
        $original = $entry->fresh()->toArray();

        $this->artisan('maternity:reconcile-obgyn-entries --detailed')->assertExitCode(0);

        $this->assertSame($original, $entry->fresh()->toArray());
    }

    /* ── Classifications ───────────────────────────────────────────────── */

    public function test_matching_values_classify_safe_to_link(): void
    {
        $this->linkProfile();
        $this->entry('obstetric_history', ['gravida' => 2, 'para' => 1]);

        $this->assertSame(Reconciler::SAFE_TO_LINK, $this->classify('obstetric_history'));
    }

    public function test_conflicting_gravida_classifies_conflict(): void
    {
        $this->linkProfile();
        $this->entry('obstetric_history', ['gravida' => 7, 'para' => 1]);

        $this->assertSame(Reconciler::CONFLICT_REQUIRES_REVIEW, $this->classify('obstetric_history'));
    }

    public function test_conflicting_lmp_classifies_conflict(): void
    {
        $this->linkProfile();
        $this->entry('lmp_edd_gestational_age', ['lmp' => '2020-01-01']);

        $this->assertSame(Reconciler::CONFLICT_REQUIRES_REVIEW, $this->classify('lmp_edd_gestational_age'));
    }

    public function test_parseable_entry_without_a_target_classifies_safe_to_migrate(): void
    {
        AntenatalVisit::query()->delete();
        $this->linkProfile();
        $this->entry('antenatal_vitals', ['blood_pressure' => '120/80', 'fundal_height' => '32 cm']);

        $this->assertSame(Reconciler::SAFE_TO_MIGRATE, $this->classify('antenatal_vitals'));
    }

    public function test_unparseable_values_require_review(): void
    {
        $this->linkProfile();
        $this->entry('antenatal_vitals', ['blood_pressure' => 'high, sitting', 'fundal_height' => '32 weeks size']);

        $this->assertSame(Reconciler::CONFLICT_REQUIRES_REVIEW, $this->classify('antenatal_vitals'));
    }

    public function test_completed_consultation_without_a_profile_classifies_historical_only(): void
    {
        PregnancyProfile::query()->delete();
        $this->route->forceFill([
            'status' => VisitConsultationRoute::STATUS_COMPLETED,
            'completed_at' => now(),
        ])->save();
        $this->entry('obstetric_history', ['gravida' => 2]);

        $this->assertSame(Reconciler::HISTORICAL_ONLY, $this->classify('obstetric_history'));
    }

    public function test_empty_entry_classifies_insufficient_context(): void
    {
        $this->entry('obstetric_history', []);

        $this->assertSame(Reconciler::INSUFFICIENT_CONTEXT, $this->classify('obstetric_history'));
    }

    /* ── Scope ─────────────────────────────────────────────────────────── */

    public function test_consultation_owned_sections_are_excluded(): void
    {
        $this->linkProfile();

        foreach (['menstrual_history', 'current_complaints', 'action_plan', 'planned_place'] as $section) {
            $this->entry($section, ['lmp' => '2026-01-01', 'value' => 'x']);
        }

        $rows = app(Reconciler::class)->scan();

        $this->assertSame([], $rows->pluck('section_key')->intersect([
            'menstrual_history', 'current_complaints', 'action_plan', 'planned_place',
        ])->all());
    }

    public function test_hidden_legacy_sections_are_audited_but_unchanged(): void
    {
        $this->linkProfile();
        $entry = $this->entry('ultrasound_findings', ['finding' => 'normal']);
        $original = $entry->fresh()->toArray();

        $rows = app(Reconciler::class)->scan();

        $this->assertTrue($rows->contains('section_key', 'ultrasound_findings'));
        $this->assertSame($original, $entry->fresh()->toArray());
    }

    public function test_gynaecology_contributes_only_obstetric_history(): void
    {
        $this->linkProfile();
        $gyn = $this->specialty('gynecology');

        $this->entry('obstetric_history', ['gravida' => 2, 'para' => 1], $gyn);
        $this->entry('antenatal_vitals', ['blood_pressure' => '120/80'], $gyn);

        $sections = app(Reconciler::class)->scan()
            ->where('specialty_profile', 'gynecology')
            ->pluck('section_key')
            ->all();

        $this->assertSame(['obstetric_history'], array_values(array_unique($sections)));
    }

    /* ── Determinism and privacy ───────────────────────────────────────── */

    public function test_the_report_is_deterministic(): void
    {
        $this->linkProfile();
        $this->entry('obstetric_history', ['gravida' => 2, 'para' => 1]);
        $this->entry('antenatal_vitals', ['blood_pressure' => '120/80']);

        $first = app(Reconciler::class)->scan()->toJson();
        $second = app(Reconciler::class)->scan()->toJson();

        $this->assertSame($first, $second);
    }

    public function test_console_output_carries_no_patient_name(): void
    {
        $this->linkProfile();
        $this->entry('obstetric_history', ['gravida' => 2]);
        $name = $this->patient->first_name;

        $this->artisan('maternity:reconcile-obgyn-entries --detailed')
            ->doesntExpectOutputToContain($name)
            ->assertExitCode(0);
    }

    /* ── Parsers ───────────────────────────────────────────────────────── */

    public function test_blood_pressure_parser(): void
    {
        $parser = app(ObgynEntryValueParser::class);

        $this->assertSame(
            ['systolic' => 120, 'diastolic' => 80],
            $parser->bloodPressure('120/80')['value']
        );
        $this->assertSame(
            ObgynEntryValueParser::CONFIDENCE_SAFE_NORMALISED,
            $parser->bloodPressure('120 / 80')['confidence']
        );

        foreach (['high', '120/80 sitting', '120-80', ''] as $bad) {
            $this->assertSame(
                ObgynEntryValueParser::CONFIDENCE_UNPARSEABLE,
                $parser->bloodPressure($bad)['confidence'],
                $bad.' should be unparseable'
            );
        }
    }

    public function test_fundal_height_parser(): void
    {
        $parser = app(ObgynEntryValueParser::class);

        $this->assertSame(32.0, $parser->fundalHeight('32')['value']);
        $this->assertSame(32.5, $parser->fundalHeight('32.5 cm')['value']);
        $this->assertSame(
            ObgynEntryValueParser::CONFIDENCE_UNPARSEABLE,
            $parser->fundalHeight('32 weeks size')['confidence']
        );
    }

    public function test_gestational_age_parser_and_scan_dating_is_never_overridden(): void
    {
        $parser = app(ObgynEntryValueParser::class);

        $this->assertSame(227, $parser->gestationalAge('32+3')['value']['total_days']);

        $comparison = $parser->compareGestationalAge(
            $parser->gestationalAge('30')['value'], 32, 0, 'early_ultrasound'
        );

        $this->assertFalse($comparison['matches']);
        $this->assertContains('scan_dated_profile_not_overridden', $comparison['warnings']);
    }

    public function test_locale_ambiguous_dates_are_refused(): void
    {
        $parser = app(ObgynEntryValueParser::class);

        $this->assertSame('2026-01-05', $parser->date('2026-01-05')['value']);
        $this->assertSame(
            ObgynEntryValueParser::CONFIDENCE_UNPARSEABLE,
            $parser->date('03/04/2026')['confidence']
        );
    }

    /* ── Helpers ───────────────────────────────────────────────────────── */

    private function classify(string $section): ?string
    {
        return app(Reconciler::class)->scan()->firstWhere('section_key', $section)['classification'] ?? null;
    }

    private function entry(string $section, array $values, ?ConsultationSpecialtyProfile $profile = null): ConsultationSpecialtyEntry
    {
        return ConsultationSpecialtyEntry::create([
            'consultation_id' => $this->route->id,
            'consultation_specialty_profile_id' => ($profile ?? $this->specialty('obstetrics'))->id,
            'section_key' => $section,
            'entry' => $values,
            'created_by' => $this->user->id,
        ]);
    }

    private function linkProfile(): void
    {
        app(ConsultationMaternityLinkService::class)->link($this->route, $this->profile, $this->user);
    }

    private function specialty(string $code): ConsultationSpecialtyProfile
    {
        return ConsultationSpecialtyProfile::firstOrCreate(
            ['code' => $code],
            ['name' => ucfirst($code), 'is_active' => true, 'sort_order' => 40]
        );
    }

    /** @return array<string, int> */
    private function snapshotCounts(): array
    {
        return [
            'entries' => ConsultationSpecialtyEntry::query()->count(),
            'links' => ConsultationMaternityLink::query()->count(),
            'profiles' => PregnancyProfile::query()->count(),
            'anc' => AntenatalVisit::query()->count(),
            'activity' => DB::table('activity_log')->count(),
        ];
    }
}
