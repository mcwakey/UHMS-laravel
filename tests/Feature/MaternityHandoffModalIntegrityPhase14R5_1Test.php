<?php

namespace Tests\Feature;

use App\Data\Maternity\MaternityHandoffActionViewModel as Action;
use App\Models\ConsultationSpecialtyProfile;
use App\Services\Admissions\Maternity\AdmissionMaternityLinkService;
use App\Services\Admissions\Maternity\AdmissionMaternityWorkspaceService;
use App\Services\Consultation\Maternity\ConsultationMaternityHandoffPresenter;
use App\Services\Consultation\Maternity\ConsultationMaternityLinkService;
use App\Services\Emergency\Maternity\EmergencyMaternityLinkService;
use App\Services\Emergency\Maternity\EmergencyMaternityWorkspaceService;
use App\Services\Maternity\Handoffs\MaternityEmergencyHandoffPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsMaternityHandoffFixtures;
use Tests\TestCase;

/**
 * Phase 14R.5.1 — modal-target integrity.
 *
 * A visible trigger must NEVER point at a missing dialog. This suite renders
 * the real O&G/Maternity handoff partials and checks, for every
 * `data-bs-toggle="modal"` trigger, that a matching `id="…"` element is present
 * in the same output.
 *
 * Deliberately scoped to the maternity handoff views — it does not scan global
 * navigation or third-party modals.
 *
 * It fails when a new handoff button is added without a dialog body, which is
 * exactly the regression that produced K2.
 */
class MaternityHandoffModalIntegrityPhase14R5_1Test extends TestCase
{
    use BuildsMaternityHandoffFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['audit_streaming.async_writes' => false]);
        $this->buildMaternityFixture();

        // Every real request gets an error bag from ShareErrorsFromSession;
        // rendering a partial standalone must mirror that so @error compiles.
        \Illuminate\Support\Facades\View::share('errors', new \Illuminate\Support\ViewErrorBag);
    }

    /* ── The integrity rule itself ─────────────────────────────────────── */

    public function test_every_emergency_trigger_has_a_rendered_modal(): void
    {
        $this->handoffFlags(emergencyContext: true, emergencyHandoffs: true);
        $user = $this->fullyPermittedUser();

        app(EmergencyMaternityLinkService::class)
            ->link($this->emergencyCase, $this->profile, $this->user);

        $html = $this->renderEmergencyCard($user);

        $this->assertNoDanglingTriggers($html);
        $this->assertNotSame('', trim($html), 'The Emergency card should render when enabled.');
    }

    public function test_every_admission_trigger_has_a_rendered_modal(): void
    {
        $this->handoffFlags(admissionContext: true);
        $user = $this->fullyPermittedUser();

        $html = $this->renderAdmissionCard($user);
        $this->assertNoDanglingTriggers($html);

        // And again once a context is linked, which swaps link → relink/unlink.
        app(AdmissionMaternityLinkService::class)
            ->link($this->admission(), $this->profile, $this->user);

        $this->assertNoDanglingTriggers($this->renderAdmissionCard($user));
    }

    public function test_every_consultation_trigger_has_a_rendered_modal(): void
    {
        $this->handoffFlags(consultation: true);
        $this->obgynFlags(obstetrics: true, gynaecology: true);
        $user = $this->fullyPermittedUser();
        $route = $this->consultationRoute();

        app(ConsultationMaternityLinkService::class)->link($route, $this->profile, $this->user);

        foreach (['obstetrics', 'gynecology'] as $code) {
            $html = $this->renderConsultationPanel($route, $code, $user);
            $this->assertNoDanglingTriggers($html);
        }
    }

    public function test_every_maternity_escalation_trigger_has_a_rendered_modal(): void
    {
        $this->handoffFlags(emergencyContext: true, emergencyHandoffs: true);
        $user = $this->fullyPermittedUser();

        $html = $this->renderEscalationPanel($this->labor, $user);

        $this->assertNoDanglingTriggers($html);
        $this->assertStringContainsString('maternityEmergencyHandoffModal', $html);
    }

    /* ── Flag-off and permission behaviour ─────────────────────────────── */

    public function test_feature_disabled_renders_no_active_trigger_and_no_modal(): void
    {
        $this->handoffFlags();
        $this->obgynFlags();
        $user = $this->fullyPermittedUser();

        foreach ([
            $this->renderEmergencyCard($user),
            $this->renderAdmissionCard($user),
            $this->renderEscalationPanel($this->labor, $user),
        ] as $html) {
            $this->assertStringNotContainsString('data-bs-toggle="modal"', $html);
            $this->assertStringNotContainsString('class="modal fade"', $html);
        }
    }

    public function test_unauthorized_user_receives_no_executable_modal_form(): void
    {
        $this->handoffFlags(emergencyContext: true, emergencyHandoffs: true);

        // Bridge permissions only — every target permission is missing.
        $user = $this->userWithPermissions([
            'emergency.maternity_context.view',
            'emergency.maternity_context.link',
            'emergency.maternity_context.start_labor',
            'emergency.maternity_context.create_admission_request',
        ]);

        $html = $this->renderEmergencyCard($user);

        $this->assertStringNotContainsString('<form', $html);
        $this->assertNoDanglingTriggers($html);
    }

    public function test_read_only_context_mode_renders_no_mutation_form(): void
    {
        // Context on, handoffs OFF — the pilot's read-only mode.
        $this->handoffFlags(emergencyContext: true, emergencyHandoffs: false);
        $user = $this->fullyPermittedUser();

        $html = $this->renderEmergencyCard($user);

        $this->assertStringContainsString(__('maternity_handoffs.emergency.title'), $html);
        $this->assertStringNotContainsString('<form', $html);
        $this->assertStringNotContainsString('data-bs-toggle="modal"', $html);
    }

    /* ── The typed contract itself ─────────────────────────────────────── */

    public function test_no_action_is_executable_without_a_modal_and_a_route(): void
    {
        $this->handoffFlags(consultation: true, emergencyContext: true, emergencyHandoffs: true, admissionContext: true);
        $this->obgynFlags(obstetrics: true, gynaecology: true);
        $user = $this->fullyPermittedUser();
        $route = $this->consultationRoute();

        $sets = [
            app(ConsultationMaternityHandoffPresenter::class)
                ->build($route, $this->specialty('obstetrics'), $user)['actions'] ?? [],
            app(EmergencyMaternityWorkspaceService::class)
                ->build($this->emergencyCase, $user)->handoffActions,
            app(AdmissionMaternityWorkspaceService::class)
                ->build($this->admission(), $user)->handoffActions,
            app(MaternityEmergencyHandoffPresenter::class)->build($this->labor, $user),
        ];

        $checked = 0;

        foreach ($sets as $actions) {
            foreach ($actions as $action) {
                $checked++;

                if ($action->isExecutable()) {
                    $this->assertNotNull($action->modalId, $action->actionKey.' is executable without a modal');
                    $this->assertNotNull($action->url(), $action->actionKey.' is executable without a resolvable route');
                } else {
                    // Non-executable actions must explain themselves or be hidden.
                    $this->assertTrue(
                        ! $action->visible || filled($action->disabledReason),
                        $action->actionKey.' is visible, non-executable and gives no reason'
                    );
                }
            }
        }

        $this->assertGreaterThan(0, $checked, 'No actions were produced to check.');
    }

    public function test_every_action_state_is_from_the_closed_set(): void
    {
        $this->handoffFlags(emergencyContext: true, emergencyHandoffs: true);
        $user = $this->fullyPermittedUser();

        $allowed = [
            Action::STATE_ENABLED, Action::STATE_EXISTING_RECORD, Action::STATE_BLOCKED,
            Action::STATE_UNAVAILABLE, Action::STATE_AMBIGUOUS, Action::STATE_PERMISSION_MISSING,
            Action::STATE_FEATURE_DISABLED, Action::STATE_INVALID_CONTEXT,
        ];

        $actions = app(EmergencyMaternityWorkspaceService::class)
            ->build($this->emergencyCase, $user)->handoffActions;

        $this->assertNotEmpty($actions);

        foreach ($actions as $action) {
            $this->assertContains($action->state, $allowed, $action->actionKey.' has an unknown state');
        }
    }

    /* ── Helpers ───────────────────────────────────────────────────────── */

    /**
     * The core rule: every modal trigger must have a matching element id in the
     * same rendered output.
     */
    private function assertNoDanglingTriggers(string $html): void
    {
        preg_match_all(
            '/data-bs-toggle="modal"[^>]*data-bs-target="#([A-Za-z0-9_-]+)"/s',
            $html,
            $a
        );
        preg_match_all(
            '/data-bs-target="#([A-Za-z0-9_-]+)"[^>]*data-bs-toggle="modal"/s',
            $html,
            $b
        );

        $targets = array_unique(array_merge($a[1], $b[1]));

        foreach ($targets as $target) {
            $this->assertMatchesRegularExpression(
                '/id="'.preg_quote($target, '/').'"/',
                $html,
                "Trigger points at #{$target} but no element with that id is rendered."
            );
        }
    }

    private function renderEmergencyCard($user): string
    {
        $this->actingAs($user);

        return view('emergency.partials.maternity-context-card', [
            'maternity' => app(EmergencyMaternityWorkspaceService::class)
                ->build($this->emergencyCase->fresh(), $user),
            'case' => $this->emergencyCase,
        ])->render();
    }

    private function renderAdmissionCard($user): string
    {
        $this->actingAs($user);
        $admission = $this->admission();

        return view('admissions.partials.maternity-context-card', [
            'maternity' => app(AdmissionMaternityWorkspaceService::class)
                ->build($admission->fresh(), $user),
            'admission' => $admission,
        ])->render();
    }

    private function renderConsultationPanel($route, string $code, $user): string
    {
        $this->actingAs($user);

        return view('consultations.partials.maternity.handoff-actions', [
            'handoffs' => app(ConsultationMaternityHandoffPresenter::class)
                ->build($route, $this->specialty($code), $user),
        ])->render();
    }

    private function renderEscalationPanel($source, $user): string
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

    /** Holds every bridge permission AND every target-domain permission. */
    private function fullyPermittedUser()
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
        ]);
    }
}
