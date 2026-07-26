<?php

namespace Tests\Feature;

use App\Data\Maternity\OperationalMaternityContext;
use App\Enums\AdmissionRequestSource;
use App\Enums\AdmissionRequestStatus;
use App\Enums\ConsultationMaternityContextType;
use App\Enums\ConsultationMaternityLinkRole;
use App\Models\Admission;
use App\Models\AdmissionMaternityLink;
use App\Models\AdmissionRequest;
use App\Models\NursingNote;
use App\Models\PregnancyProfile;
use App\Services\Admissions\AdmissionRequestService;
use App\Services\Admissions\Maternity\AdmissionMaternityContextPropagationService;
use App\Services\Admissions\Maternity\AdmissionMaternityContextResolver;
use App\Services\Admissions\Maternity\AdmissionMaternityLinkService;
use App\Services\Admissions\Maternity\AdmissionMaternityWorkspaceService;
use App\Services\Admissions\Maternity\AdmissionRequestMaternityLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\BuildsMaternityHandoffFixtures;
use Tests\TestCase;

/**
 * Phase 14R.5, scenario E — admitted obstetric patient.
 */
class AdmissionMaternityHandoffsPhase14R5Test extends TestCase
{
    use BuildsMaternityHandoffFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['audit_streaming.async_writes' => false]);
        $this->handoffFlags(admissionContext: true);
        $this->buildMaternityFixture();
    }

    /* ── Flag behaviour ────────────────────────────────────────────────── */

    public function test_context_feature_off_adds_zero_resolver_calls_and_zero_queries(): void
    {
        $this->handoffFlags();

        // Build the fixture BEFORE measuring so only the card is counted.
        $admission = $this->admission();
        $service = app(AdmissionMaternityWorkspaceService::class);

        DB::enableQueryLog();
        DB::flushQueryLog();

        $model = $service->build($admission, $this->user);

        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertFalse($model->shouldRender());
        $this->assertSame(0, $queries, 'A disabled Admission card must issue no queries.');
    }

    public function test_propagation_is_skipped_entirely_while_the_flag_is_off(): void
    {
        $this->handoffFlags();

        $request = $this->requestWithContext();

        $result = app(AdmissionMaternityContextPropagationService::class)
            ->propagate($request, $this->admission(), $this->user);

        $this->assertTrue($result['skipped']);
        $this->assertSame(0, AdmissionMaternityLink::query()->count());
    }

    /* ── Request → Admission propagation ───────────────────────────────── */

    public function test_request_context_propagates_to_the_admission_on_conversion(): void
    {
        $request = $this->requestWithContext();
        $admission = $this->admission();

        $result = app(AdmissionMaternityContextPropagationService::class)
            ->propagate($request, $admission, $this->user);

        $this->assertCount(1, $result['propagated']);
        $this->assertSame([], $result['conflicts']);

        $link = AdmissionMaternityLink::query()->forAdmission($admission)->active()->first();
        $this->assertNotNull($link);
        $this->assertSame(ConsultationMaternityLinkRole::HANDOFF, $link->link_role);
        // The originating request is recorded for audit.
        $this->assertSame($request->id, (int) $link->admission_request_id);

        // The request itself is preserved, source and all.
        $this->assertSame(AdmissionRequestSource::DIRECT, $request->fresh()->source_type);
    }

    public function test_repeated_propagation_is_idempotent(): void
    {
        $request = $this->requestWithContext();
        $admission = $this->admission();
        $service = app(AdmissionMaternityContextPropagationService::class);

        $service->propagate($request, $admission, $this->user);
        $second = $service->propagate($request->fresh(), $admission->fresh(), $this->user);

        $this->assertSame([], $second['propagated']);
        $this->assertCount(1, $second['reused']);
        $this->assertSame(1, AdmissionMaternityLink::query()->active()->count());
    }

    public function test_conflicting_existing_admission_id_is_never_overwritten(): void
    {
        $other = Admission::create([
            'admission_number' => Admission::generateAdmissionNumber(),
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'bed_id' => $this->bed->id,
            'admitted_by' => $this->user->id,
            'admission_date' => now()->subDay(),
            'status' => \App\Enums\AdmissionStatus::ADMITTED,
        ]);

        // The labor episode already belongs to ANOTHER admission.
        $this->labor->forceFill(['admission_id' => $other->id])->save();

        $request = $this->admissionRequest();
        app(AdmissionRequestMaternityLinkService::class)
            ->link($request, $this->labor->fresh(), $this->user);

        $result = app(AdmissionMaternityContextPropagationService::class)
            ->propagate($request, $this->admission(), $this->user);

        $this->assertNotEmpty($result['conflicts']);
        // Untouched.
        $this->assertSame($other->id, (int) $this->labor->fresh()->admission_id);
    }

    public function test_null_admission_id_is_adopted_but_the_pregnancy_profile_is_never_forced(): void
    {
        $this->labor->forceFill(['admission_id' => null])->save();
        $profileAdmissionBefore = $this->profile->admission_id;

        $request = $this->admissionRequest();
        $links = app(AdmissionRequestMaternityLinkService::class);
        $links->link($request, $this->profile, $this->user);
        $links->link($request, $this->labor->fresh(), $this->user);

        $admission = $this->admission();
        app(AdmissionMaternityContextPropagationService::class)
            ->propagate($request, $admission, $this->user);

        // Stage record adopts the admission …
        $this->assertSame($admission->id, (int) $this->labor->fresh()->admission_id);
        // … but the longitudinal profile column is left exactly as it was.
        $this->assertSame($profileAdmissionBefore, $this->profile->fresh()->admission_id);
    }

    public function test_conversion_propagates_context_in_the_same_transaction(): void
    {
        $request = AdmissionRequest::create([
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'source_type' => AdmissionRequestSource::MATERNITY->value,
            'source_id' => $this->labor->id,
            'requested_by' => $this->user->id,
            'status' => AdmissionRequestStatus::ACCEPTED->value,
            'requested_at' => now(),
        ]);

        app(AdmissionRequestMaternityLinkService::class)->link($request, $this->profile, $this->user);

        // AdmissionService::admit() falls back to the authenticated user for
        // admitted_by, matching how the real conversion endpoint runs. The
        // visit must already be ADMITTING, as it is after a real acceptance.
        $this->actingAs($this->user);
        $this->visit->forceFill(['status' => \App\Enums\VisitStatus::ADMITTING->value])->save();

        $admission = app(AdmissionRequestService::class)->convertToAdmission($request, [
            'admitted_by' => $this->user->id,
            'bed_id' => $this->bed->id,
        ], $this->user);

        $this->assertSame(1, AdmissionMaternityLink::query()->forAdmission($admission)->active()->count());
        // The operational origin survives conversion untouched.
        $this->assertSame(AdmissionRequestSource::MATERNITY, $request->fresh()->source_type);
        $this->assertSame($this->labor->id, (int) $request->fresh()->source_id);
    }

    /* ── Resolution order ──────────────────────────────────────────────── */

    public function test_explicit_links_win_and_request_context_is_the_next_source(): void
    {
        $admission = $this->admission();
        $resolver = app(AdmissionMaternityContextResolver::class);

        // 2. carried from the request
        $request = $this->requestWithContext();
        $admission->forceFill(['admission_request_id' => $request->id])->save();

        $context = $resolver->resolve($admission->fresh());
        $this->assertSame(OperationalMaternityContext::SOURCE_REQUEST, $context->resolutionSource);

        // 1. explicit link takes precedence
        app(AdmissionMaternityLinkService::class)->link($admission->fresh(), $this->profile, $this->user);

        $context = app(AdmissionMaternityContextResolver::class)->resolve($admission->fresh());
        $this->assertSame(OperationalMaternityContext::SOURCE_EXPLICIT, $context->resolutionSource);
        $this->assertTrue($context->isResolved());
    }

    public function test_multiple_pregnancy_profiles_are_ambiguous_never_latest(): void
    {
        $this->secondProfile();
        $admission = $this->admission();

        $context = app(AdmissionMaternityContextResolver::class)->resolve($admission);

        $this->assertTrue($context->isAmbiguous());
        $this->assertNull($context->pregnancyProfile);
        $this->assertSame(2, $context->candidateProfiles?->count());
    }

    public function test_direct_non_maternity_admission_is_unaffected_and_uses_the_fast_path(): void
    {
        $admission = Admission::create([
            'admission_number' => Admission::generateAdmissionNumber(),
            'patient_id' => $this->otherPatient()->id,
            'visit_id' => \App\Models\Visit::factory()->create([
                'patient_id' => $this->otherPatient()->id,
                'created_by' => $this->user->id,
            ])->id,
            'bed_id' => $this->bed->id,
            'admitted_by' => $this->user->id,
            'admission_date' => now(),
            'status' => \App\Enums\AdmissionStatus::ADMITTED,
        ]);

        DB::enableQueryLog();
        DB::flushQueryLog();
        $context = app(AdmissionMaternityContextResolver::class)->resolve($admission);
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertTrue($context->isNone());
        $this->assertLessThanOrEqual(
            4,
            $queries,
            'A non-maternity admission must take the fast no-context path.'
        );
    }

    /* ── Ownership ─────────────────────────────────────────────────────── */

    public function test_admission_context_management_never_creates_maternity_records(): void
    {
        $profilesBefore = PregnancyProfile::query()->count();
        $laborBefore = \App\Models\LaborEpisode::query()->count();
        $ancBefore = \App\Models\AntenatalVisit::query()->count();

        $user = $this->userWithPermissions([
            'admission.maternity_context.link', 'maternity.pregnancy.view',
        ], baseline: ['ward.view']);

        $this->actingAs($user)
            ->post(route('admin.admissions.maternity-context.link', $this->admission()), [
                'pregnancy_profile_id' => $this->profile->id,
            ])
            ->assertRedirect();

        $this->assertSame(1, AdmissionMaternityLink::query()->active()->count());
        $this->assertSame($profilesBefore, PregnancyProfile::query()->count());
        $this->assertSame($laborBefore, \App\Models\LaborEpisode::query()->count());
        $this->assertSame($ancBefore, \App\Models\AntenatalVisit::query()->count());
    }

    public function test_bed_nursing_and_discharge_remain_admission_owned(): void
    {
        $admission = $this->admission();
        $bedBefore = $admission->bed_id;
        $statusBefore = $admission->status;
        $notesBefore = NursingNote::query()->count();

        app(AdmissionMaternityLinkService::class)->link($admission, $this->profile, $this->user);
        app(AdmissionMaternityContextPropagationService::class)
            ->propagate($this->requestWithContext(), $admission, $this->user);

        $fresh = $admission->fresh();
        $this->assertSame($bedBefore, $fresh->bed_id);
        $this->assertSame($statusBefore, $fresh->status);
        $this->assertSame($notesBefore, NursingNote::query()->count());
    }

    public function test_relink_corrects_an_incorrectly_propagated_context(): void
    {
        $admission = $this->admission();
        $service = app(AdmissionMaternityLinkService::class);

        $service->link($admission, $this->profile, $this->user);
        $service->relink($admission, $this->secondProfile(), $this->user, 'Wrong context propagated');

        $active = AdmissionMaternityLink::query()->forAdmission($admission)->active()->first();

        $this->assertSame($this->secondProfile()->id, (int) $active->pregnancy_profile_id);
        $this->assertSame(1, AdmissionMaternityLink::query()->historical()->count());
    }

    /* ── Discharge readiness stays advisory ────────────────────────────── */

    public function test_postnatal_readiness_stays_advisory_by_default(): void
    {
        $this->assertFalse(
            (bool) config('admissions.discharge.require_postnatal_ready_before_discharge', false),
            'Phase 14R.5 must not change the discharge enforcement default.'
        );

        $admission = $this->admission();
        $case = $this->postnatal();
        $case->forceFill(['admission_id' => null])->save();

        // Explicit context makes the postnatal case resolvable to this admission.
        app(AdmissionMaternityLinkService::class)
            ->link($admission, $case->fresh(), $this->user, ConsultationMaternityLinkRole::HANDOFF);

        $readiness = app(\App\Services\Admissions\AdmissionDischargeReadinessService::class)
            ->forAdmission($admission->fresh());

        $postnatal = collect($readiness['areas'] ?? [])->firstWhere('label', __('admissions.postnatal_readiness'));

        $this->assertNotNull($postnatal, 'A postnatal readiness area should be present.');
        $this->assertSame(1, $postnatal['meta']['case_count'] ?? 0);
        $this->assertFalse($readiness['enforcement']['postnatal_required']);
    }

    /* ── Permissions ───────────────────────────────────────────────────── */

    public function test_admission_bridge_requires_both_permissions(): void
    {
        $bridgeOnly = $this->userWithPermissions(['admission.maternity_context.link'], baseline: ['ward.view']);
        $targetOnly = $this->userWithPermissions(['maternity.pregnancy.view'], baseline: ['ward.view']);

        foreach ([$bridgeOnly, $targetOnly] as $user) {
            $this->actingAs($user)
                ->post(route('admin.admissions.maternity-context.link', $this->admission()), [
                    'pregnancy_profile_id' => $this->profile->id,
                ])
                ->assertForbidden();
        }

        $this->assertSame(0, AdmissionMaternityLink::query()->count());
    }

    /* ── Helpers ───────────────────────────────────────────────────────── */

    private function requestWithContext(): AdmissionRequest
    {
        $request = $this->admissionRequest();

        app(AdmissionRequestMaternityLinkService::class)
            ->link($request, $this->profile, $this->user, ConsultationMaternityLinkRole::HANDOFF);

        return $request->fresh();
    }
}
