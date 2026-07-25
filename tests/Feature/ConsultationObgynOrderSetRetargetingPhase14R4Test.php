<?php

namespace Tests\Feature;

use App\Console\Commands\ObgynOrderSetAuditCommand;
use App\Enums\ConsultationMaternityLinkRole;
use App\Enums\DepartmentType;
use App\Enums\PregnancyProfileStatus;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
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
use App\Services\Consultation\Specialty\ConsultationSpecialtyEntryService;
use App\Services\Consultation\Maternity\ConsultationMaternityWriteBlockedException;
use Database\Seeders\ConsultationSpecialtyOrderSetMaternityReconciliationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 14R.4 — order-set retargeting (decision R5) and runtime write-path
 * hardening.
 *
 * The critical property: no sanctioned runtime path may write a maternity-owned
 * specialty field while the guard applies — including order sets, which write
 * through the service directly rather than the guarded controller.
 */
class ConsultationObgynOrderSetRetargetingPhase14R4Test extends TestCase
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

        config([
            'audit_streaming.async_writes' => false,
            'consultation.maternity_context.obstetrics_workspace_enabled' => false,
            'consultation.maternity_context.obstetrics_write_guard_enabled' => false,
        ]);

        $this->department = Department::factory()->create([
            'type' => DepartmentType::CONSULTATION->value,
            'status' => 'active',
        ]);
        $this->user = User::factory()->create(['department_id' => $this->department->id]);
        $this->patient = Patient::factory()->create();
        $this->visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'created_by' => $this->user->id,
            'visit_type' => VisitType::OUTPATIENT,
            'status' => VisitStatus::CONSULTING,
            'current_department_id' => $this->department->id,
        ]);
        $this->consultation = VisitConsultationRoute::create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'department_id' => $this->department->id,
            'status' => VisitConsultationRoute::STATUS_ACTIVE,
            'routed_by' => $this->user->id,
            'started_at' => now(),
            'activated_at' => now(),
        ]);
    }

    /* ── Audit ────────────────────────────────────────────────────────── */

    public function test_audit_identifies_the_seeded_maternity_shaped_patch_items(): void
    {
        $set = $this->makeOrderSet();
        $confirmed = $this->makePatchItem($set, 'current_pregnancy', ['pregnancy_confirmed' => true]);
        $counselling = $this->makePatchItem($set, 'birth_plan', ['danger_signs_counseling' => true]);

        $this->assertNotNull(ObgynOrderSetAuditCommand::retargetDefinitionFor($confirmed));
        $this->assertNotNull(ObgynOrderSetAuditCommand::retargetDefinitionFor($counselling));

        $this->artisan('consultation:obgyn-order-set-audit')->assertExitCode(0);
    }

    public function test_audit_makes_no_writes(): void
    {
        $set = $this->makeOrderSet();
        $item = $this->makePatchItem($set, 'current_pregnancy', ['pregnancy_confirmed' => true]);
        $before = $item->fresh()->toArray();

        $this->artisan('consultation:obgyn-order-set-audit')->assertExitCode(0);

        $this->assertSame($before, $item->fresh()->toArray());
    }

    public function test_unguarded_gynaecology_patch_is_not_flagged(): void
    {
        // menstrual_history is never maternity-owned — it must stay untouched.
        $set = $this->makeOrderSet();
        $item = $this->makePatchItem($set, 'menstrual_history', ['bleeding_pattern' => 'Document severity']);

        $this->assertNull(ObgynOrderSetAuditCommand::retargetDefinitionFor($item));
    }

    /* ── Seeder ───────────────────────────────────────────────────────── */

    public function test_seeder_retargets_known_items_and_is_idempotent(): void
    {
        $set = $this->makeOrderSet();
        $confirmed = $this->makePatchItem($set, 'current_pregnancy', ['pregnancy_confirmed' => true]);
        $counselling = $this->makePatchItem($set, 'birth_plan', ['danger_signs_counseling' => true]);

        $this->seed(ConsultationSpecialtyOrderSetMaternityReconciliationSeeder::class);

        $this->assertSame('maternity_context_action', $confirmed->fresh()->apply_mode);
        $this->assertSame('create_or_link_pregnancy_profile', $confirmed->fresh()->payload['maternity_action']);
        $this->assertSame('record_anc_counselling', $counselling->fresh()->payload['maternity_action']);

        // Original definition retained for audit.
        $this->assertSame('patch_specialty_entry', $confirmed->fresh()->payload['retargeted_from']['apply_mode']);

        // Idempotent: a second run changes nothing.
        $snapshot = $confirmed->fresh()->toArray();
        $this->seed(ConsultationSpecialtyOrderSetMaternityReconciliationSeeder::class);
        $this->assertSame($snapshot, $confirmed->fresh()->toArray());
    }

    public function test_seeder_never_overwrites_an_admin_modified_item(): void
    {
        $set = $this->makeOrderSet();
        // Same section, DIFFERENT payload → administrator-authored.
        $custom = $this->makePatchItem($set, 'current_pregnancy', [
            'pregnancy_confirmed' => true,
            'booking_status' => 'booked',
        ]);

        $this->seed(ConsultationSpecialtyOrderSetMaternityReconciliationSeeder::class);

        $this->assertSame('patch_specialty_entry', $custom->fresh()->apply_mode, 'custom item must be left alone');
        $this->assertSame(
            ['pregnancy_confirmed' => true, 'booking_status' => 'booked'],
            $custom->fresh()->payload['merge']
        );
    }

    /* ── Runtime write-path hardening ─────────────────────────────────── */

    public function test_service_boundary_blocks_guarded_fields_for_every_runtime_path(): void
    {
        $this->enableGuard();
        $this->linkProfile();

        $entries = app(ConsultationSpecialtyEntryService::class);

        // This is the exact path order sets use (upsertEntry), bypassing the
        // controller — it must still be blocked.
        $this->expectException(ConsultationMaternityWriteBlockedException::class);

        $entries->upsertEntry(
            $this->consultation,
            $this->obstetricsProfile(),
            'current_pregnancy',
            ['pregnancy_confirmed' => true],
            $this->user,
        );
    }

    public function test_service_boundary_allows_consultation_owned_siblings(): void
    {
        $this->enableGuard();
        $this->linkProfile();

        $entry = app(ConsultationSpecialtyEntryService::class)->upsertEntry(
            $this->consultation,
            $this->obstetricsProfile(),
            'current_pregnancy',
            ['current_complaints' => 'Backache', 'high_risk_notes' => 'None'],
            $this->user,
        );

        $this->assertSame('Backache', $entry->entry['current_complaints']);
    }

    public function test_guard_disabled_preserves_previous_write_behaviour(): void
    {
        // Flags off — the historical behaviour must be untouched.
        $this->linkProfile();

        $entry = app(ConsultationSpecialtyEntryService::class)->upsertEntry(
            $this->consultation,
            $this->obstetricsProfile(),
            'current_pregnancy',
            ['pregnancy_confirmed' => true],
            $this->user,
        );

        $this->assertTrue($entry->entry['pregnancy_confirmed']);
    }

    public function test_non_obgyn_profiles_are_unaffected(): void
    {
        $this->enableGuard();
        $this->linkProfile();

        $other = ConsultationSpecialtyProfile::firstOrCreate(
            ['code' => 'general_medicine'],
            ['name' => 'General Medicine', 'is_active' => true, 'sort_order' => 10]
        );

        $entry = app(ConsultationSpecialtyEntryService::class)->upsertEntry(
            $this->consultation, $other, 'current_pregnancy', ['pregnancy_confirmed' => true], $this->user
        );

        $this->assertTrue($entry->entry['pregnancy_confirmed']);
    }

    public function test_historical_specialty_entries_are_never_deleted(): void
    {
        $existing = ConsultationSpecialtyEntry::create([
            'consultation_id' => $this->consultation->id,
            'section_key' => 'birth_plan',
            'entry' => ['danger_signs_counseling' => true],
        ]);

        $this->enableGuard();
        $this->linkProfile();

        try {
            app(ConsultationSpecialtyEntryService::class)->upsertEntry(
                $this->consultation, $this->obstetricsProfile(), 'birth_plan',
                ['danger_signs_counseling' => false], $this->user
            );
        } catch (ConsultationMaternityWriteBlockedException) {
            // expected
        }

        $this->assertDatabaseHas('consultation_specialty_entries', ['id' => $existing->id]);
        $this->assertTrue($existing->fresh()->entry['danger_signs_counseling']);
    }

    /* ── Helpers ──────────────────────────────────────────────────────── */

    private function enableGuard(): void
    {
        config([
            'consultation.maternity_context.obstetrics_workspace_enabled' => true,
            'consultation.maternity_context.obstetrics_write_guard_enabled' => true,
        ]);
    }

    private function obstetricsProfile(): ConsultationSpecialtyProfile
    {
        return ConsultationSpecialtyProfile::firstOrCreate(
            ['code' => 'obstetrics'],
            ['name' => 'Obstetrics / Antenatal', 'is_active' => true, 'sort_order' => 40]
        );
    }

    private function linkProfile(): PregnancyProfile
    {
        $profile = PregnancyProfile::create([
            'patient_id' => $this->patient->id,
            'department_id' => $this->department->id,
            'created_by' => $this->user->id,
            'profile_status' => PregnancyProfileStatus::ACTIVE->value,
        ]);

        app(ConsultationMaternityLinkService::class)->link(
            $this->consultation, $profile, $this->user, ConsultationMaternityLinkRole::PRIMARY
        );

        return $profile;
    }

    private function makeOrderSet(): ConsultationSpecialtyOrderSet
    {
        return ConsultationSpecialtyOrderSet::create([
            'consultation_specialty_profile_id' => $this->obstetricsProfile()->id,
            'code' => 'obstetrics_test_set_'.uniqid(),
            'name' => 'Test Set',
            'is_active' => true,
        ]);
    }

    private function makePatchItem(
        ConsultationSpecialtyOrderSet $set,
        string $section,
        array $merge,
    ): ConsultationSpecialtyOrderSetItem {
        return ConsultationSpecialtyOrderSetItem::create([
            'consultation_specialty_order_set_id' => $set->id,
            'item_type' => 'specialty_entry_patch',
            'label' => 'Patch '.$section,
            'apply_mode' => 'patch_specialty_entry',
            'target_section' => $section,
            'payload' => ['section_key' => $section, 'merge' => $merge],
            'is_active' => true,
        ]);
    }
}
