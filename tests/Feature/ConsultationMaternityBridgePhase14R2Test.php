<?php

namespace Tests\Feature;

use App\Data\Consultation\Maternity\ConsultationMaternityContext;
use App\Enums\AdmissionStatus;
use App\Enums\BedStatus;
use App\Enums\ConsultationMaternityContextType;
use App\Enums\ConsultationMaternityLinkRole;
use App\Enums\DepartmentType;
use App\Enums\PregnancyProfileStatus;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\ActivityLog;
use App\Models\Admission;
use App\Models\AntenatalVisit;
use App\Models\Bed;
use App\Models\ConsultationMaternityLink;
use App\Models\ConsultationSpecialtyEntry;
use App\Models\DeliveryRecord;
use App\Models\Department;
use App\Models\LaborEpisode;
use App\Models\NewbornRecord;
use App\Models\Patient;
use App\Models\PostnatalCase;
use App\Models\PregnancyProfile;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Models\Ward;
use App\Services\Consultation\Maternity\ConsultationMaternityContextResolver;
use App\Services\Consultation\Maternity\ConsultationMaternityLinkException;
use App\Services\Consultation\Maternity\ConsultationMaternityLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 14R.2 — Consultation ↔ Maternity bridge.
 *
 * Covers link/relink/unlink semantics, target + patient validation, the
 * active-slot uniqueness strategy, and resolver precedence/ambiguity rules.
 */
class ConsultationMaternityBridgePhase14R2Test extends TestCase
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

        $this->department = Department::factory()->create([
            'type' => DepartmentType::CONSULTATION->value,
            'status' => 'active',
        ]);

        $this->user = User::factory()->create(['department_id' => $this->department->id]);
        $this->patient = Patient::factory()->create();
        $this->visit = $this->makeVisit($this->patient);
        $this->consultation = $this->makeConsultation($this->visit, $this->patient);
    }

    /* ── Link ─────────────────────────────────────────────────────────── */

    public function test_bridge_can_link_a_pregnancy_profile_to_a_consultation(): void
    {
        $profile = $this->makeProfile();

        $link = $this->linkService()->link($this->consultation, $profile, $this->user);

        $this->assertTrue($link->isActive());
        $this->assertSame(ConsultationMaternityContextType::PREGNANCY_PROFILE, $link->context_type);
        $this->assertSame($profile->id, $link->pregnancy_profile_id);
        $this->assertSame(ConsultationMaternityLinkRole::PRIMARY, $link->link_role);
        $this->assertSame(ConsultationMaternityLink::ACTIVE_SLOT, $link->active_slot);
    }

    public function test_linking_an_anc_visit_derives_the_pregnancy_profile(): void
    {
        $profile = $this->makeProfile();
        $anc = $this->makeAncVisit($profile);

        $link = $this->linkService()->link($this->consultation, $anc, $this->user);

        $this->assertSame(ConsultationMaternityContextType::ANC_VISIT, $link->context_type);
        $this->assertSame($anc->id, $link->antenatal_visit_id);
        $this->assertSame($profile->id, $link->pregnancy_profile_id, 'root profile must be derived from the target');
    }

    public function test_bridge_can_link_labor_delivery_newborn_and_postnatal_targets(): void
    {
        $profile = $this->makeProfile();
        $labor = $this->makeLaborEpisode($profile);
        $delivery = $this->makeDeliveryRecord($profile, $labor);
        $newborn = $this->makeNewbornRecord($profile, $labor, $delivery);
        $postnatal = $this->makePostnatalCase($profile, $labor, $delivery);

        $service = $this->linkService();

        $this->assertSame($labor->id, $service->link($this->consultation, $labor, $this->user)->labor_episode_id);
        $this->assertSame($delivery->id, $service->link($this->consultation, $delivery, $this->user)->delivery_record_id);
        $this->assertSame($newborn->id, $service->link($this->consultation, $newborn, $this->user)->newborn_record_id);
        $this->assertSame($postnatal->id, $service->link($this->consultation, $postnatal, $this->user)->postnatal_case_id);

        // One active link per context type → four distinct active links.
        $this->assertSame(4, $service->getActiveLinks($this->consultation)->count());
    }

    public function test_unsupported_model_target_is_rejected(): void
    {
        $this->expectException(ConsultationMaternityLinkException::class);
        $this->expectExceptionMessageMatches('/Unsupported/i');

        $this->linkService()->link($this->consultation, $this->patient, $this->user);
    }

    public function test_mismatched_patient_target_is_rejected(): void
    {
        $otherPatient = Patient::factory()->create();
        $foreignProfile = $this->makeProfile($otherPatient);

        try {
            $this->linkService()->link($this->consultation, $foreignProfile, $this->user);
            $this->fail('Expected a patient mismatch exception.');
        } catch (ConsultationMaternityLinkException $e) {
            $this->assertSame(ConsultationMaternityLinkException::PATIENT_MISMATCH, $e->errorCode);
        }

        $this->assertDatabaseCount('consultation_maternity_links', 0);
    }

    public function test_newborn_target_validates_against_the_mother_patient(): void
    {
        $otherPatient = Patient::factory()->create();
        $foreignProfile = $this->makeProfile($otherPatient);
        $labor = $this->makeLaborEpisode($foreignProfile, $otherPatient);
        $delivery = $this->makeDeliveryRecord($foreignProfile, $labor, $otherPatient);
        $newborn = $this->makeNewbornRecord($foreignProfile, $labor, $delivery, $otherPatient);

        try {
            $this->linkService()->link($this->consultation, $newborn, $this->user);
            $this->fail('Expected a patient mismatch exception for a newborn of another mother.');
        } catch (ConsultationMaternityLinkException $e) {
            $this->assertSame(ConsultationMaternityLinkException::PATIENT_MISMATCH, $e->errorCode);
        }
    }

    public function test_linking_the_same_target_twice_is_idempotent(): void
    {
        $profile = $this->makeProfile();
        $service = $this->linkService();

        $first = $service->link($this->consultation, $profile, $this->user);
        $second = $service->link($this->consultation, $profile, $this->user);

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('consultation_maternity_links', 1);
    }

    public function test_a_different_target_of_the_same_context_type_requires_relink(): void
    {
        $first = $this->makeProfile();
        $second = $this->makeProfile();
        $service = $this->linkService();

        $service->link($this->consultation, $first, $this->user);

        try {
            $service->link($this->consultation, $second, $this->user);
            $this->fail('Expected a relink-required exception.');
        } catch (ConsultationMaternityLinkException $e) {
            $this->assertSame(ConsultationMaternityLinkException::RELINK_REQUIRED, $e->errorCode);
        }
    }

    /* ── Relink / unlink ──────────────────────────────────────────────── */

    public function test_relink_preserves_the_old_row_and_keeps_one_active_row(): void
    {
        $first = $this->makeProfile();
        $second = $this->makeProfile();
        $service = $this->linkService();

        $old = $service->link($this->consultation, $first, $this->user);
        $new = $service->relink($this->consultation, $second, $this->user, 'Corrected pregnancy profile');

        $old->refresh();

        $this->assertNotSame($old->id, $new->id);
        $this->assertNull($old->active_slot, 'old row must be retired, not deleted');
        $this->assertNotNull($old->unlinked_at);
        $this->assertDatabaseCount('consultation_maternity_links', 2);

        $this->assertSame(1, ConsultationMaternityLink::query()
            ->forConsultation($this->consultation)
            ->forContextType(ConsultationMaternityContextType::PREGNANCY_PROFILE)
            ->active()->count());
    }

    public function test_multiple_inactive_historical_rows_are_allowed(): void
    {
        $a = $this->makeProfile();
        $b = $this->makeProfile();
        $c = $this->makeProfile();
        $service = $this->linkService();

        $service->link($this->consultation, $a, $this->user);
        $service->relink($this->consultation, $b, $this->user, 'first correction');
        $service->relink($this->consultation, $c, $this->user, 'second correction');

        $this->assertDatabaseCount('consultation_maternity_links', 3);
        $this->assertSame(2, ConsultationMaternityLink::query()->historical()->count());
        $this->assertSame(1, ConsultationMaternityLink::query()->active()->count());
    }

    public function test_unlink_requires_a_reason_and_never_deletes_the_row(): void
    {
        $profile = $this->makeProfile();
        $service = $this->linkService();
        $service->link($this->consultation, $profile, $this->user);

        try {
            $service->unlink($this->consultation, ConsultationMaternityContextType::PREGNANCY_PROFILE, $this->user, '   ');
            $this->fail('Expected a reason-required exception.');
        } catch (ConsultationMaternityLinkException $e) {
            $this->assertSame(ConsultationMaternityLinkException::REASON_REQUIRED, $e->errorCode);
        }

        $link = $service->unlink(
            $this->consultation,
            ConsultationMaternityContextType::PREGNANCY_PROFILE,
            $this->user,
            'Linked in error'
        );

        $this->assertNull($link->refresh()->active_slot);
        $this->assertDatabaseCount('consultation_maternity_links', 1);
    }

    public function test_link_relink_and_unlink_write_activity_logs(): void
    {
        $first = $this->makeProfile();
        $second = $this->makeProfile();
        $service = $this->linkService();

        $service->link($this->consultation, $first, $this->user);
        $service->relink($this->consultation, $second, $this->user, 'correction');
        $service->unlink($this->consultation, ConsultationMaternityContextType::PREGNANCY_PROFILE, $this->user, 'done');

        // Spatie stores the action in `event` and the module in `log_name`.
        foreach ([
            'CONSULTATION_MATERNITY_CONTEXT_LINKED',
            'CONSULTATION_MATERNITY_CONTEXT_RELINKED',
            'CONSULTATION_MATERNITY_CONTEXT_UNLINKED',
        ] as $action) {
            $this->assertTrue(
                ActivityLog::where('log_name', 'CONSULTATION')->where('event', $action)->exists(),
                "Missing activity log for {$action}"
            );
        }
    }

    /* ── Resolver ─────────────────────────────────────────────────────── */

    public function test_resolver_returns_none_when_no_context_exists(): void
    {
        $context = $this->resolver()->resolve($this->consultation);

        $this->assertTrue($context->isNone());
        $this->assertSame(ConsultationMaternityContext::SOURCE_NONE, $context->resolutionSource);
    }

    public function test_resolver_returns_the_explicit_link_first(): void
    {
        // An unrelated active profile exists that the fallback would otherwise pick.
        $this->makeProfile();
        $explicit = $this->makeProfile();

        $this->linkService()->link($this->consultation, $explicit, $this->user);

        $context = $this->resolver()->resolve($this->consultation);

        $this->assertTrue($context->isResolved());
        $this->assertSame(ConsultationMaternityContext::SOURCE_EXPLICIT, $context->resolutionSource);
        $this->assertSame($explicit->id, $context->pregnancyProfile?->id);
    }

    public function test_resolver_falls_back_to_the_same_visit(): void
    {
        // Two active profiles → the profile fallback would be ambiguous, so a
        // resolved result here proves the visit fallback ran first.
        $onVisit = $this->makeProfile();
        $this->makeProfile();
        $this->makeAncVisit($onVisit, $this->visit);

        $context = $this->resolver()->resolve($this->consultation);

        $this->assertTrue($context->isResolved());
        $this->assertSame(ConsultationMaternityContext::SOURCE_VISIT, $context->resolutionSource);
        $this->assertSame($onVisit->id, $context->pregnancyProfile?->id);
    }

    public function test_resolver_falls_back_to_the_same_admission(): void
    {
        // Two active profiles → the profile fallback alone would be ambiguous,
        // so a resolved result proves the admission fallback ran first. The
        // maternity record carries only admission_id (no visit_id), so the
        // visit fallback cannot match either.
        $onAdmission = $this->makeProfile();
        $this->makeProfile();

        $admission = $this->makeAdmission();

        LaborEpisode::create([
            'pregnancy_profile_id' => $onAdmission->id,
            'patient_id' => $this->patient->id,
            'admission_id' => $admission->id,
            'department_id' => $this->department->id,
            'started_at' => now(),
            'labor_stage' => 'first_stage',
            'status' => 'active',
        ]);

        $context = $this->resolver()->resolve($this->consultation->refresh());

        $this->assertTrue($context->isResolved());
        $this->assertSame(ConsultationMaternityContext::SOURCE_ADMISSION, $context->resolutionSource);
        $this->assertSame($onAdmission->id, $context->pregnancyProfile?->id);
    }

    public function test_resolver_falls_back_to_a_single_active_profile(): void
    {
        $profile = $this->makeProfile();

        $context = $this->resolver()->resolve($this->consultation);

        $this->assertTrue($context->isResolved());
        $this->assertSame(ConsultationMaternityContext::SOURCE_ACTIVE_PROFILE, $context->resolutionSource);
        $this->assertSame($profile->id, $context->pregnancyProfile?->id);
    }

    public function test_resolver_returns_ambiguous_for_multiple_active_profiles(): void
    {
        $this->makeProfile();
        $this->makeProfile();

        $context = $this->resolver()->resolve($this->consultation);

        $this->assertTrue($context->isAmbiguous());
        $this->assertNull($context->pregnancyProfile, 'resolver must never pick one');
        $this->assertSame(2, $context->candidateProfiles?->count());
        $this->assertNotEmpty($context->warnings);
    }

    public function test_resolver_never_creates_a_pregnancy_profile_or_maternity_record(): void
    {
        $profileCount = PregnancyProfile::count();
        $ancCount = AntenatalVisit::count();
        $laborCount = LaborEpisode::count();

        $this->resolver()->resolve($this->consultation);

        $this->assertSame($profileCount, PregnancyProfile::count());
        $this->assertSame($ancCount, AntenatalVisit::count());
        $this->assertSame($laborCount, LaborEpisode::count());
        // Resolution must never persist a link.
        $this->assertDatabaseCount('consultation_maternity_links', 0);
    }

    public function test_pregnancy_signals_do_not_cause_creation_or_linking(): void
    {
        // A positive pregnancy test / obstetric diagnosis / patient sex must
        // never imply a maternity context.
        ConsultationSpecialtyEntry::create([
            'consultation_id' => $this->consultation->id,
            'section_key' => 'sexual_sti_history',
            'entry' => ['pregnancy_test' => 'positive'],
        ]);

        $context = $this->resolver()->resolve($this->consultation);

        $this->assertTrue($context->isNone());
        $this->assertSame(0, PregnancyProfile::count());
        $this->assertDatabaseCount('consultation_maternity_links', 0);
    }

    public function test_explicit_link_survives_consultation_completion(): void
    {
        $profile = $this->makeProfile();
        $this->linkService()->link($this->consultation, $profile, $this->user);

        $this->consultation->forceFill([
            'status' => VisitConsultationRoute::STATUS_COMPLETED,
            'completed_at' => now(),
        ])->save();

        $context = $this->resolver()->resolve($this->consultation->refresh());

        $this->assertTrue($context->isResolved());
        $this->assertSame($profile->id, $context->pregnancyProfile?->id);
    }

    public function test_bridge_operations_never_create_specialty_entries(): void
    {
        $before = ConsultationSpecialtyEntry::count();
        $profile = $this->makeProfile();
        $service = $this->linkService();

        $service->link($this->consultation, $profile, $this->user);
        $service->unlink($this->consultation, ConsultationMaternityContextType::PREGNANCY_PROFILE, $this->user, 'done');
        $this->resolver()->resolve($this->consultation);

        $this->assertSame($before, ConsultationSpecialtyEntry::count());
    }

    /* ── Helpers ──────────────────────────────────────────────────────── */

    private function linkService(): ConsultationMaternityLinkService
    {
        return app(ConsultationMaternityLinkService::class);
    }

    private function resolver(): ConsultationMaternityContextResolver
    {
        return app(ConsultationMaternityContextResolver::class);
    }

    private function makeVisit(Patient $patient): Visit
    {
        return Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $this->user->id,
            'visit_type' => VisitType::OUTPATIENT,
            'status' => VisitStatus::CONSULTING,
            'current_department_id' => $this->department->id,
        ]);
    }

    private function makeConsultation(Visit $visit, Patient $patient): VisitConsultationRoute
    {
        return VisitConsultationRoute::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'department_id' => $this->department->id,
            'status' => VisitConsultationRoute::STATUS_ACTIVE,
            'routed_by' => $this->user->id,
        ]);
    }

    private function makeAdmission(): Admission
    {
        $ward = Ward::create([
            'name' => 'Bridge Test Ward',
            'code' => 'BTW01',
            'department_id' => $this->department->id,
            'capacity' => 4,
            'is_active' => true,
        ]);

        $bed = Bed::create([
            'ward_id' => $ward->id,
            'bed_number' => 'BTW-B1',
            'bed_type' => 'standard',
            'status' => BedStatus::AVAILABLE,
            'daily_rate' => 0,
        ]);

        return Admission::create([
            'admission_number' => Admission::generateAdmissionNumber(),
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'bed_id' => $bed->id,
            'admitted_by' => $this->user->id,
            'admission_date' => now(),
            'status' => AdmissionStatus::ADMITTED,
        ]);
    }

    private function makeProfile(?Patient $patient = null): PregnancyProfile
    {
        return PregnancyProfile::create([
            'patient_id' => ($patient ?? $this->patient)->id,
            'department_id' => $this->department->id,
            'created_by' => $this->user->id,
            'gravida' => 2,
            'para' => 1,
            'last_menstrual_period' => now()->subWeeks(16)->toDateString(),
            'estimated_due_date' => now()->addWeeks(24)->toDateString(),
            'profile_status' => PregnancyProfileStatus::ACTIVE->value,
        ]);
    }

    private function makeAncVisit(PregnancyProfile $profile, ?Visit $visit = null): AntenatalVisit
    {
        return AntenatalVisit::create([
            'pregnancy_profile_id' => $profile->id,
            'patient_id' => $profile->patient_id,
            'visit_id' => $visit?->id,
            'department_id' => $this->department->id,
            'recorded_by' => $this->user->id,
            'visit_date' => now(),
            'status' => 'recorded',
        ]);
    }

    private function makeLaborEpisode(PregnancyProfile $profile, ?Patient $patient = null): LaborEpisode
    {
        return LaborEpisode::create([
            'pregnancy_profile_id' => $profile->id,
            'patient_id' => ($patient ?? $this->patient)->id,
            'department_id' => $this->department->id,
            'started_at' => now(),
            'labor_stage' => 'first_stage',
            'status' => 'active',
        ]);
    }

    private function makeDeliveryRecord(
        PregnancyProfile $profile,
        LaborEpisode $labor,
        ?Patient $patient = null,
    ): DeliveryRecord {
        return DeliveryRecord::create([
            'labor_episode_id' => $labor->id,
            'pregnancy_profile_id' => $profile->id,
            'patient_id' => ($patient ?? $this->patient)->id,
            'department_id' => $this->department->id,
            'recorded_by' => $this->user->id,
            'delivery_at' => now(),
            'status' => 'draft',
        ]);
    }

    private function makeNewbornRecord(
        PregnancyProfile $profile,
        LaborEpisode $labor,
        DeliveryRecord $delivery,
        ?Patient $mother = null,
    ): NewbornRecord {
        return NewbornRecord::create([
            'delivery_record_id' => $delivery->id,
            'labor_episode_id' => $labor->id,
            'pregnancy_profile_id' => $profile->id,
            'mother_patient_id' => ($mother ?? $this->patient)->id,
            'department_id' => $this->department->id,
            'recorded_by' => $this->user->id,
            'birth_order' => 1,
            'birth_time' => now(),
        ]);
    }

    private function makePostnatalCase(
        PregnancyProfile $profile,
        LaborEpisode $labor,
        DeliveryRecord $delivery,
        ?Patient $mother = null,
    ): PostnatalCase {
        return PostnatalCase::create([
            'delivery_record_id' => $delivery->id,
            'labor_episode_id' => $labor->id,
            'pregnancy_profile_id' => $profile->id,
            'mother_patient_id' => ($mother ?? $this->patient)->id,
            'department_id' => $this->department->id,
            'opened_by' => $this->user->id,
        ]);
    }
}
