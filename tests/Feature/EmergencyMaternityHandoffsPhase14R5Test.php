<?php

namespace Tests\Feature;

use App\Data\Maternity\OperationalMaternityContext;
use App\Enums\AdmissionRequestSource;
use App\Enums\ConsultationMaternityContextType;
use App\Models\AdmissionRequest;
use App\Models\AdmissionRequestMaternityLink;
use App\Models\EmergencyBayAssignment;
use App\Models\EmergencyMaternityLink;
use App\Models\InvoiceItem;
use App\Models\LaborEpisode;
use App\Models\PregnancyProfile;
use App\Services\Emergency\Maternity\EmergencyMaternityContextResolver;
use App\Services\Emergency\Maternity\EmergencyMaternityHandoffService;
use App\Services\Emergency\Maternity\EmergencyMaternityWorkspaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\BuildsMaternityHandoffFixtures;
use Tests\TestCase;

/**
 * Phase 14R.5, scenario D — Emergency obstetric case.
 */
class EmergencyMaternityHandoffsPhase14R5Test extends TestCase
{
    use BuildsMaternityHandoffFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['audit_streaming.async_writes' => false]);
        $this->handoffFlags(emergencyContext: true, emergencyHandoffs: true);
        $this->buildMaternityFixture();
    }

    /* ── Flag behaviour ────────────────────────────────────────────────── */

    public function test_context_feature_off_adds_zero_resolver_calls_and_zero_queries(): void
    {
        $this->handoffFlags();

        DB::enableQueryLog();
        DB::flushQueryLog();

        $model = app(EmergencyMaternityWorkspaceService::class)->build($this->emergencyCase, $this->user);

        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertFalse($model->shouldRender());
        $this->assertSame(0, $queries, 'A disabled Emergency card must issue no queries.');
    }

    public function test_handoff_actions_are_refused_while_the_flag_is_off(): void
    {
        $this->handoffFlags(emergencyContext: false, emergencyHandoffs: false);

        $user = $this->userWithPermissions([
            'emergency.maternity_context.link', 'maternity.pregnancy.view',
        ]);

        $this->actingAs($user)
            ->post(route('admin.emergency.cases.maternity-context.link', $this->emergencyCase), [
                'pregnancy_profile_id' => $this->profile->id,
            ])
            ->assertForbidden();

        $this->assertSame(0, EmergencyMaternityLink::query()->count());
    }

    /* ── Context resolution ────────────────────────────────────────────── */

    public function test_same_visit_context_is_only_ever_suggested_never_linked(): void
    {
        $context = app(EmergencyMaternityContextResolver::class)->resolve($this->emergencyCase);

        $this->assertSame(OperationalMaternityContext::STATUS_SUGGESTED, $context->status);
        $this->assertTrue($context->isSuggested());
        $this->assertFalse($context->isResolved());
        // Displayed only — nothing was persisted.
        $this->assertSame(0, EmergencyMaternityLink::query()->count());
    }

    public function test_no_context_is_inferred_from_a_pregnancy_related_complaint(): void
    {
        // A patient with NO pregnancy profile: an obstetric-sounding complaint
        // must not manufacture one.
        $visit = \App\Models\Visit::factory()->create([
            'patient_id' => $this->otherPatient()->id,
            'created_by' => $this->user->id,
            'current_department_id' => $this->department->id,
        ]);
        $case = $this->makeEmergencyCase($visit);
        $case->forceFill(['chief_complaint' => 'Severe abdominal pain in pregnancy'])->save();

        $context = app(EmergencyMaternityContextResolver::class)->resolve($case->fresh());

        $this->assertTrue($context->isNone());
        $this->assertSame(0, PregnancyProfile::query()->where('patient_id', $visit->patient_id)->count());
    }

    /* ── A. Link / create profile ──────────────────────────────────────── */

    public function test_explicit_pregnancy_profile_can_be_linked_and_patient_mismatch_is_blocked(): void
    {
        $user = $this->userWithPermissions([
            'emergency.maternity_context.link', 'maternity.pregnancy.view',
        ]);

        $this->actingAs($user)
            ->post(route('admin.emergency.cases.maternity-context.link', $this->emergencyCase), [
                'pregnancy_profile_id' => $this->profile->id,
            ])
            ->assertRedirect();

        $this->assertSame(1, EmergencyMaternityLink::query()->active()->count());

        $foreign = PregnancyProfile::create([
            'patient_id' => $this->otherPatient()->id,
            'created_by' => $this->user->id,
            'profile_status' => 'active',
        ]);

        $this->actingAs($user)
            ->post(route('admin.emergency.cases.maternity-context.link', $this->emergencyCase), [
                'pregnancy_profile_id' => $foreign->id,
            ])
            ->assertSessionHasErrors('pregnancy_profile_id');

        $this->assertSame(1, EmergencyMaternityLink::query()->active()->count());
    }

    /* ── B. Start / re-use Labor ───────────────────────────────────────── */

    public function test_start_labor_creates_exactly_one_episode_and_reuses_an_active_one(): void
    {
        // Clear the fixture's active episode so this genuinely creates one.
        $this->labor->forceFill(['status' => 'closed', 'closed_at' => now()])->save();

        $service = app(EmergencyMaternityHandoffService::class);
        $service->linkPregnancyProfile($this->emergencyCase, $this->profile, $this->user);

        $before = LaborEpisode::query()->count();

        $first = $service->startOrReuseLabor($this->emergencyCase, $this->profile, [], $this->user);
        $second = $service->startOrReuseLabor($this->emergencyCase, $this->profile, [], $this->user);

        $this->assertFalse($first['reused']);
        $this->assertTrue($second['reused'], 'An active labor episode must be reused, not duplicated.');
        $this->assertSame($first['episode']->id, $second['episode']->id);
        $this->assertSame($before + 1, LaborEpisode::query()->count());

        // The episode is linked to the emergency case exactly once.
        $this->assertSame(1, EmergencyMaternityLink::query()
            ->active()->forContextType(ConsultationMaternityContextType::LABOR)->count());
    }

    public function test_start_labor_requires_an_explicit_link_not_a_suggestion(): void
    {
        // Suggested context exists (same visit) but nothing is linked.
        $context = app(EmergencyMaternityContextResolver::class)->resolve($this->emergencyCase);
        $this->assertTrue($context->isSuggested());

        $this->expectException(\App\Services\Maternity\Context\MaternityLinkException::class);

        app(EmergencyMaternityHandoffService::class)
            ->startOrReuseLabor($this->emergencyCase, $this->profile, [], $this->user);
    }

    public function test_labor_is_never_started_automatically_from_danger_signs(): void
    {
        $this->labor->forceFill(['status' => 'closed'])->save();
        $before = LaborEpisode::query()->count();

        $this->emergencyCase->forceFill([
            'chief_complaint' => 'Contractions, ruptured membranes, heavy bleeding',
        ])->save();

        app(EmergencyMaternityContextResolver::class)->resolve($this->emergencyCase->fresh());
        app(EmergencyMaternityWorkspaceService::class)->build($this->emergencyCase->fresh(), $this->user);

        $this->assertSame($before, LaborEpisode::query()->count());
    }

    /* ── C. Admission request ──────────────────────────────────────────── */

    public function test_emergency_admission_request_keeps_its_emergency_source_and_carries_context(): void
    {
        $service = app(EmergencyMaternityHandoffService::class);
        $service->linkPregnancyProfile($this->emergencyCase, $this->profile, $this->user);

        $result = $service->createOrReuseAdmissionRequest($this->emergencyCase, [], $this->user);
        $request = $result['request'];

        $this->assertFalse($result['reused']);
        $this->assertSame(AdmissionRequestSource::EMERGENCY, $request->source_type);
        $this->assertSame($this->emergencyCase->id, (int) $request->source_id);

        // Clinical context lives in the separate link table, not in source_id.
        $this->assertSame(1, AdmissionRequestMaternityLink::query()
            ->forAdmissionRequest($request)->active()->count());
    }

    public function test_repeated_admit_action_does_not_duplicate_the_request(): void
    {
        $service = app(EmergencyMaternityHandoffService::class);
        $service->linkPregnancyProfile($this->emergencyCase, $this->profile, $this->user);

        $first = $service->createOrReuseAdmissionRequest($this->emergencyCase, [], $this->user);
        $second = $service->createOrReuseAdmissionRequest($this->emergencyCase, [], $this->user);
        $third = $service->createOrReuseAdmissionRequest($this->emergencyCase, [], $this->user);

        $this->assertSame($first['request']->id, $second['request']->id);
        $this->assertSame($first['request']->id, $third['request']->id);
        $this->assertSame(1, AdmissionRequest::query()->count());
        // Nor is the context link duplicated.
        $this->assertSame(1, AdmissionRequestMaternityLink::query()->active()->count());
    }

    public function test_existing_emergency_to_admission_behaviour_is_intact_without_maternity_context(): void
    {
        $case = $this->makeEmergencyCase(\App\Models\Visit::factory()->create([
            'patient_id' => $this->otherPatient()->id,
            'created_by' => $this->user->id,
            'current_department_id' => $this->department->id,
        ]));

        $result = app(EmergencyMaternityHandoffService::class)
            ->createOrReuseAdmissionRequest($case, ['priority' => 'urgent'], $this->user);

        $this->assertSame(AdmissionRequestSource::EMERGENCY, $result['request']->source_type);
        $this->assertSame(0, AdmissionRequestMaternityLink::query()->count());
    }

    /* ── Ownership and non-interference ────────────────────────────────── */

    public function test_bay_and_disposition_history_remain_intact_after_handoffs(): void
    {
        $bayBefore = EmergencyBayAssignment::query()->count();
        $statusBefore = $this->emergencyCase->emergency_status;
        $dispositionBefore = $this->emergencyCase->disposition;

        $service = app(EmergencyMaternityHandoffService::class);
        $service->linkPregnancyProfile($this->emergencyCase, $this->profile, $this->user);
        $service->createOrReuseAdmissionRequest($this->emergencyCase, [], $this->user);

        $fresh = $this->emergencyCase->fresh();

        $this->assertSame($bayBefore, EmergencyBayAssignment::query()->count());
        $this->assertSame($statusBefore, $fresh->emergency_status);
        $this->assertSame($dispositionBefore, $fresh->disposition);
    }

    public function test_no_invoice_item_is_created_by_any_maternity_handoff(): void
    {
        $before = InvoiceItem::query()->count();

        $service = app(EmergencyMaternityHandoffService::class);
        $service->linkPregnancyProfile($this->emergencyCase, $this->profile, $this->user);
        $service->createOrReuseAdmissionRequest($this->emergencyCase, [], $this->user);

        $this->assertSame($before, InvoiceItem::query()->count());
        $this->assertFalse((bool) config('billing.maternity_billing.enabled'));
    }

    /* ── Permissions ───────────────────────────────────────────────────── */

    public function test_bridge_permission_without_target_permission_is_blocked(): void
    {
        // Bridge only — missing maternity.pregnancy.view.
        $user = $this->userWithPermissions(['emergency.maternity_context.link']);

        $this->actingAs($user)
            ->post(route('admin.emergency.cases.maternity-context.link', $this->emergencyCase), [
                'pregnancy_profile_id' => $this->profile->id,
            ])
            ->assertForbidden();

        $this->assertSame(0, EmergencyMaternityLink::query()->count());
    }

    public function test_target_permission_without_bridge_permission_is_blocked(): void
    {
        // Target only — missing emergency.maternity_context.link.
        $user = $this->userWithPermissions(['maternity.pregnancy.view']);

        $this->actingAs($user)
            ->post(route('admin.emergency.cases.maternity-context.link', $this->emergencyCase), [
                'pregnancy_profile_id' => $this->profile->id,
            ])
            ->assertForbidden();

        $this->assertSame(0, EmergencyMaternityLink::query()->count());
    }

    /* ── Query budget ──────────────────────────────────────────────────── */

    public function test_context_resolves_at_most_once_per_request(): void
    {
        app(EmergencyMaternityHandoffService::class)
            ->linkPregnancyProfile($this->emergencyCase, $this->profile, $this->user);

        $resolver = app(EmergencyMaternityContextResolver::class);

        $resolver->resolve($this->emergencyCase);

        DB::enableQueryLog();
        DB::flushQueryLog();
        $resolver->resolve($this->emergencyCase);
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(0, $queries, 'The resolver must memoise per request.');
    }
}
