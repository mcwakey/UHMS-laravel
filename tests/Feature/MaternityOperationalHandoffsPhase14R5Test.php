<?php

namespace Tests\Feature;

use App\Enums\ConsultationMaternityContextType;
use App\Enums\ConsultationMaternityLinkRole;
use App\Enums\LaborEpisodeStatus;
use App\Models\AdmissionMaternityLink;
use App\Models\AdmissionRequest;
use App\Models\AdmissionRequestMaternityLink;
use App\Models\EmergencyCase;
use App\Models\EmergencyMaternityLink;
use App\Models\LaborEpisode;
use App\Models\PostnatalCase;
use App\Models\PregnancyProfile;
use App\Services\Admissions\Maternity\AdmissionMaternityLinkService;
use App\Services\Admissions\Maternity\AdmissionRequestMaternityLinkService;
use App\Services\Emergency\Maternity\EmergencyMaternityLinkService;
use App\Services\Maternity\Context\MaternityContextTargetException;
use App\Services\Maternity\Context\MaternityContextTargetService;
use App\Services\Maternity\Context\MaternityLinkException;
use App\Services\Maternity\Handoffs\MaternityEmergencyHandoffService;
use App\Support\Maternity\MaternityReturnContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsMaternityHandoffFixtures;
use Tests\TestCase;

/**
 * Phase 14R.5 — shared target derivation, link lifecycle across all three new
 * bridges, Maternity → Emergency escalation, and return-context security.
 */
class MaternityOperationalHandoffsPhase14R5Test extends TestCase
{
    use BuildsMaternityHandoffFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['audit_streaming.async_writes' => false]);
        $this->handoffFlags(consultation: true, emergencyContext: true, admissionContext: true, emergencyHandoffs: true);
        $this->buildMaternityFixture();
    }

    /* ── Shared target descriptor (must preserve 14R.2 derivation) ─────── */

    public function test_descriptor_derives_context_type_root_and_owner_for_every_supported_target(): void
    {
        $targets = [
            [$this->profile, ConsultationMaternityContextType::PREGNANCY_PROFILE, 'pregnancy_profile_id'],
            [$this->ancVisit, ConsultationMaternityContextType::ANC_VISIT, 'antenatal_visit_id'],
            [$this->labor, ConsultationMaternityContextType::LABOR, 'labor_episode_id'],
        ];

        foreach ($targets as [$target, $expectedType, $expectedKey]) {
            $descriptor = app(MaternityContextTargetService::class)
                ->describe($target, $this->patient->id);

            $this->assertSame($expectedType, $descriptor->contextType);
            $this->assertSame((int) $target->getKey(), $descriptor->targetId);
            $this->assertSame($this->profile->id, $descriptor->pregnancyProfileId);
            $this->assertSame($this->patient->id, $descriptor->patientId);
            $this->assertSame((int) $target->getKey(), (int) $descriptor->foreignKeys[$expectedKey]);
        }
    }

    public function test_descriptor_resolves_postnatal_ownership_through_the_mother(): void
    {
        $descriptor = app(MaternityContextTargetService::class)
            ->describe($this->postnatal(), $this->patient->id);

        $this->assertSame(ConsultationMaternityContextType::POSTNATAL, $descriptor->contextType);
        // Owner is the MOTHER — the O&G bridge convention, unchanged.
        $this->assertSame($this->patient->id, $descriptor->patientId);
    }

    public function test_descriptor_fails_closed_on_patient_mismatch_and_unsupported_target(): void
    {
        $other = $this->otherPatient();

        try {
            app(MaternityContextTargetService::class)->describe($this->profile, $other->id);
            $this->fail('Expected a patient mismatch.');
        } catch (MaternityContextTargetException $e) {
            $this->assertSame(MaternityContextTargetException::PATIENT_MISMATCH, $e->errorCode);
        }

        try {
            app(MaternityContextTargetService::class)->describe($this->patient, $this->patient->id);
            $this->fail('Expected an unsupported target.');
        } catch (MaternityContextTargetException $e) {
            $this->assertSame(MaternityContextTargetException::UNSUPPORTED_TARGET, $e->errorCode);
        }
    }

    public function test_visit_mismatch_is_still_allowed_for_longitudinal_links(): void
    {
        // A profile from an earlier visit must remain linkable — maternity
        // records legitimately span visits (14R.2 rule, preserved).
        $this->profile->forceFill(['visit_id' => null])->save();

        $link = app(EmergencyMaternityLinkService::class)
            ->link($this->emergencyCase, $this->profile->fresh(), $this->user);

        $this->assertNotNull($link->id);
    }

    /* ── Link lifecycle, on every new bridge ───────────────────────────── */

    public function test_same_target_link_is_idempotent_on_every_bridge(): void
    {
        $bridges = [
            [app(EmergencyMaternityLinkService::class), $this->emergencyCase, EmergencyMaternityLink::class],
            [app(AdmissionRequestMaternityLinkService::class), $this->admissionRequest(), AdmissionRequestMaternityLink::class],
            [app(AdmissionMaternityLinkService::class), $this->admission(), AdmissionMaternityLink::class],
        ];

        foreach ($bridges as [$service, $source, $model]) {
            $first = $service->link($source, $this->profile, $this->user);
            $second = $service->link($source, $this->profile, $this->user);

            $this->assertSame($first->id, $second->id, $model.' link was not idempotent');
            $this->assertSame(1, $model::query()->count());
            $model::query()->delete();
        }
    }

    public function test_a_different_target_requires_an_explicit_relink_with_a_reason(): void
    {
        $service = app(EmergencyMaternityLinkService::class);
        $service->link($this->emergencyCase, $this->profile, $this->user);

        $second = $this->secondProfile();

        try {
            $service->link($this->emergencyCase, $second, $this->user);
            $this->fail('Expected relink to be required.');
        } catch (MaternityLinkException $e) {
            $this->assertSame(MaternityLinkException::RELINK_REQUIRED, $e->errorCode);
        }

        try {
            $service->relink($this->emergencyCase, $second, $this->user, '   ');
            $this->fail('Expected a reason to be required.');
        } catch (MaternityLinkException $e) {
            $this->assertSame(MaternityLinkException::REASON_REQUIRED, $e->errorCode);
        }

        $service->relink($this->emergencyCase, $second, $this->user, 'Wrong profile selected');

        $this->assertSame(1, EmergencyMaternityLink::query()->active()->count());
        $this->assertSame(1, EmergencyMaternityLink::query()->historical()->count());
    }

    public function test_unlink_preserves_history_and_ownership_is_fail_closed(): void
    {
        $service = app(AdmissionMaternityLinkService::class);
        $admission = $this->admission();

        $service->link($admission, $this->profile, $this->user);
        $service->unlink($admission, ConsultationMaternityContextType::PREGNANCY_PROFILE, $this->user, 'Corrected');

        $this->assertSame(0, AdmissionMaternityLink::query()->active()->count());
        $this->assertSame(1, AdmissionMaternityLink::query()->historical()->count());

        // Another patient's profile can never be linked.
        $foreign = PregnancyProfile::create([
            'patient_id' => $this->otherPatient()->id,
            'created_by' => $this->user->id,
            'profile_status' => 'active',
        ]);

        try {
            $service->link($admission, $foreign, $this->user);
            $this->fail('Expected a patient mismatch.');
        } catch (MaternityLinkException $e) {
            $this->assertSame(MaternityLinkException::PATIENT_MISMATCH, $e->errorCode);
        }
    }

    public function test_one_active_link_per_source_and_context_type(): void
    {
        $service = app(EmergencyMaternityLinkService::class);

        $service->link($this->emergencyCase, $this->profile, $this->user);
        $service->link($this->emergencyCase, $this->labor, $this->user, ConsultationMaternityLinkRole::HANDOFF);

        // Two DIFFERENT context types coexist; neither displaced the other.
        $this->assertSame(2, EmergencyMaternityLink::query()->active()->count());
        $this->assertSame(
            1,
            EmergencyMaternityLink::query()->active()->forContextType(ConsultationMaternityContextType::LABOR)->count()
        );
    }

    /* ── Maternity → Emergency escalation ──────────────────────────────── */

    public function test_labor_escalation_flag_alone_creates_no_emergency_case(): void
    {
        $before = EmergencyCase::query()->count();

        // Setting the clinical escalation indicator must have NO operational
        // side effect: no emergency case, no link. Only the explicit action does.
        $this->labor->forceFill(['emergency_escalation_required' => true])->save();

        $this->assertTrue((bool) $this->labor->fresh()->emergency_escalation_required);
        $this->assertSame($before, EmergencyCase::query()->count());
        $this->assertSame(0, EmergencyMaternityLink::query()->count());
    }

    public function test_explicit_escalation_creates_exactly_one_case_and_repeats_reuse_it(): void
    {
        // The fixture's own emergency case sits on a different visit, so this
        // escalation genuinely creates one rather than reusing it.
        $this->labor->forceFill(['visit_id' => null])->save();
        $service = app(MaternityEmergencyHandoffService::class);

        $first = $service->createOrReuseEmergencyCase($this->labor->fresh(), [], $this->user);
        $second = $service->createOrReuseEmergencyCase($this->labor->fresh(), [], $this->user);

        $this->assertFalse($first['reused']);
        $this->assertTrue($second['reused']);
        $this->assertSame($first['case']->id, $second['case']->id);
        // One pre-existing fixture case + exactly one created by escalation.
        $this->assertSame(2, EmergencyCase::query()->count());

        // The link exists and the maternity source is preserved untouched.
        $this->assertSame(1, EmergencyMaternityLink::query()
            ->forContextType(ConsultationMaternityContextType::LABOR)->active()->count());
        $this->assertSame(LaborEpisodeStatus::ACTIVE, $this->labor->fresh()->status);

        // No admission request and no theatre case are created automatically.
        $this->assertSame(0, AdmissionRequest::query()->count());
    }

    public function test_postnatal_escalation_is_supported_and_unsupported_sources_are_rejected(): void
    {
        $service = app(MaternityEmergencyHandoffService::class);
        $case = $this->postnatal();
        $case->forceFill(['visit_id' => null])->save();
        $before = EmergencyCase::query()->count();

        $result = $service->createOrReuseEmergencyCase($case->fresh(), [], $this->user);
        $this->assertFalse($result['reused']);
        $this->assertSame($before + 1, EmergencyCase::query()->count());

        try {
            $service->createOrReuseEmergencyCase($this->profile, [], $this->user);
            $this->fail('Expected an unsupported escalation source.');
        } catch (MaternityLinkException $e) {
            $this->assertSame(MaternityLinkException::UNSUPPORTED_TARGET, $e->errorCode);
        }
    }

    /* ── Return-context security ───────────────────────────────────────── */

    public function test_return_context_accepts_only_internal_named_routes(): void
    {
        $this->assertNotNull(MaternityReturnContext::make(
            MaternityReturnContext::MODULE_EMERGENCY,
            'admin.emergency.cases.show',
            ['emergencyCase' => $this->emergencyCase->id],
        ));

        // Unknown route name.
        $this->assertNull(MaternityReturnContext::make(
            MaternityReturnContext::MODULE_EMERGENCY, 'not.a.real.route', []
        ));

        // Real route, wrong module — cross-module escape is refused.
        $this->assertNull(MaternityReturnContext::make(
            MaternityReturnContext::MODULE_EMERGENCY, 'admin.maternity.labor.show', ['laborEpisode' => 1]
        ));

        // Unknown module.
        $this->assertNull(MaternityReturnContext::make('billing', 'admin.emergency.cases.show', []));
    }

    public function test_external_return_urls_are_rejected_outright(): void
    {
        foreach (['https://evil.example.com/steal', '//evil.example.com', 'javascript:alert(1)'] as $external) {
            $this->assertNull(MaternityReturnContext::fromArray([
                'return_module' => MaternityReturnContext::MODULE_EMERGENCY,
                'return_route' => $external,
            ]), $external.' should never be accepted as a return route');
        }
    }

    public function test_return_context_round_trips_and_sanitises_its_anchor(): void
    {
        $context = MaternityReturnContext::make(
            MaternityReturnContext::MODULE_ADMISSION,
            'admin.admissions.show',
            ['admission' => $this->admission()->id],
            'maternity-context"><script>',
        );

        $this->assertNotNull($context);
        $this->assertSame('maternity-contextscript', $context->anchor);
        $this->assertStringContainsString('#maternity-contextscript', (string) $context->url());

        $rebuilt = MaternityReturnContext::fromArray($context->toFormFields());
        $this->assertSame($context->url(), $rebuilt?->url());
    }

    public function test_resolution_never_creates_an_emergency_case_or_labor_episode(): void
    {
        $emergencyBefore = EmergencyCase::query()->count();
        $laborBefore = LaborEpisode::query()->count();
        $postnatalBefore = PostnatalCase::query()->count();

        app(\App\Services\Emergency\Maternity\EmergencyMaternityContextResolver::class)
            ->resolve($this->emergencyCase);
        app(\App\Services\Admissions\Maternity\AdmissionMaternityContextResolver::class)
            ->resolve($this->admission());

        $this->assertSame($emergencyBefore, EmergencyCase::query()->count());
        $this->assertSame($laborBefore, LaborEpisode::query()->count());
        $this->assertSame($postnatalBefore, PostnatalCase::query()->count());
        // Resolution NEVER persists a link on any bridge.
        $this->assertSame(0, EmergencyMaternityLink::query()->count());
        $this->assertSame(0, AdmissionMaternityLink::query()->count());
    }
}
