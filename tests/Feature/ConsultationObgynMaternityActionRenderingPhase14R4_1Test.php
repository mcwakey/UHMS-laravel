<?php

namespace Tests\Feature;

use App\Enums\ConsultationMaternityLinkRole;
use App\Enums\DepartmentType;
use App\Enums\PregnancyProfileStatus;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\AntenatalVisit;
use App\Models\ConsultationSpecialtyEntry;
use App\Models\ConsultationSpecialtyOrderSet;
use App\Models\ConsultationSpecialtyOrderSetItem;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\Department;
use App\Models\Patient;
use App\Models\PregnancyProfile;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Services\Consultation\Maternity\ConsultationMaternityLinkService;
use App\Services\Consultation\Maternity\ConsultationMaternityOrderSetActionPresenter as Presenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 14R.4.1 (R2) — clinician-facing rendering for `maternity_context_action`.
 *
 * The presenter is presentation-only: it must never write, never execute, and
 * must fail closed on unknown action keys.
 */
class ConsultationObgynMaternityActionRenderingPhase14R4_1Test extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Department $department;

    private Patient $patient;

    private Visit $visit;

    private VisitConsultationRoute $consultation;

    protected function setUp(): void
    {
        parent::setUp();

        config(['audit_streaming.async_writes' => false]);
        $this->flags(obstetrics: true, gynaecology: true);

        $this->department = Department::factory()->create([
            'type' => DepartmentType::CONSULTATION->value, 'status' => 'active',
        ]);
        $this->user = User::factory()->create(['department_id' => $this->department->id]);
        $this->patient = Patient::factory()->create();
        $this->visit = Visit::factory()->create([
            'patient_id' => $this->patient->id, 'created_by' => $this->user->id,
            'visit_type' => VisitType::OUTPATIENT, 'status' => VisitStatus::CONSULTING,
            'current_department_id' => $this->department->id,
        ]);
        $this->consultation = VisitConsultationRoute::create([
            'visit_id' => $this->visit->id, 'patient_id' => $this->patient->id,
            'department_id' => $this->department->id,
            'status' => VisitConsultationRoute::STATUS_ACTIVE,
            'routed_by' => $this->user->id, 'started_at' => now(), 'activated_at' => now(),
        ]);
    }

    /* ── create_or_link_pregnancy_profile ─────────────────────────────── */

    public function test_create_or_link_renders_action_required_when_unlinked(): void
    {
        $item = $this->actionItem(Presenter::ACTION_CREATE_OR_LINK);

        $result = $this->present($item, $this->obstetrics(), $this->linker(), false);

        $this->assertSame(Presenter::STATE_ACTION_REQUIRED, $result['state']);
        $this->assertTrue($result['executable']);
    }

    public function test_create_or_link_renders_satisfied_when_already_linked(): void
    {
        $item = $this->actionItem(Presenter::ACTION_CREATE_OR_LINK);

        $result = $this->present($item, $this->obstetrics(), $this->linker(), true);

        $this->assertSame(Presenter::STATE_SATISFIED, $result['state']);
        $this->assertFalse($result['executable'], 'satisfied action must not be re-executable');
    }

    public function test_gynaecology_create_or_link_uses_gynaecology_wording(): void
    {
        $item = $this->actionItem(Presenter::ACTION_CREATE_OR_LINK);

        $result = $this->present($item, $this->gynaecology(), $this->linker(), false);

        $this->assertSame(Presenter::STATE_ACTION_REQUIRED, $result['state']);
        $this->assertSame(__('consultation_maternity.gynaecology.start_or_link'), $result['label']);
    }

    public function test_presenting_creates_no_records_at_all(): void
    {
        $item = $this->actionItem(Presenter::ACTION_CREATE_OR_LINK);
        $itemSnapshot = $item->fresh()->toArray();

        $this->present($item, $this->obstetrics(), $this->linker(), false);
        $this->present($item, $this->obstetrics(), $this->linker(), true);

        $this->assertSame(0, PregnancyProfile::count());
        $this->assertSame(0, AntenatalVisit::count());
        $this->assertSame(0, ConsultationSpecialtyEntry::count());
        $this->assertDatabaseCount('consultation_maternity_links', 0);
        // The item definition itself is untouched by rendering.
        $this->assertSame($itemSnapshot, $item->fresh()->toArray());
    }

    /* ── record_anc_counselling ───────────────────────────────────────── */

    public function test_record_anc_is_never_offered_in_gynaecology(): void
    {
        $item = $this->actionItem(Presenter::ACTION_RECORD_ANC_COUNSELLING);

        // Even a user holding every ANC permission, with a linked profile.
        $result = $this->present($item, $this->gynaecology(), $this->ancUser(), true);

        $this->assertSame(Presenter::STATE_UNAVAILABLE, $result['state']);
        $this->assertFalse($result['executable']);
    }

    public function test_record_anc_requires_an_explicit_profile(): void
    {
        $item = $this->actionItem(Presenter::ACTION_RECORD_ANC_COUNSELLING);

        $result = $this->present($item, $this->obstetrics(), $this->ancUser(), false);

        // Falls back to "link a profile first".
        $this->assertSame(Presenter::STATE_ACTION_REQUIRED, $result['state']);
        $this->assertSame(Presenter::ACTION_CREATE_OR_LINK, $result['action_key']);
    }

    public function test_record_anc_requires_both_permissions(): void
    {
        $item = $this->actionItem(Presenter::ACTION_RECORD_ANC_COUNSELLING);

        // Bridge permission only — missing maternity.anc.record.
        $result = $this->present(
            $item, $this->obstetrics(),
            $this->userWith(['consultation.maternity_context.record_anc']), true
        );

        $this->assertSame(Presenter::STATE_BLOCKED, $result['state']);
        $this->assertFalse($result['executable']);
    }

    public function test_completed_consultation_renders_non_executable_guidance(): void
    {
        $item = $this->actionItem(Presenter::ACTION_RECORD_ANC_COUNSELLING);

        $result = $this->present(
            $item, $this->obstetrics(), $this->ancUser(), true, consultationEditable: false
        );

        $this->assertSame(Presenter::STATE_BLOCKED, $result['state']);
        $this->assertFalse($result['executable']);
        $this->assertSame(
            __('consultation_maternity.order_sets.start_new_active_consultation'),
            $result['message']
        );
    }

    public function test_record_anc_renders_executable_when_fully_eligible(): void
    {
        $item = $this->actionItem(Presenter::ACTION_RECORD_ANC_COUNSELLING);

        $result = $this->present($item, $this->obstetrics(), $this->ancUser(), true);

        $this->assertSame(Presenter::STATE_ACTION_REQUIRED, $result['state']);
        $this->assertTrue($result['executable']);
    }

    /* ── Fail-closed + legacy + flags ─────────────────────────────────── */

    public function test_unknown_action_key_fails_closed(): void
    {
        $item = $this->actionItem('delete_everything');

        $result = $this->present($item, $this->obstetrics(), $this->linker(), false);

        $this->assertSame(Presenter::STATE_UNSUPPORTED, $result['state']);
        $this->assertFalse($result['executable']);
    }

    public function test_legacy_patch_item_renders_as_legacy_not_as_new_action(): void
    {
        $item = ConsultationSpecialtyOrderSetItem::create([
            'consultation_specialty_order_set_id' => $this->orderSet()->id,
            'item_type' => 'specialty_entry_patch',
            'label' => 'Legacy patch',
            'apply_mode' => 'patch_specialty_entry',
            'target_section' => 'current_pregnancy',
            'payload' => ['section_key' => 'current_pregnancy', 'merge' => ['pregnancy_confirmed' => true]],
            'is_active' => true,
        ]);

        $result = $this->present($item, $this->obstetrics(), $this->linker(), false);

        $this->assertSame(Presenter::STATE_LEGACY_PATCH, $result['state']);
        $this->assertFalse($result['executable']);
    }

    public function test_disabled_integration_does_not_revert_to_patching(): void
    {
        $this->flags(obstetrics: false, gynaecology: false);
        $item = $this->actionItem(Presenter::ACTION_CREATE_OR_LINK);

        $result = $this->present($item, $this->obstetrics(), $this->linker(), false);

        $this->assertSame(Presenter::STATE_UNAVAILABLE, $result['state']);
        $this->assertFalse($result['executable']);
        // Crucially the item is still a maternity action, not a patch.
        $this->assertSame('maternity_context_action', $item->fresh()->apply_mode);
        $this->assertSame(0, ConsultationSpecialtyEntry::count());
    }

    /* ── Helpers ──────────────────────────────────────────────────────── */

    private function flags(bool $obstetrics, bool $gynaecology): void
    {
        config([
            'consultation.maternity_context.obstetrics_workspace_enabled' => $obstetrics,
            'consultation.maternity_context.gynaecology_context_enabled' => $gynaecology,
        ]);
    }

    private function present(
        ConsultationSpecialtyOrderSetItem $item,
        ConsultationSpecialtyProfile $profile,
        User $user,
        bool $linked,
        bool $consultationEditable = true,
    ): array {
        return app(Presenter::class)->present(
            $item, $this->consultation, $profile, $user, $linked, $consultationEditable
        );
    }

    private function obstetrics(): ConsultationSpecialtyProfile
    {
        return ConsultationSpecialtyProfile::firstOrCreate(
            ['code' => 'obstetrics'],
            ['name' => 'Obstetrics', 'is_active' => true, 'sort_order' => 40]
        );
    }

    private function gynaecology(): ConsultationSpecialtyProfile
    {
        return ConsultationSpecialtyProfile::firstOrCreate(
            ['code' => 'gynecology'],
            ['name' => 'Gynecology', 'is_active' => true, 'sort_order' => 50]
        );
    }

    private function orderSet(): ConsultationSpecialtyOrderSet
    {
        return ConsultationSpecialtyOrderSet::firstOrCreate(
            ['code' => 'test_action_set'],
            [
                'consultation_specialty_profile_id' => $this->obstetrics()->id,
                'name' => 'Action Set',
                'is_active' => true,
            ]
        );
    }

    private function actionItem(string $actionKey): ConsultationSpecialtyOrderSetItem
    {
        return ConsultationSpecialtyOrderSetItem::create([
            'consultation_specialty_order_set_id' => $this->orderSet()->id,
            'item_type' => 'specialty_entry_patch',
            'label' => 'Maternity action',
            'apply_mode' => 'maternity_context_action',
            'payload' => ['maternity_action' => $actionKey],
            'is_active' => true,
        ]);
    }

    private function linker(): User
    {
        return $this->userWith(['consultation.maternity_context.link']);
    }

    private function ancUser(): User
    {
        return $this->userWith([
            'consultation.maternity_context.link',
            'consultation.maternity_context.record_anc',
            'maternity.anc.record',
        ]);
    }

    private function userWith(array $permissions): User
    {
        $user = User::factory()->create(['department_id' => $this->department->id]);
        $role = \Spatie\Permission\Models\Role::findOrCreate('ActionRender '.uniqid(), 'web');

        foreach (array_merge(['consultations.view'], $permissions) as $permission) {
            \Spatie\Permission\Models\Permission::findOrCreate($permission, 'web');
            $role->givePermissionTo($permission);
        }

        $user->assignRole($role);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }
}
