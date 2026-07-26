<?php

namespace Tests\Feature;

use App\Enums\AdmissionRequestSource;
use App\Enums\ConsultationMaternityContextType;
use App\Enums\ConsultationMaternityLinkRole;
use App\Enums\DepartmentType;
use App\Models\AdmissionRequest;
use App\Models\AdmissionRequestMaternityLink;
use App\Models\AntenatalVisit;
use App\Models\ConsultationMaternityLink;
use App\Models\ConsultationSpecialtyEntry;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\ConsultationSpecialtyProfileMapping;
use App\Models\Department;
use App\Models\InvoiceItem;
use App\Models\LaborEpisode;
use App\Models\PostnatalCase;
use App\Models\PregnancyProfile;
use App\Models\VisitConsultationRoute;
use App\Services\Consultation\Maternity\ConsultationMaternityAdmissionRequestService;
use App\Services\Consultation\Maternity\ConsultationMaternityContextResolver;
use App\Services\Consultation\Maternity\ConsultationMaternityHandoffPresenter;
use App\Services\Consultation\Maternity\ConsultationMaternityLinkService;
use App\Services\Consultation\Maternity\ConsultationPostnatalReviewService;
use App\Services\Consultation\Maternity\GynaecologyObstetricsReferralService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\BuildsMaternityHandoffFixtures;
use Tests\TestCase;

/**
 * Phase 14R.5, scenarios A / B / C / F — consultation-side handoffs.
 */
class ConsultationMaternityHandoffsPhase14R5Test extends TestCase
{
    use BuildsMaternityHandoffFixtures;
    use RefreshDatabase;

    private VisitConsultationRoute $route;

    protected function setUp(): void
    {
        parent::setUp();

        config(['audit_streaming.async_writes' => false]);
        $this->handoffFlags(consultation: true);
        $this->obgynFlags(obstetrics: true, gynaecology: true);
        $this->buildMaternityFixture();

        $this->route = $this->consultationRoute();
    }

    /* ── Scenario A — Obstetrics outpatient ────────────────────────────── */

    public function test_linked_obstetrics_consultation_creates_exactly_one_admission_request(): void
    {
        $this->linkProfile();

        $service = app(ConsultationMaternityAdmissionRequestService::class);
        $context = app(ConsultationMaternityContextResolver::class)->resolve($this->route);

        $first = $service->createOrReuse($this->route, $context, ['priority' => 'urgent'], $this->user);
        $second = $service->createOrReuse($this->route, $context, ['priority' => 'urgent'], $this->user);
        $third = $service->createOrReuse($this->route, $context, [], $this->user);

        $this->assertFalse($first['reused']);
        $this->assertTrue($second['reused'], 'A repeated action must reuse the open request.');
        $this->assertTrue($third['reused']);
        $this->assertSame($first['request']->id, $third['request']->id);
        $this->assertSame(1, AdmissionRequest::query()->count());
    }

    public function test_request_source_remains_consultation_and_receives_maternity_context(): void
    {
        $this->linkProfile();
        $context = app(ConsultationMaternityContextResolver::class)->resolve($this->route);

        $request = app(ConsultationMaternityAdmissionRequestService::class)
            ->createOrReuse($this->route, $context, [], $this->user)['request'];

        $this->assertSame(AdmissionRequestSource::CONSULTATION, $request->source_type);
        $this->assertSame($this->route->id, (int) $request->source_id);

        $links = AdmissionRequestMaternityLink::query()->forAdmissionRequest($request)->active()->get();
        $this->assertGreaterThanOrEqual(1, $links->count());
        $this->assertTrue($links->contains(fn ($link) => (int) $link->pregnancy_profile_id === $this->profile->id));
    }

    public function test_no_admission_no_billing_and_no_duplicate_clinical_records_are_created(): void
    {
        $this->linkProfile();
        $ancBefore = AntenatalVisit::query()->count();
        $laborBefore = LaborEpisode::query()->count();
        $entriesBefore = ConsultationSpecialtyEntry::query()->count();
        $invoiceBefore = InvoiceItem::query()->count();

        $context = app(ConsultationMaternityContextResolver::class)->resolve($this->route);
        app(ConsultationMaternityAdmissionRequestService::class)
            ->createOrReuse($this->route, $context, [], $this->user);

        $this->assertSame(0, \App\Models\Admission::query()->count(), 'No admission may be created.');
        $this->assertSame($ancBefore, AntenatalVisit::query()->count());
        $this->assertSame($laborBefore, LaborEpisode::query()->count());
        $this->assertSame($entriesBefore, ConsultationSpecialtyEntry::query()->count());
        $this->assertSame($invoiceBefore, InvoiceItem::query()->count());
    }

    public function test_a_closed_request_is_never_reused_as_open(): void
    {
        $this->linkProfile();
        $service = app(ConsultationMaternityAdmissionRequestService::class);
        $context = app(ConsultationMaternityContextResolver::class)->resolve($this->route);

        $first = $service->createOrReuse($this->route, $context, [], $this->user)['request'];
        $first->forceFill(['status' => \App\Enums\AdmissionRequestStatus::CANCELLED->value])->save();

        $second = $service->createOrReuse($this->route, $context, [], $this->user);

        $this->assertFalse($second['reused']);
        $this->assertNotSame($first->id, $second['request']->id);
        $this->assertSame(2, AdmissionRequest::query()->count());
    }

    public function test_full_consultation_notes_are_not_copied_into_the_request(): void
    {
        $this->linkProfile();
        $context = app(ConsultationMaternityContextResolver::class)->resolve($this->route);

        $request = app(ConsultationMaternityAdmissionRequestService::class)->createOrReuse(
            $this->route,
            $context,
            ['clinical_summary' => str_repeat('long clinical note ', 200)],
            $this->user
        )['request'];

        $this->assertLessThanOrEqual(501, mb_strlen((string) $request->clinical_summary));
    }

    /* ── Scenario B — Gynaecology, no pregnancy ────────────────────────── */

    public function test_gynaecology_without_pregnancy_requires_no_context_and_runs_no_handoff(): void
    {
        $handoffs = app(ConsultationMaternityHandoffPresenter::class)->build(
            $this->route,
            $this->gynaecologyProfile(),
            $this->userWithPermissions([
                'consultation.maternity_context.view',
                'consultation.maternity_context.refer_obstetrics',
                'consultations.create',
            ]),
        );

        $this->assertTrue($handoffs['enabled']);
        $this->assertFalse($handoffs['has_explicit_link']);
        $this->assertSame(0, ConsultationMaternityLink::query()->count());
        $this->assertSame(0, AdmissionRequest::query()->count());
    }

    /* ── Scenario C — Gynaecology discovers pregnancy ──────────────────── */

    public function test_linking_a_profile_leaves_the_specialty_as_gynaecology(): void
    {
        $this->linkProfile();

        // The route's department — and therefore its specialty — is unchanged.
        $this->assertSame($this->department->id, (int) $this->route->fresh()->department_id);
    }

    public function test_obstetrics_referral_preserves_the_source_route_and_its_entries(): void
    {
        $this->linkProfile();
        $this->makeObstetricsDepartment();

        $entry = ConsultationSpecialtyEntry::create([
            'consultation_id' => $this->route->id,
            'patient_id' => $this->patient->id,
            'section_key' => 'menstrual_history',
            'entry' => ['lmp' => now()->subWeeks(20)->toDateString()],
            'created_by' => $this->user->id,
        ]);

        $result = app(GynaecologyObstetricsReferralService::class)
            ->refer($this->route, $this->profile, $this->user);

        $this->assertSame(GynaecologyObstetricsReferralService::OUTCOME_CREATED, $result['outcome']);

        // Source route and entry are untouched.
        $this->assertSame($this->department->id, (int) $this->route->fresh()->department_id);
        $this->assertSame(
            VisitConsultationRoute::STATUS_ACTIVE,
            $this->route->fresh()->status
        );
        $this->assertSame($entry->entry, $entry->fresh()->entry);

        // The TARGET route carries the same pregnancy profile as a handoff.
        $targetLink = ConsultationMaternityLink::query()
            ->forConsultation($result['route'])
            ->active()
            ->first();
        $this->assertNotNull($targetLink);
        $this->assertSame(ConsultationMaternityLinkRole::HANDOFF, $targetLink->link_role);
        $this->assertSame($this->profile->id, (int) $targetLink->pregnancy_profile_id);
    }

    public function test_repeated_referral_does_not_duplicate_an_open_equivalent_route(): void
    {
        $this->linkProfile();
        $this->makeObstetricsDepartment();
        $service = app(GynaecologyObstetricsReferralService::class);

        $before = VisitConsultationRoute::query()->count();

        $first = $service->refer($this->route, $this->profile, $this->user);
        $second = $service->refer($this->route, $this->profile, $this->user);

        $this->assertSame($first['route']->id, $second['route']->id);
        $this->assertSame(GynaecologyObstetricsReferralService::OUTCOME_REUSED, $second['outcome']);
        $this->assertSame($before + 1, VisitConsultationRoute::query()->count());
    }

    public function test_referral_creates_no_anc_labor_or_admission_request(): void
    {
        $this->linkProfile();
        $this->makeObstetricsDepartment();

        $ancBefore = AntenatalVisit::query()->count();
        $laborBefore = LaborEpisode::query()->count();

        app(GynaecologyObstetricsReferralService::class)->refer($this->route, $this->profile, $this->user);

        $this->assertSame($ancBefore, AntenatalVisit::query()->count());
        $this->assertSame($laborBefore, LaborEpisode::query()->count());
        $this->assertSame(0, AdmissionRequest::query()->count());
    }

    public function test_referral_reports_unavailable_when_no_obstetrics_department_is_configured(): void
    {
        $this->linkProfile();

        $result = app(GynaecologyObstetricsReferralService::class)
            ->refer($this->route, $this->profile, $this->user);

        $this->assertSame(GynaecologyObstetricsReferralService::OUTCOME_UNAVAILABLE, $result['outcome']);
        $this->assertNull($result['route']);
    }

    /* ── Scenario F — Postnatal review ─────────────────────────────────── */

    public function test_consultation_links_an_existing_postnatal_case_read_only(): void
    {
        $case = $this->postnatal();
        $motherObsBefore = \App\Models\PostnatalMotherObservation::query()->count();

        $link = app(ConsultationPostnatalReviewService::class)
            ->linkForReview($this->route, $case, $this->user);

        $this->assertSame(ConsultationMaternityLinkRole::REVIEWED, $link->link_role);
        $this->assertSame(1, PostnatalCase::query()->count(), 'No second PostnatalCase may be created.');
        $this->assertSame($motherObsBefore, \App\Models\PostnatalMotherObservation::query()->count());

        $projection = app(ConsultationPostnatalReviewService::class)->projection($case->fresh());
        $this->assertSame($case->id, $projection['case_id']);
        $this->assertSame(
            __('maternity_handoffs.postnatal.readiness_advisory'),
            $projection['readiness']['advisory_note']
        );
        $this->assertSame([], $projection['mother_observations']);
    }

    public function test_postnatal_review_creates_no_specialty_entry_and_leaves_completion_alone(): void
    {
        $entriesBefore = ConsultationSpecialtyEntry::query()->count();
        $statusBefore = $this->route->status;

        app(ConsultationPostnatalReviewService::class)
            ->linkForReview($this->route, $this->postnatal(), $this->user);

        $this->assertSame($entriesBefore, ConsultationSpecialtyEntry::query()->count());
        $this->assertSame($statusBefore, $this->route->fresh()->status);
    }

    /* ── Permissions and lifecycle ─────────────────────────────────────── */

    public function test_admission_request_action_requires_both_permissions(): void
    {
        $this->linkProfile();

        $bridgeOnly = $this->userWithPermissions([
            'consultation.maternity_context.create_admission_request', 'consultations.create',
        ]);
        $targetOnly = $this->userWithPermissions(['admission.requests.create', 'consultations.create']);

        foreach ([$bridgeOnly, $targetOnly] as $user) {
            $this->actingAs($user)
                ->post(route('admin.consultations.maternity-context.admission-request', $this->visit), [])
                ->assertForbidden();
        }

        $this->assertSame(0, AdmissionRequest::query()->count());
    }

    public function test_completed_consultation_cannot_create_a_new_admission_request(): void
    {
        $this->linkProfile();
        $this->route->forceFill([
            'status' => VisitConsultationRoute::STATUS_COMPLETED,
            'completed_at' => now(),
        ])->save();

        $user = $this->userWithPermissions([
            'consultation.maternity_context.create_admission_request',
            'admission.requests.create',
            'consultations.create',
        ]);

        $this->actingAs($user)
            ->post(route('admin.consultations.maternity-context.admission-request', $this->visit), [])
            ->assertStatus(422);

        $this->assertSame(0, AdmissionRequest::query()->count());
    }

    public function test_handoff_actions_are_refused_while_the_integration_flag_is_off(): void
    {
        $this->handoffFlags();
        $this->linkProfile();

        $user = $this->userWithPermissions([
            'consultation.maternity_context.create_admission_request',
            'admission.requests.create',
            'consultations.create',
        ]);

        $this->actingAs($user)
            ->post(route('admin.consultations.maternity-context.admission-request', $this->visit), [])
            ->assertForbidden();

        $this->assertSame(0, AdmissionRequest::query()->count());
    }

    public function test_gynaecology_never_receives_anc_or_labor_mutations_from_the_handoff_panel(): void
    {
        $this->linkProfile();

        $user = $this->userWithPermissions([
            'consultation.maternity_context.view',
            'consultation.maternity_context.create_admission_request',
            'consultation.maternity_context.refer_obstetrics',
            'consultation.maternity_context.record_anc',
            'consultation.maternity_context.start_labor',
            'maternity.anc.record',
            'maternity.labor.start',
            'admission.requests.create',
            'consultations.create',
        ]);

        $handoffs = app(ConsultationMaternityHandoffPresenter::class)
            ->build($this->route, $this->gynaecologyProfile(), $user);

        // The Gynaecology panel exposes referral only — never ANC, labor or a
        // direct admission request.
        $this->assertTrue($handoffs['can_refer_obstetrics']);
        $this->assertFalse($handoffs['can_create_admission_request']);
        $this->assertArrayNotHasKey('can_record_anc', $handoffs);
        $this->assertArrayNotHasKey('can_start_labor', $handoffs);
    }

    /* ── Query budget ──────────────────────────────────────────────────── */

    public function test_disabled_handoffs_cost_zero_queries(): void
    {
        $this->handoffFlags();
        $presenter = app(ConsultationMaternityHandoffPresenter::class);
        $profile = $this->obstetricsProfile();
        $route = $this->route;

        DB::enableQueryLog();
        DB::flushQueryLog();
        $handoffs = $presenter->build($route, $profile, $this->user);
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertFalse($handoffs['enabled']);
        $this->assertSame(0, $queries);
    }

    /* ── Helpers ───────────────────────────────────────────────────────── */

    private function linkProfile(): void
    {
        app(ConsultationMaternityLinkService::class)
            ->link($this->route, $this->profile, $this->user);
    }

    private function obstetricsProfile(): ConsultationSpecialtyProfile
    {
        return ConsultationSpecialtyProfile::firstOrCreate(
            ['code' => 'obstetrics'],
            ['name' => 'Obstetrics', 'is_active' => true, 'sort_order' => 40]
        );
    }

    private function gynaecologyProfile(): ConsultationSpecialtyProfile
    {
        return ConsultationSpecialtyProfile::firstOrCreate(
            ['code' => 'gynecology'],
            ['name' => 'Gynecology', 'is_active' => true, 'sort_order' => 50]
        );
    }

    /** An Obstetrics consultation department mapped to the Obstetrics profile. */
    private function makeObstetricsDepartment(): Department
    {
        $department = Department::factory()->create([
            'name' => 'Obstetrics Clinic',
            'type' => DepartmentType::CONSULTATION->value,
            'status' => 'active',
        ]);

        ConsultationSpecialtyProfileMapping::create([
            'consultation_specialty_profile_id' => $this->obstetricsProfile()->id,
            'department_id' => $department->id,
            'is_active' => true,
            'priority' => 10,
        ]);

        return $department;
    }
}
