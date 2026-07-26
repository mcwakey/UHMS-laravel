<?php

namespace Tests\Feature;

use App\Enums\PregnancyProfileStatus;
use App\Models\PregnancyProfile;
use App\Services\Admissions\Maternity\AdmissionMaternityWorkspaceService;
use App\Services\Emergency\Maternity\EmergencyMaternityWorkspaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\BuildsMaternityHandoffFixtures;
use Tests\TestCase;

/**
 * Phase 14R.5.1 — lazy, patient-scoped candidate selectors.
 *
 * The selectors exist so candidate Pregnancy Profiles are fetched only when a
 * clinician opens one, and so the patient scope cannot be widened from the
 * browser.
 */
class MaternityHandoffSelectorsPhase14R5_1Test extends TestCase
{
    use BuildsMaternityHandoffFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['audit_streaming.async_writes' => false]);
        $this->handoffFlags(consultation: true, emergencyContext: true, admissionContext: true, emergencyHandoffs: true);
        $this->obgynFlags(obstetrics: true, gynaecology: true);
        $this->buildMaternityFixture();
    }

    /* ── Scope safety ──────────────────────────────────────────────────── */

    public function test_candidates_are_scoped_to_the_source_records_patient(): void
    {
        // Another patient with their own profile — must never be returned.
        $foreignProfile = PregnancyProfile::create([
            'patient_id' => $this->otherPatient()->id,
            'created_by' => $this->user->id,
            'profile_status' => PregnancyProfileStatus::ACTIVE->value,
        ]);

        $response = $this->actingAs($this->selectorUser())
            ->getJson(route('admin.emergency.cases.maternity-context.candidates', $this->emergencyCase));

        $response->assertOk();
        $ids = collect($response->json())->pluck('id')->map(fn ($id) => (int) $id);

        $this->assertTrue($ids->contains($this->profile->id));
        $this->assertFalse(
            $ids->contains($foreignProfile->id),
            "Another patient's Pregnancy Profile must never appear in the selector."
        );
    }

    public function test_client_input_cannot_widen_the_patient_scope(): void
    {
        $foreign = PregnancyProfile::create([
            'patient_id' => $this->otherPatient()->id,
            'created_by' => $this->user->id,
            'profile_status' => PregnancyProfileStatus::ACTIVE->value,
        ]);

        // Try every plausible scope-widening parameter.
        $response = $this->actingAs($this->selectorUser())->getJson(
            route('admin.emergency.cases.maternity-context.candidates', $this->emergencyCase)
            .'?patient_id='.$this->otherPatient()->id
            .'&pregnancy_profile_id='.$foreign->id
            .'&all=1&q='
        );

        $response->assertOk();
        $ids = collect($response->json())->pluck('id')->map(fn ($id) => (int) $id);

        $this->assertFalse($ids->contains($foreign->id));
    }

    public function test_admission_and_consultation_selectors_are_scoped_too(): void
    {
        $foreign = PregnancyProfile::create([
            'patient_id' => $this->otherPatient()->id,
            'created_by' => $this->user->id,
            'profile_status' => PregnancyProfileStatus::ACTIVE->value,
        ]);

        $user = $this->selectorUser();

        foreach ([
            route('admin.admissions.maternity-context.candidates', $this->admission()),
            route('admin.consultations.maternity-context.candidates', $this->visit),
        ] as $url) {
            $ids = collect($this->actingAs($user)->getJson($url)->assertOk()->json())
                ->pluck('id')->map(fn ($id) => (int) $id);

            $this->assertTrue($ids->contains($this->profile->id), $url);
            $this->assertFalse($ids->contains($foreign->id), $url);
        }
    }

    /* ── Permissions and flags ─────────────────────────────────────────── */

    public function test_selector_requires_both_bridge_and_target_permission(): void
    {
        $bridgeOnly = $this->userWithPermissions(['emergency.maternity_context.link']);
        $targetOnly = $this->userWithPermissions(['maternity.pregnancy.view']);

        foreach ([$bridgeOnly, $targetOnly] as $user) {
            $this->actingAs($user)
                ->getJson(route('admin.emergency.cases.maternity-context.candidates', $this->emergencyCase))
                ->assertForbidden();
        }
    }

    public function test_selector_is_closed_while_the_feature_flag_is_off(): void
    {
        $this->handoffFlags();

        $this->actingAs($this->selectorUser())
            ->getJson(route('admin.emergency.cases.maternity-context.candidates', $this->emergencyCase))
            ->assertForbidden();
    }

    /* ── Laziness ──────────────────────────────────────────────────────── */

    public function test_candidates_are_not_loaded_when_the_page_renders(): void
    {
        $user = $this->selectorUser();
        $case = $this->emergencyCase;
        $service = app(EmergencyMaternityWorkspaceService::class);

        DB::enableQueryLog();
        DB::flushQueryLog();
        $model = $service->build($case, $user);
        $queries = collect(DB::getQueryLog())->pluck('query');
        DB::disableQueryLog();

        // The card renders a select with a search URL, not a candidate list.
        $this->assertNotEmpty($model->handoffActions);
        $this->assertFalse(
            $queries->contains(fn ($q) => str_contains($q, 'pregnancy_profiles')
                && str_contains($q, 'limit 20')),
            'Candidate profiles must not be queried while the page renders.'
        );
    }

    public function test_admission_page_render_does_not_load_candidates(): void
    {
        $user = $this->selectorUser();
        $admission = $this->admission();
        $service = app(AdmissionMaternityWorkspaceService::class);

        DB::enableQueryLog();
        DB::flushQueryLog();
        $service->build($admission, $user);
        $queries = collect(DB::getQueryLog())->pluck('query');
        DB::disableQueryLog();

        $this->assertFalse(
            $queries->contains(fn ($q) => str_contains($q, 'limit 20')),
            'Candidate profiles must not be queried while the Admission page renders.'
        );
    }

    /* ── Bounded and read-only ─────────────────────────────────────────── */

    public function test_the_selector_is_bounded_and_creates_nothing(): void
    {
        for ($i = 0; $i < 25; $i++) {
            PregnancyProfile::create([
                'patient_id' => $this->patient->id,
                'created_by' => $this->user->id,
                'profile_status' => PregnancyProfileStatus::CLOSED->value,
            ]);
        }

        $before = PregnancyProfile::query()->count();

        $rows = $this->actingAs($this->selectorUser())
            ->getJson(route('admin.emergency.cases.maternity-context.candidates', $this->emergencyCase))
            ->assertOk()
            ->json();

        $this->assertLessThanOrEqual(20, count($rows), 'The candidate list must stay bounded.');
        $this->assertSame($before, PregnancyProfile::query()->count(), 'A selector must never write.');
        $this->assertSame(0, \App\Models\EmergencyMaternityLink::query()->count());
    }

    public function test_selector_rows_carry_identifiers_not_clinical_narrative(): void
    {
        $rows = $this->actingAs($this->selectorUser())
            ->getJson(route('admin.emergency.cases.maternity-context.candidates', $this->emergencyCase))
            ->assertOk()
            ->json();

        $this->assertNotEmpty($rows);

        foreach ($rows as $row) {
            $this->assertSame(['id', 'text', 'status'], array_keys($row));
        }
    }

    /**
     * Holds each bridge permission plus maternity.pregnancy.view.
     *
     * `consultations.create` and `ward.view` are the ENCLOSING route groups'
     * own requirements, not something this phase added — the consultation
     * maternity-context group has required them since 14R.3.
     */
    private function selectorUser()
    {
        return $this->userWithPermissions([
            'emergency.maternity_context.link',
            'admission.maternity_context.link',
            'consultation.maternity_context.link',
            'maternity.pregnancy.view',
        ], baseline: ['consultations.view', 'consultations.create', 'ward.view']);
    }
}
