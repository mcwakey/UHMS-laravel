<?php

namespace Tests\Feature;

use App\Data\Maternity\MaternityHandoffActionViewModel as Action;
use App\Models\AdmissionRequest;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\ConsultationSpecialtyProfileMapping;
use App\Models\Department;
use App\Models\EmergencyCase;
use App\Models\InvoiceItem;
use App\Models\LaborEpisode;
use App\Models\PostnatalCase;
use App\Models\PregnancyProfile;
use App\Models\VisitConsultationRoute;
use App\Services\Admissions\Maternity\AdmissionMaternityLinkService;
use App\Services\Admissions\Maternity\AdmissionMaternityWorkspaceService;
use App\Services\Consultation\Maternity\ConsultationMaternityHandoffPresenter;
use App\Services\Consultation\Maternity\ConsultationMaternityLinkService;
use App\Services\Emergency\Maternity\EmergencyMaternityHandoffService;
use App\Services\Emergency\Maternity\EmergencyMaternityLinkService;
use App\Services\Emergency\Maternity\EmergencyMaternityWorkspaceService;
use App\Services\Maternity\Handoffs\MaternityEmergencyHandoffPresenter;
use App\Support\Maternity\MaternityReturnContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Tests\Feature\Concerns\BuildsMaternityHandoffFixtures;
use Tests\TestCase;

/**
 * Phase 14R.5.1 — handoff UI behaviour: honest states, real forms, visible
 * idempotency, safe returns and lifecycle/permission boundaries.
 */
class MaternityHandoffUiPhase14R5_1Test extends TestCase
{
    use BuildsMaternityHandoffFixtures;
    use RefreshDatabase;

    private VisitConsultationRoute $route;

    protected function setUp(): void
    {
        parent::setUp();

        config(['audit_streaming.async_writes' => false]);
        $this->handoffFlags(consultation: true, emergencyContext: true, admissionContext: true, emergencyHandoffs: true);
        $this->obgynFlags(obstetrics: true, gynaecology: true);
        $this->buildMaternityFixture();
        $this->route = $this->consultationRoute();

        View::share('errors', new ViewErrorBag);
    }

    /* ── Consultation → Admission Request ──────────────────────────────── */

    public function test_admission_request_modal_renders_a_real_form_posting_to_the_real_route(): void
    {
        $this->linkConsultationProfile();
        $user = $this->fullUser();

        $html = $this->consultationHtml('obstetrics', $user);

        $this->assertStringContainsString('id="consultationMaternityAdmissionRequestModal"', $html);
        $this->assertStringContainsString(
            'action="'.route('admin.consultations.maternity-context.admission-request', $this->visit).'"',
            $html
        );
        // Safe handover fields only — never the full note.
        foreach (['name="priority"', 'name="requested_ward_id"', 'name="provisional_diagnosis"', 'name="clinical_summary"'] as $field) {
            $this->assertStringContainsString($field, $html);
        }
        $this->assertStringContainsString('name="return_route"', $html);
    }

    public function test_form_submission_creates_then_reuses_the_request_without_side_effects(): void
    {
        $this->linkConsultationProfile();
        $user = $this->fullUser();

        $payload = ['priority' => 'urgent', 'clinical_summary' => 'Handover line'];

        $this->actingAs($user)
            ->post(route('admin.consultations.maternity-context.admission-request', $this->visit), $payload)
            ->assertRedirect();
        $this->actingAs($user)
            ->post(route('admin.consultations.maternity-context.admission-request', $this->visit), $payload)
            ->assertRedirect();

        $this->assertSame(1, AdmissionRequest::query()->count());
        $this->assertSame(0, \App\Models\Admission::query()->count());
        $this->assertSame(0, InvoiceItem::query()->count());
    }

    public function test_open_request_switches_the_action_to_open_existing(): void
    {
        $this->linkConsultationProfile();
        $user = $this->fullUser();

        $this->actingAs($user)
            ->post(route('admin.consultations.maternity-context.admission-request', $this->visit), []);

        $action = $this->consultationActions('obstetrics', $user)['create_admission_request'];

        $this->assertSame(Action::STATE_EXISTING_RECORD, $action->state);
        $this->assertTrue($action->reusesExistingRecord());
        $this->assertSame(
            __('maternity_handoffs.consultation.open_existing_admission_request'),
            $action->label
        );
        $this->assertNotNull($action->existingRecord['url']);
    }

    public function test_completed_consultation_renders_blocked_guidance_not_a_form(): void
    {
        $this->linkConsultationProfile();
        $this->route->forceFill([
            'status' => VisitConsultationRoute::STATUS_COMPLETED,
            'completed_at' => now(),
        ])->save();

        $user = $this->fullUser();
        $action = $this->consultationActions('obstetrics', $user)['create_admission_request'];

        $this->assertSame(Action::STATE_BLOCKED, $action->state);
        $this->assertFalse($action->isExecutable());
        $this->assertNotEmpty($action->disabledReason);

        // No executable form reaches the browser.
        $html = $this->consultationHtml('obstetrics', $user);
        $this->assertStringNotContainsString('id="consultationMaternityAdmissionRequestModal"', $html);
    }

    public function test_no_explicit_link_renders_invalid_context_not_a_form(): void
    {
        $action = $this->consultationActions('obstetrics', $this->fullUser())['create_admission_request'];

        $this->assertSame(Action::STATE_INVALID_CONTEXT, $action->state);
        $this->assertFalse($action->isExecutable());
    }

    public function test_admission_request_action_is_never_offered_in_gynaecology(): void
    {
        $this->linkConsultationProfile();
        $action = $this->consultationActions('gynecology', $this->fullUser())['create_admission_request'];

        $this->assertSame(Action::STATE_FEATURE_DISABLED, $action->state);
        $this->assertFalse($action->visible);
    }

    /* ── K1 — Gynaecology referral fallback ────────────────────────────── */

    public function test_missing_obstetrics_mapping_shows_a_reason_and_a_usable_standard_link(): void
    {
        $this->linkConsultationProfile();
        $user = $this->fullUser();

        $action = $this->consultationActions('gynecology', $user)['refer_obstetrics'];

        $this->assertSame(Action::STATE_UNAVAILABLE, $action->state);
        $this->assertSame(
            __('maternity_handoffs.fallback.obstetrics_mapping_unavailable'),
            $action->disabledReason
        );
        // A real internal link into the existing standard flow — not a dead end.
        $this->assertNotEmpty($action->fallbackUrl);
        $this->assertStringStartsWith(config('app.url'), $action->fallbackUrl);

        $html = $this->consultationHtml('gynecology', $user);
        $this->assertStringContainsString(__('maternity_handoffs.fallback.continue_standard_consultation'), $html);
        // No referral form is offered while the mapping is missing.
        $this->assertStringNotContainsString('id="consultationReferObstetricsModal"', $html);
    }

    public function test_valid_mapping_renders_a_real_referral_confirmation(): void
    {
        $this->linkConsultationProfile();
        $this->mapObstetricsDepartment();
        $user = $this->fullUser();

        $action = $this->consultationActions('gynecology', $user)['refer_obstetrics'];
        $this->assertSame(Action::STATE_ENABLED, $action->state);

        $html = $this->consultationHtml('gynecology', $user);
        $this->assertStringContainsString('id="consultationReferObstetricsModal"', $html);
        $this->assertStringContainsString(
            'action="'.route('admin.consultations.maternity-context.refer-obstetrics', $this->visit).'"',
            $html
        );
    }

    public function test_repeated_referral_reuses_the_route_and_leaves_gynaecology_unchanged(): void
    {
        $this->linkConsultationProfile();
        $this->mapObstetricsDepartment();
        $user = $this->fullUser();

        $before = VisitConsultationRoute::query()->count();
        $department = $this->route->department_id;

        foreach ([1, 2] as $_) {
            $this->actingAs($user)
                ->post(route('admin.consultations.maternity-context.refer-obstetrics', $this->visit), [])
                ->assertRedirect();
        }

        $this->assertSame($before + 1, VisitConsultationRoute::query()->count());
        $this->assertSame($department, $this->route->fresh()->department_id);
        $this->assertSame(0, AdmissionRequest::query()->count());
        $this->assertSame(0, \App\Models\AntenatalVisit::query()->where('id', '>', $this->ancVisit->id)->count());
    }

    /* ── Emergency ─────────────────────────────────────────────────────── */

    public function test_link_modal_uses_a_lazy_selector_not_an_inline_candidate_list(): void
    {
        $user = $this->fullUser();
        $html = $this->emergencyHtml($user);

        $this->assertStringContainsString('id="emergencyLinkPregnancyModal"', $html);
        $this->assertStringContainsString('maternity-profile-select', $html);
        $this->assertStringContainsString(
            'data-search-url="'.route('admin.emergency.cases.maternity-context.candidates', $this->emergencyCase).'"',
            $html
        );
        // The profile itself must NOT be pre-rendered as an option.
        $this->assertStringNotContainsString('value="'.$this->profile->id.'" selected', $html);
    }

    public function test_start_labor_requires_an_explicit_link_and_shows_open_existing_when_active(): void
    {
        $user = $this->fullUser();

        // Suggested-only context: not enough for a clinical mutation.
        $action = $this->emergencyActions($user)['start_labor'];
        $this->assertSame(Action::STATE_INVALID_CONTEXT, $action->state);

        app(EmergencyMaternityLinkService::class)
            ->link($this->emergencyCase, $this->profile, $this->user);

        // The fixture already has an ACTIVE labor episode → open, never create.
        $action = $this->emergencyActions($user)['start_labor'];
        $this->assertSame(Action::STATE_EXISTING_RECORD, $action->state);
        $this->assertSame(__('maternity_handoffs.emergency.open_existing_labor'), $action->label);
        $this->assertSame($this->labor->id, $action->existingRecord['id']);
    }

    public function test_repeated_start_labor_reuses_the_episode(): void
    {
        $user = $this->fullUser();
        app(EmergencyMaternityLinkService::class)
            ->link($this->emergencyCase, $this->profile, $this->user);

        $before = LaborEpisode::query()->count();

        foreach ([1, 2] as $_) {
            $this->actingAs($user)->post(
                route('admin.emergency.cases.maternity-context.start-labor', $this->emergencyCase),
                ['pregnancy_profile_id' => $this->profile->id]
            )->assertRedirect();
        }

        $this->assertSame($before, LaborEpisode::query()->count());
    }

    public function test_emergency_admission_request_modal_shows_source_and_context_separately(): void
    {
        $user = $this->fullUser();
        app(EmergencyMaternityLinkService::class)
            ->link($this->emergencyCase, $this->profile, $this->user);

        $html = $this->emergencyHtml($user);

        $this->assertStringContainsString('id="emergencyMaternityAdmissionRequestModal"', $html);
        $this->assertStringContainsString(__('maternity_handoffs.modal.operational_source'), $html);
        $this->assertStringContainsString(__('maternity_handoffs.modal.source_emergency'), $html);
        $this->assertStringContainsString(__('maternity_handoffs.modal.clinical_context'), $html);
        $this->assertStringContainsString(__('maternity_handoffs.modal.no_bed_reserved'), $html);
    }

    public function test_suggested_context_must_be_confirmed_before_a_maternity_request(): void
    {
        // Same-visit maternity records exist → SUGGESTED, not linked.
        $action = $this->emergencyActions($this->fullUser())['create_admission_request'];

        $this->assertSame(Action::STATE_INVALID_CONTEXT, $action->state);
        $this->assertSame(
            __('maternity_handoffs.emergency.confirm_context_first'),
            $action->disabledReason
        );
    }

    public function test_relink_and_unlink_require_a_reason_field(): void
    {
        app(EmergencyMaternityLinkService::class)
            ->link($this->emergencyCase, $this->profile, $this->user);

        $actions = $this->emergencyActions($this->fullUser());

        foreach (['relink_profile', 'unlink_profile'] as $key) {
            $this->assertTrue($actions[$key]->requiresReason(), $key.' must require a reason');
            $this->assertContains('reason', $actions[$key]->requiredFields);
        }

        $html = $this->emergencyHtml($this->fullUser());
        $this->assertStringContainsString('id="emergencyUnlinkPregnancyModalReason"', $html);
    }

    public function test_closed_emergency_case_blocks_maternity_mutations(): void
    {
        $this->emergencyCase->forceFill([
            'emergency_status' => EmergencyCase::STATUS_DISPOSED,
            'disposition' => EmergencyCase::DISPOSITION_DISCHARGED,
        ])->save();

        $action = $this->emergencyActions($this->fullUser())['link_profile'];

        $this->assertSame(Action::STATE_BLOCKED, $action->state);
        $this->assertSame(__('maternity_handoffs.states.emergency_case_closed'), $action->disabledReason);
    }

    /* ── Admission ─────────────────────────────────────────────────────── */

    public function test_admission_modal_distinguishes_propagated_from_direct_context(): void
    {
        $user = $this->fullUser();
        app(AdmissionMaternityLinkService::class)
            ->link($this->admission(), $this->profile, $this->user);

        $model = app(AdmissionMaternityWorkspaceService::class)->build($this->admission()->fresh(), $user);
        $relink = $model->action('relink_profile');

        $this->assertSame(Action::STATE_ENABLED, $relink->state);
        $this->assertTrue($relink->requiresReason());
        $this->assertSame(
            __('maternity_handoffs.admission.context_linked_directly'),
            $relink->context['summary'][__('maternity_handoffs.ownership.handoff_context')] ?? null
        );
    }

    public function test_admission_modal_creates_no_maternity_record(): void
    {
        $user = $this->fullUser();
        $profiles = PregnancyProfile::query()->count();
        $labors = LaborEpisode::query()->count();
        $postnatal = PostnatalCase::query()->count();

        $this->actingAs($user)->post(
            route('admin.admissions.maternity-context.link', $this->admission()),
            ['pregnancy_profile_id' => $this->profile->id]
        )->assertRedirect();

        $this->assertSame($profiles, PregnancyProfile::query()->count());
        $this->assertSame($labors, LaborEpisode::query()->count());
        $this->assertSame($postnatal, PostnatalCase::query()->count());
        $this->assertSame(0, InvoiceItem::query()->count());
    }

    /* ── Maternity → Emergency ─────────────────────────────────────────── */

    public function test_escalation_dialog_states_the_flag_created_nothing(): void
    {
        $this->labor->forceFill(['emergency_escalation_required' => true])->save();
        $user = $this->fullUser();

        $html = $this->escalationHtml($this->labor->fresh(), $user);

        $this->assertStringContainsString(__('maternity_handoffs.escalation.flag_created_nothing'), $html);
        $this->assertStringContainsString(__('maternity_handoffs.escalation.no_theatre_case_created'), $html);
        $this->assertStringContainsString('id="maternityEmergencyHandoffModal"', $html);
    }

    public function test_escalation_shows_open_existing_when_a_case_is_already_linked(): void
    {
        $user = $this->fullUser();

        // The fixture's emergency case sits on the same visit → reuse, not create.
        $action = app(MaternityEmergencyHandoffPresenter::class)
            ->build($this->labor, $user)['emergency_handoff'];

        $this->assertSame(Action::STATE_EXISTING_RECORD, $action->state);
        $this->assertSame(__('maternity_handoffs.escalation.open_existing_case'), $action->label);
        $this->assertSame($this->emergencyCase->id, $action->existingRecord['id']);
    }

    public function test_escalation_returns_to_the_source_maternity_record(): void
    {
        $action = app(MaternityEmergencyHandoffPresenter::class)
            ->build($this->labor, $this->fullUser())['emergency_handoff'];

        $fields = $action->returnFields();

        $this->assertSame(MaternityReturnContext::MODULE_MATERNITY, $fields['return_module']);
        $this->assertSame('admin.maternity.labor.show', $fields['return_route']);
    }

    public function test_escalation_hidden_entirely_while_the_flag_is_off(): void
    {
        $this->handoffFlags(consultation: true, admissionContext: true);

        $this->assertSame([], app(MaternityEmergencyHandoffPresenter::class)
            ->build($this->labor, $this->fullUser()));
    }

    /* ── Return-context security ───────────────────────────────────────── */

    public function test_every_modal_form_carries_a_named_route_return_context(): void
    {
        $this->linkConsultationProfile();
        $user = $this->fullUser();

        foreach ([$this->consultationHtml('obstetrics', $user), $this->emergencyHtml($user)] as $html) {
            $this->assertStringContainsString('name="return_route"', $html);
            $this->assertStringContainsString('name="return_module"', $html);
            // A raw URL field must never appear.
            $this->assertStringNotContainsString('name="return_url"', $html);
        }
    }

    public function test_external_return_url_is_ignored_and_falls_back_safely(): void
    {
        $user = $this->fullUser();
        app(EmergencyMaternityLinkService::class)
            ->link($this->emergencyCase, $this->profile, $this->user);

        $this->actingAs($user)->post(
            route('admin.emergency.cases.maternity-context.unlink', $this->emergencyCase),
            [
                'reason' => 'Corrected',
                'return_module' => 'emergency',
                'return_route' => 'https://evil.example.com/steal',
            ]
        )->assertRedirect(route('admin.emergency.cases.show', $this->emergencyCase));
    }

    /* ── Permissions ───────────────────────────────────────────────────── */

    public function test_either_half_of_a_permission_pair_alone_hides_the_form(): void
    {
        app(EmergencyMaternityLinkService::class)
            ->link($this->emergencyCase, $this->profile, $this->user);

        $bridgeOnly = $this->userWithPermissions([
            'emergency.maternity_context.view', 'emergency.maternity_context.start_labor',
        ]);
        $targetOnly = $this->userWithPermissions([
            'emergency.maternity_context.view', 'maternity.labor.start',
        ]);

        foreach ([$bridgeOnly, $targetOnly] as $user) {
            $action = $this->emergencyActions($user)['start_labor'];

            $this->assertSame(Action::STATE_PERMISSION_MISSING, $action->state);
            $this->assertFalse($action->visible);
            $this->assertStringNotContainsString('id="emergencyStartLaborModal"', $this->emergencyHtml($user));
        }
    }

    public function test_both_permissions_make_the_form_executable(): void
    {
        app(EmergencyMaternityLinkService::class)
            ->link($this->emergencyCase, $this->profile, $this->user);
        // Close the fixture episode so this is a create, not a reuse.
        $this->labor->forceFill(['status' => 'closed'])->save();

        $action = $this->emergencyActions($this->fullUser())['start_labor'];

        $this->assertSame(Action::STATE_ENABLED, $action->state);
        $this->assertTrue($action->isExecutable());
    }

    /* ── Helpers ───────────────────────────────────────────────────────── */

    private function linkConsultationProfile(): void
    {
        app(ConsultationMaternityLinkService::class)
            ->link($this->route, $this->profile, $this->user);
    }

    private function mapObstetricsDepartment(): void
    {
        $department = Department::factory()->create([
            'name' => 'Obstetrics Clinic',
            'type' => \App\Enums\DepartmentType::CONSULTATION->value,
            'status' => 'active',
        ]);

        ConsultationSpecialtyProfileMapping::create([
            'consultation_specialty_profile_id' => $this->specialty('obstetrics')->id,
            'department_id' => $department->id,
            'is_active' => true,
            'priority' => 10,
        ]);
    }

    /** @return array<string, Action> */
    private function consultationActions(string $code, $user): array
    {
        return app(ConsultationMaternityHandoffPresenter::class)
            ->build($this->route->fresh(), $this->specialty($code), $user)['actions'];
    }

    /** @return array<string, Action> */
    private function emergencyActions($user): array
    {
        return app(EmergencyMaternityWorkspaceService::class)
            ->build($this->emergencyCase->fresh(), $user)->handoffActions;
    }

    private function consultationHtml(string $code, $user): string
    {
        $this->actingAs($user);

        return view('consultations.partials.maternity.handoff-actions', [
            'handoffs' => app(ConsultationMaternityHandoffPresenter::class)
                ->build($this->route->fresh(), $this->specialty($code), $user),
        ])->render();
    }

    private function emergencyHtml($user): string
    {
        $this->actingAs($user);

        return view('emergency.partials.maternity-context-card', [
            'maternity' => app(EmergencyMaternityWorkspaceService::class)
                ->build($this->emergencyCase->fresh(), $user),
            'case' => $this->emergencyCase,
        ])->render();
    }

    private function escalationHtml($source, $user): string
    {
        $this->actingAs($user);

        return view('maternity.partials.emergency-handoff-panel', [
            'actions' => app(MaternityEmergencyHandoffPresenter::class)->build($source, $user),
        ])->render();
    }

    private function specialty(string $code): ConsultationSpecialtyProfile
    {
        return ConsultationSpecialtyProfile::firstOrCreate(
            ['code' => $code],
            ['name' => ucfirst($code), 'is_active' => true, 'sort_order' => 40]
        );
    }

    private function fullUser()
    {
        return $this->userWithPermissions([
            'consultation.maternity_context.view',
            'consultation.maternity_context.link',
            'consultation.maternity_context.unlink',
            'consultation.maternity_context.create_admission_request',
            'consultation.maternity_context.refer_obstetrics',
            'consultation.maternity_context.open_postnatal',
            'emergency.maternity_context.view',
            'emergency.maternity_context.link',
            'emergency.maternity_context.unlink',
            'emergency.maternity_context.create_profile',
            'emergency.maternity_context.start_labor',
            'emergency.maternity_context.create_admission_request',
            'admission.maternity_context.view',
            'admission.maternity_context.link',
            'admission.maternity_context.unlink',
            'maternity.emergency_handoff.create',
            'maternity.pregnancy.view',
            'maternity.pregnancy.create',
            'maternity.labor.start',
            'maternity.postnatal.view',
            'admission.requests.create',
            'emergency.case.create',
            'consultations.create',
        ], baseline: ['consultations.view', 'ward.view']);
    }
}
