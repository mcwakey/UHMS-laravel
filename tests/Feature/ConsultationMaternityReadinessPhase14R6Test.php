<?php

namespace Tests\Feature;

use App\Data\Consultation\Maternity\ConsultationMaternityReadinessResult as Result;
use App\Enums\ConsultationMaternityLinkRole;
use App\Models\AntenatalVisit;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\PregnancyProfile;
use App\Models\VisitConsultationRoute;
use App\Services\Consultation\Maternity\ConsultationMaternityLinkService;
use App\Services\Consultation\Maternity\ConsultationMaternityReadinessService;
use App\Services\ConsultationRouteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\BuildsMaternityHandoffFixtures;
use Tests\TestCase;

/**
 * Phase 14R.6 — ADVISORY maternity readiness.
 *
 * The load-bearing guarantee: warnings never block completion, and Gynaecology
 * readiness is untouched.
 */
class ConsultationMaternityReadinessPhase14R6Test extends TestCase
{
    use BuildsMaternityHandoffFixtures;
    use RefreshDatabase;

    private VisitConsultationRoute $route;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'audit_streaming.async_writes' => false,
            'consultation.maternity_context.readiness_enabled' => true,
        ]);
        $this->buildMaternityFixture();
        $this->route = $this->consultationRoute();
    }

    /* ── Flag and scope ────────────────────────────────────────────────── */

    public function test_flag_off_invokes_nothing_and_issues_no_query(): void
    {
        config(['consultation.maternity_context.readiness_enabled' => false]);
        $service = app(ConsultationMaternityReadinessService::class);
        $profile = $this->specialty('obstetrics');
        $user = $this->readinessUser();

        DB::enableQueryLog();
        DB::flushQueryLog();
        $result = $service->evaluate($this->route, $profile, $user);
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(Result::STATUS_UNAVAILABLE, $result->status);
        $this->assertFalse($result->shouldRender());
        $this->assertSame(0, $queries);
    }

    public function test_gynaecology_never_receives_a_maternity_warning(): void
    {
        $this->link($this->profile);

        $result = $this->evaluate('gynecology');

        $this->assertSame(Result::STATUS_UNAVAILABLE, $result->status);
        $this->assertFalse($result->shouldRender());
    }

    public function test_permission_is_required_to_render(): void
    {
        $withoutPermission = $this->userWithPermissions(['consultations.view']);

        $result = app(ConsultationMaternityReadinessService::class)
            ->evaluate($this->route, $this->specialty('obstetrics'), $withoutPermission);

        $this->assertSame(Result::STATUS_UNAVAILABLE, $result->status);
    }

    /* ── Modes ─────────────────────────────────────────────────────────── */

    public function test_general_obstetrics_without_any_pregnancy_is_ready(): void
    {
        // A different patient entirely: nothing inferable, nothing to warn about.
        $visit = \App\Models\Visit::factory()->create([
            'patient_id' => $this->otherPatient()->id,
            'created_by' => $this->user->id,
            'current_department_id' => $this->department->id,
        ]);
        $route = VisitConsultationRoute::create([
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'department_id' => $this->department->id,
            'status' => VisitConsultationRoute::STATUS_ACTIVE,
            'routed_by' => $this->user->id,
            'started_at' => now(),
        ]);

        $result = app(ConsultationMaternityReadinessService::class)
            ->evaluate($route, $this->specialty('obstetrics'), $this->readinessUser());

        $this->assertTrue($result->isReady());
        $this->assertSame(Result::MODE_GENERAL, $result->mode);
        $this->assertFalse($result->hasWarnings());
    }

    public function test_explicit_anc_link_with_a_visit_is_ready(): void
    {
        $this->link($this->profile);
        $this->link($this->ancVisit, ConsultationMaternityLinkRole::REVIEWED);

        $result = $this->evaluate('obstetrics');

        $this->assertSame(Result::MODE_ANTENATAL_REVIEW, $result->mode);
        $this->assertTrue($result->isReady());
    }

    public function test_linked_profile_without_an_anc_visit_warns(): void
    {
        AntenatalVisit::query()->delete();
        $this->link($this->profile);

        $result = $this->evaluate('obstetrics');

        $this->assertSame(Result::MODE_ANTENATAL_REVIEW, $result->mode);
        $this->assertContains('anc_visit_not_recorded', $result->warningCodes);
    }

    public function test_labor_review_warns_when_the_episode_no_longer_matches_the_profile(): void
    {
        $this->link($this->profile);
        $this->link($this->labor, ConsultationMaternityLinkRole::HANDOFF);

        // Move the episode to a different pregnancy behind the link's back.
        $other = PregnancyProfile::create([
            'patient_id' => $this->patient->id,
            'created_by' => $this->user->id,
            'profile_status' => 'active',
        ]);
        DB::table('labor_episodes')->where('id', $this->labor->id)
            ->update(['pregnancy_profile_id' => $other->id]);

        $result = $this->evaluate('obstetrics');

        $this->assertSame(Result::MODE_LABOR_REVIEW, $result->mode);
        $this->assertContains('labor_episode_profile_mismatch', $result->warningCodes);
    }

    public function test_postnatal_review_warns_on_referral_and_missing_readiness(): void
    {
        $case = $this->postnatal();
        $case->forceFill(['referral_required' => true])->save();

        $this->link($this->profile);
        $this->link($case->fresh(), ConsultationMaternityLinkRole::REVIEWED);

        $result = $this->evaluate('obstetrics');

        $this->assertSame(Result::MODE_POSTNATAL_REVIEW, $result->mode);
        $this->assertContains('postnatal_referral_required', $result->warningCodes);
        $this->assertContains('postnatal_readiness_unavailable', $result->warningCodes);
    }

    public function test_unconfirmed_context_produces_an_advisory_warning(): void
    {
        // Same-visit maternity records exist, but nothing is linked.
        $result = $this->evaluate('obstetrics');

        $this->assertSame(Result::STATUS_WARNING, $result->status);
        $this->assertContains('context_confirmation_required', $result->warningCodes);
    }

    public function test_ambiguous_context_produces_an_advisory_warning(): void
    {
        AntenatalVisit::create([
            'pregnancy_profile_id' => $this->secondProfile()->id,
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'department_id' => $this->department->id,
            'recorded_by' => $this->user->id,
            'visit_date' => now(),
            'visit_number' => 1,
            'status' => 'recorded',
        ]);

        $result = $this->evaluate('obstetrics');

        $this->assertSame(Result::STATUS_WARNING, $result->status);
        $this->assertContains('context_ambiguous', $result->warningCodes);
    }

    /* ── Advisory guarantee ────────────────────────────────────────────── */

    public function test_warnings_never_block_completion(): void
    {
        AntenatalVisit::query()->delete();
        $this->link($this->profile);

        $result = $this->evaluate('obstetrics');
        $this->assertTrue($result->hasWarnings());
        $this->assertFalse($result->blocksCompletion());

        // And completion really does succeed.
        app(ConsultationRouteService::class)->completeRoute($this->route->fresh(), $this->user);

        $this->assertSame(
            VisitConsultationRoute::STATUS_COMPLETED,
            $this->route->fresh()->status
        );
    }

    public function test_readiness_creates_no_maternity_record(): void
    {
        $profiles = PregnancyProfile::query()->count();
        $ancs = AntenatalVisit::query()->count();

        $this->link($this->profile);
        $this->evaluate('obstetrics');

        $this->assertSame($profiles, PregnancyProfile::query()->count());
        $this->assertSame($ancs, AntenatalVisit::query()->count());
    }

    public function test_result_is_memoised_per_request(): void
    {
        $this->link($this->profile);
        $service = app(ConsultationMaternityReadinessService::class);
        $profile = $this->specialty('obstetrics');
        $user = $this->readinessUser();

        $service->evaluate($this->route, $profile, $user);

        DB::enableQueryLog();
        DB::flushQueryLog();
        $service->evaluate($this->route, $profile, $user);
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(0, $queries);
    }

    /* ── Helpers ───────────────────────────────────────────────────────── */

    private function evaluate(string $code): Result
    {
        return app(ConsultationMaternityReadinessService::class)
            ->evaluate($this->route->fresh(), $this->specialty($code), $this->readinessUser());
    }

    private function link($target, ConsultationMaternityLinkRole $role = ConsultationMaternityLinkRole::PRIMARY): void
    {
        app(ConsultationMaternityLinkService::class)
            ->link($this->route, $target, $this->user, $role);
    }

    private function specialty(string $code): ConsultationSpecialtyProfile
    {
        return ConsultationSpecialtyProfile::firstOrCreate(
            ['code' => $code],
            ['name' => ucfirst($code), 'is_active' => true, 'sort_order' => 40]
        );
    }

    private function readinessUser()
    {
        return $this->userWithPermissions([
            'consultation.maternity_context.readiness.view',
            'consultation.maternity_context.view',
        ]);
    }
}
