<?php

namespace Tests\Feature;

use App\Data\Maternity\Billing\MaternityBillingPolicyDecision as Decision;
use App\Enums\MaternityBillingDeduplicationPolicy as Policy;
use App\Models\ConsultationSpecialtyBillingApplication;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\ConsultationSpecialtyServiceMapping;
use App\Models\InvoiceItem;
use App\Models\MaternityBillingEvent;
use App\Models\VisitConsultationRoute;
use App\Services\Consultation\Maternity\ConsultationMaternityLinkService;
use App\Services\Maternity\MaternityBillingDeduplicationPolicyService;
use App\Services\Maternity\MaternityBillingPostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsMaternityHandoffFixtures;
use Tests\TestCase;

/**
 * Phase 14R.6 — advisory billing de-duplication policy.
 *
 * The load-bearing guarantees: posting stays unavailable, no invoice item is
 * ever created, and the BASE attendance charge is never mistaken for a
 * duplicate of a clinical maternity event.
 */
class MaternityBillingDeduplicationPolicyPhase14R6Test extends TestCase
{
    use BuildsMaternityHandoffFixtures;
    use RefreshDatabase;

    private VisitConsultationRoute $route;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'audit_streaming.async_writes' => false,
            'billing.maternity_billing.deduplication_policy_enabled' => true,
        ]);
        $this->buildMaternityFixture();
        $this->route = $this->consultationRoute();
    }

    /* ── Flag behaviour ────────────────────────────────────────────────── */

    public function test_policy_flag_off_returns_a_disabled_decision_only(): void
    {
        config(['billing.maternity_billing.deduplication_policy_enabled' => false]);

        $decision = app(MaternityBillingDeduplicationPolicyService::class)
            ->decide($this->ancVisit, 'anc_registration_package', $this->route->id);

        $this->assertTrue($decision->isDisabled());
        $this->assertSame(Decision::STATUS_POLICY_DISABLED, $decision->status);
        $this->assertSame(Decision::SOURCE_NONE, $decision->allowedBillingSource);
    }

    /* ── Approved defaults ─────────────────────────────────────────────── */

    public function test_obstetrics_without_a_maternity_event_is_consultation_only(): void
    {
        $decision = app(MaternityBillingDeduplicationPolicyService::class)
            ->decideForConsultationOnly($this->route->id);

        $this->assertSame(Policy::CONSULTATION_ONLY, $decision->policy);
        $this->assertSame(Decision::SOURCE_CONSULTATION, $decision->allowedBillingSource);
        $this->assertNull($decision->suppressedBillingSource);
    }

    public function test_every_maternity_event_defaults_to_maternity_event_only(): void
    {
        $service = app(MaternityBillingDeduplicationPolicyService::class);

        $cases = [
            [$this->ancVisit, 'anc_registration_package'],
            [$this->labor, 'labor_observation'],
            [$this->delivery(), 'delivery_normal'],
            [$this->postnatal(), 'postnatal_mother_care'],
        ];

        foreach ($cases as [$source, $mappingKey]) {
            $decision = $service->decide($source, $mappingKey, $this->route->id);

            $this->assertSame(
                Policy::MATERNITY_EVENT_ONLY,
                $decision->policy,
                class_basename($source).' should be maternity_event_only'
            );
            $this->assertSame(Decision::SOURCE_MATERNITY_EVENT, $decision->allowedBillingSource);
        }
    }

    public function test_newborn_resolves_maternity_event_only_and_reports_the_newborn_policy(): void
    {
        $newborn = \App\Models\NewbornRecord::create([
            'delivery_record_id' => $this->delivery()->id,
            'labor_episode_id' => $this->labor->id,
            'pregnancy_profile_id' => $this->profile->id,
            'mother_patient_id' => $this->patient->id,
            'department_id' => $this->department->id,
            'birth_order' => 1,
            'birth_time' => now(),
        ]);

        $decision = app(MaternityBillingDeduplicationPolicyService::class)
            ->decide($newborn, 'newborn_care', $this->route->id);

        $this->assertSame(Policy::MATERNITY_EVENT_ONLY, $decision->policy);
        // The existing MATERNITY_NEWBORN_BILLING_POLICY still governs who pays.
        $this->assertArrayHasKey('newborn_billing_policy', $decision->configuration);
    }

    /* ── Base vs event-specific ────────────────────────────────────────── */

    public function test_base_attendance_charge_is_not_treated_as_a_duplicate(): void
    {
        // A specialty billing application whose mapping is NOT one of the
        // maternity-act sections — i.e. an ordinary encounter charge.
        $this->specialtyApplication('current_complaints');

        $decision = app(MaternityBillingDeduplicationPolicyService::class)
            ->decide($this->ancVisit, 'anc_registration_package', $this->route->id);

        $this->assertSame(Decision::STATUS_NO_CONFLICT, $decision->status);
        $this->assertNull($decision->suppressedBillingSource);
        $this->assertNull($decision->consultationBillingApplicationId);
        $this->assertNotContains('event_specific_duplicate_risk', $decision->warnings);
    }

    public function test_event_specific_consultation_mapping_overlapping_anc_is_flagged(): void
    {
        $application = $this->specialtyApplication('antenatal_vitals');

        $decision = app(MaternityBillingDeduplicationPolicyService::class)
            ->decide($this->ancVisit, 'anc_registration_package', $this->route->id);

        $this->assertSame(Decision::STATUS_DUPLICATE_SOURCE_SUPPRESSED, $decision->status);
        $this->assertSame(Decision::SOURCE_MATERNITY_EVENT, $decision->allowedBillingSource);
        $this->assertTrue($decision->suppressesConsultationCharge());
        $this->assertSame($application->id, $decision->consultationBillingApplicationId);
        $this->assertContains('event_specific_duplicate_risk', $decision->warnings);
    }

    /* ── Duplicate identity ────────────────────────────────────────────── */

    public function test_duplicate_identity_uses_the_source_record_not_patient_and_date(): void
    {
        $service = app(MaternityBillingDeduplicationPolicyService::class);

        $identity = $service->duplicateIdentity($this->ancVisit, 'anc_follow_up');

        $this->assertSame([
            'source_type' => 'antenatal_visit',
            'source_id' => $this->ancVisit->id,
            'mapping_key' => 'anc_follow_up',
        ], $identity);
        $this->assertArrayNotHasKey('patient_id', $identity);
        $this->assertArrayNotHasKey('date', $identity);
    }

    public function test_two_anc_visits_on_the_same_day_are_not_duplicates_of_each_other(): void
    {
        $second = \App\Models\AntenatalVisit::create([
            'pregnancy_profile_id' => $this->profile->id,
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'department_id' => $this->department->id,
            'recorded_by' => $this->user->id,
            'visit_date' => $this->ancVisit->visit_date,
            'visit_number' => 2,
            'status' => 'recorded',
        ]);

        $service = app(MaternityBillingDeduplicationPolicyService::class);

        $this->assertNotSame(
            $service->duplicateIdentity($this->ancVisit, 'anc_follow_up'),
            $service->duplicateIdentity($second, 'anc_follow_up'),
        );
    }

    /* ── Opt-in policies remain unavailable ────────────────────────────── */

    public function test_both_and_manual_selection_remain_unavailable_by_default(): void
    {
        $service = app(MaternityBillingDeduplicationPolicyService::class);

        $this->assertFalse($service->bothWhenConfiguredAllowed());
        $this->assertFalse($service->manualSelectionAllowed());
        $this->assertFalse(config('billing.maternity_billing.allow_both_when_configured'));
        $this->assertFalse(config('billing.maternity_billing.allow_manual_selection'));
    }

    public function test_an_unsupported_source_requires_manual_review(): void
    {
        $decision = app(MaternityBillingDeduplicationPolicyService::class)
            ->decide($this->profile, 'anc_follow_up', $this->route->id);

        $this->assertSame(Policy::MANUAL_SELECTION, $decision->policy);
        $this->assertTrue($decision->requiresManualReview);
        $this->assertContains('manual_selection_disabled', $decision->warnings);
    }

    /* ── Preview integration and non-posting ───────────────────────────── */

    public function test_preview_includes_the_policy_decision(): void
    {
        app(ConsultationMaternityLinkService::class)
            ->link($this->route, $this->ancVisit, $this->user);

        $preview = app(MaternityBillingPostingService::class)
            ->previewForSource($this->ancVisit, 'anc_registration_package');

        $this->assertArrayHasKey('deduplication_policy', $preview);
        $this->assertSame(
            Policy::MATERNITY_EVENT_ONLY->value,
            $preview['deduplication_policy']['policy']
        );
        $this->assertSame($this->route->id, $preview['deduplication_policy']['consultation_route_id']);
    }

    public function test_posting_remains_unavailable_and_creates_nothing(): void
    {
        $invoiceItems = InvoiceItem::query()->count();
        $events = MaternityBillingEvent::query()->count();

        $result = app(MaternityBillingPostingService::class)
            ->postForSource($this->ancVisit, 'anc_registration_package', $this->user);

        $this->assertFalse($result['posted']);
        $this->assertSame(
            MaternityBillingPostingService::STATUS_POSTING_NOT_IMPLEMENTED,
            $result['status']
        );
        $this->assertSame($invoiceItems, InvoiceItem::query()->count());
        $this->assertSame($events, MaternityBillingEvent::query()->count());
    }

    public function test_evaluating_the_policy_creates_nothing_at_all(): void
    {
        $before = [
            'invoice_items' => InvoiceItem::query()->count(),
            'events' => MaternityBillingEvent::query()->count(),
            'applications' => ConsultationSpecialtyBillingApplication::query()->count(),
        ];

        $service = app(MaternityBillingDeduplicationPolicyService::class);
        $service->decide($this->ancVisit, 'anc_registration_package', $this->route->id);
        $service->decide($this->labor, 'labor_observation', $this->route->id);
        $service->decideForConsultationOnly($this->route->id);

        $this->assertSame($before['invoice_items'], InvoiceItem::query()->count());
        $this->assertSame($before['events'], MaternityBillingEvent::query()->count());
        $this->assertSame($before['applications'], ConsultationSpecialtyBillingApplication::query()->count());
        $this->assertFalse((bool) config('billing.maternity_billing.enabled'));
    }

    /* ── Helpers ───────────────────────────────────────────────────────── */

    private function specialtyApplication(string $sectionKey): ConsultationSpecialtyBillingApplication
    {
        $profile = ConsultationSpecialtyProfile::firstOrCreate(
            ['code' => 'obstetrics'],
            ['name' => 'Obstetrics', 'is_active' => true, 'sort_order' => 40]
        );

        $mapping = ConsultationSpecialtyServiceMapping::create([
            'consultation_specialty_profile_id' => $profile->id,
            'section_key' => $sectionKey,
            'billing_trigger' => 'manual',
            'priority' => 10,
            'is_active' => true,
        ]);

        return ConsultationSpecialtyBillingApplication::create([
            'consultation_id' => $this->route->id,
            'consultation_specialty_profile_id' => $profile->id,
            'consultation_specialty_service_mapping_id' => $mapping->id,
            'status' => 'applied',
            'trigger' => 'manual',
            'applied_by' => $this->user->id,
        ]);
    }
}
