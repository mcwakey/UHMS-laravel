<?php

namespace Tests\Feature\FrontDesk;

use App\Models\ActivityLog;
use App\Models\Admission;
use App\Models\Bed;
use App\Models\FrontDeskVisitorLog;
use App\Models\Patient;
use App\Models\Visit;
use App\Models\Ward;
use App\Services\FrontDesk\PatientVisitorRuleService;
use App\Services\FrontDesk\VisitorLogService;

/**
 * Phase 18B — patient visitor management: badge generation, admission-linked
 * workflow, advisory warnings, checkout improvements, visitor pass, history
 * filters, dashboard metrics and audit.
 */
class PatientVisitorManagementTest extends FrontDeskTestCase
{
    private const SECRET_DX = 'ZZCONFIDENTIALDX';

    /* ── Badge generation ────────────────────────────────────────── */

    public function test_auto_generates_badge_number_when_blank(): void
    {
        $log = app(VisitorLogService::class)->create([
            'visitor_context' => 'facility', 'visitor_name' => 'No Badge',
        ], $this->user);

        $this->assertMatchesRegularExpression('/^VIS-\d{8}-\d{4}$/', $log->badge_number);
    }

    public function test_keeps_manually_entered_badge_number(): void
    {
        $log = app(VisitorLogService::class)->create([
            'visitor_context' => 'facility', 'visitor_name' => 'Manual Badge', 'badge_number' => 'MANUAL-007',
        ], $this->user);

        $this->assertSame('MANUAL-007', $log->badge_number);
    }

    public function test_badge_numbers_are_unique_and_sequential(): void
    {
        $service = app(VisitorLogService::class);
        $a = $service->create(['visitor_context' => 'facility', 'visitor_name' => 'A'], $this->user);
        $b = $service->create(['visitor_context' => 'facility', 'visitor_name' => 'B'], $this->user);

        $this->assertNotSame($a->badge_number, $b->badge_number);
        $this->assertSame(1, (int) substr($a->badge_number, -4));
        $this->assertSame(2, (int) substr($b->badge_number, -4));
    }

    /* ── Admission-linked workflow ───────────────────────────────── */

    public function test_create_visitor_linked_to_patient_resolves_active_admission(): void
    {
        ['patient' => $patient, 'admission' => $admission, 'ward' => $ward, 'bed' => $bed] = $this->admittedPatient();

        $log = app(VisitorLogService::class)->create([
            'visitor_context' => 'patient', 'visitor_name' => 'Relative', 'patient_id' => $patient->id,
        ], $this->user);

        $this->assertSame($admission->id, $log->admission_id);
        $this->assertSame($ward->id, $log->ward_id);
        $this->assertSame($bed->id, $log->bed_id);
    }

    public function test_patient_linked_visitor_does_not_expose_clinical_data(): void
    {
        ['patient' => $patient] = $this->admittedPatient();
        $log = app(VisitorLogService::class)->create([
            'visitor_context' => 'patient', 'visitor_name' => 'Relative', 'patient_id' => $patient->id,
        ], $this->user);

        // NB: admin pages render through the Legacy/BladePage bridge which
        // JSON-encodes the body (slashes escaped), so assert on the patient's
        // name (safe identity shown) rather than the number with its "/".
        $this->actingAs($this->user)->get(route('admin.front-desk.visitors.show', $log))
            ->assertOk()
            ->assertSee($patient->first_name)
            ->assertDontSee(self::SECRET_DX);
    }

    public function test_discharged_patient_warning_appears(): void
    {
        ['patient' => $patient, 'admission' => $admission] = $this->admittedPatient();
        $admission->update(['status' => 'discharged', 'actual_discharge_date' => now()->subDays(3)]);

        $warnings = app(PatientVisitorRuleService::class)->warningsForPatient($patient->fresh());
        $codes = array_column($warnings, 'code');

        $this->assertContains('patient_discharged', $codes);
    }

    /* ── Visitor warnings ────────────────────────────────────────── */

    public function test_warns_when_patient_has_no_active_admission(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);

        $codes = array_column(app(PatientVisitorRuleService::class)->warningsForPatient($patient), 'code');
        $this->assertContains('patient_not_admitted', $codes);
    }

    public function test_warns_when_active_visitor_limit_reached(): void
    {
        ['patient' => $patient, 'admission' => $admission] = $this->admittedPatient();
        // Limit is 2 per patient; create two currently-inside visitors.
        foreach (['A', 'B'] as $name) {
            $this->makeVisitor(['patient_id' => $patient->id, 'admission_id' => $admission->id, 'visitor_name' => $name]);
        }

        $codes = array_column(app(PatientVisitorRuleService::class)->warningsForPatient($patient->fresh(), $admission), 'code');
        $this->assertContains('visitor_limit_for_patient_reached', $codes);
    }

    public function test_warns_when_same_phone_already_checked_in(): void
    {
        $this->makeVisitor(['visitor_phone' => '0244999888', 'visitor_name' => 'Already In']);

        $codes = array_column(app(PatientVisitorRuleService::class)->warningsFor(['visitor_phone' => '0244999888']), 'code');
        $this->assertContains('visitor_already_inside_same_phone', $codes);
    }

    public function test_warnings_do_not_hard_block_create(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]); // not admitted → warning

        $this->actingAs($this->user)->post(route('admin.front-desk.visitors.store'), [
            'visitor_context' => 'patient', 'visitor_name' => 'Warned Visitor', 'patient_id' => $patient->id,
        ])->assertRedirect(route('admin.front-desk.visitors.index'));

        $log = FrontDeskVisitorLog::firstWhere('visitor_name', 'Warned Visitor');
        $this->assertNotNull($log);
        $this->assertContains('patient_not_admitted', $log->metadata['visitor_warnings'] ?? []);
    }

    /* ── Checkout improvements ───────────────────────────────────── */

    public function test_checkout_accepts_optional_note(): void
    {
        $log = $this->makeVisitor(['time_in' => now()->subHour()]);

        $this->actingAs($this->user)->post(route('admin.front-desk.visitors.check-out', $log), [
            'checkout_note' => 'Left via main gate',
        ])->assertRedirect();

        $log->refresh();
        $this->assertSame('checked_out', $log->status->value);
        $this->assertSame('Left via main gate', $log->checkoutNote());
        $this->assertNotNull($log->durationMinutes());
    }

    public function test_cannot_checkout_twice(): void
    {
        $log = $this->makeVisitor(['status' => 'checked_out', 'time_out' => now(), 'checked_out_by' => $this->user->id]);

        $this->actingAs($this->user)->post(route('admin.front-desk.visitors.check-out', $log))
            ->assertSessionHasErrors('status');
    }

    /* ── Visitor pass ────────────────────────────────────────────── */

    public function test_authorized_user_can_view_visitor_pass(): void
    {
        ['patient' => $patient, 'ward' => $ward] = $this->admittedPatient();
        $log = $this->makeVisitor(['patient_id' => $patient->id, 'ward_id' => $ward->id, 'badge_number' => 'VIS-TEST-0001']);

        $this->actingAs($this->user)->get(route('admin.front-desk.visitors.pass', $log))
            ->assertOk()
            ->assertSee('VIS-TEST-0001')
            ->assertSee($log->visitor_name)
            ->assertSee($patient->patient_number)
            ->assertSee($ward->name)
            ->assertDontSee(self::SECRET_DX);
    }

    public function test_unauthorized_user_cannot_view_visitor_pass(): void
    {
        $log = $this->makeVisitor();
        $viewer = $this->userWith(['front_desk.view', 'front_desk.visitors.view']); // no print_pass

        $this->actingAs($viewer)->get(route('admin.front-desk.visitors.pass', $log))->assertForbidden();
    }

    /* ── History / filters ───────────────────────────────────────── */

    public function test_patient_visitor_history_filter_works(): void
    {
        ['patient' => $patient] = $this->admittedPatient();
        $this->makeVisitor(['patient_id' => $patient->id, 'visitor_name' => 'Belongs Here']);
        $this->makeVisitor(['visitor_name' => 'Different Visitor']);

        $this->actingAs($this->user)->get(route('admin.front-desk.visitors.patient-history', $patient))
            ->assertOk()
            ->assertSee('Belongs Here')
            ->assertDontSee('Different Visitor');
    }

    public function test_admission_visitor_history_works(): void
    {
        ['patient' => $patient, 'admission' => $admission] = $this->admittedPatient();
        $this->makeVisitor(['patient_id' => $patient->id, 'admission_id' => $admission->id, 'visitor_name' => 'Admission Guest']);

        $this->actingAs($this->user)->get(route('admin.front-desk.visitors.admission-history', $admission))
            ->assertOk()
            ->assertSee('Admission Guest');
    }

    public function test_quick_filters_work(): void
    {
        $inside = $this->makeVisitor(['visitor_name' => 'Inside Guest', 'time_in' => now()->subHour()]);
        $overdue = $this->makeVisitor(['visitor_name' => 'Overdue Guest', 'time_in' => now()->subHours(6)]);
        $out = $this->makeVisitor(['visitor_name' => 'Gone Guest', 'status' => 'checked_out', 'time_out' => now()]);
        ['patient' => $patient] = $this->admittedPatient();
        $patientVisitor = $this->makeVisitor(['visitor_name' => 'Patient Guest', 'patient_id' => $patient->id]);

        $this->actingAs($this->user)->get(route('admin.front-desk.visitors.index', ['quick' => 'inside']))
            ->assertOk()->assertSee('Inside Guest')->assertDontSee('Gone Guest');

        $this->actingAs($this->user)->get(route('admin.front-desk.visitors.index', ['quick' => 'overdue']))
            ->assertOk()->assertSee('Overdue Guest');

        $this->actingAs($this->user)->get(route('admin.front-desk.visitors.index', ['quick' => 'patient']))
            ->assertOk()->assertSee('Patient Guest');

        $this->actingAs($this->user)->get(route('admin.front-desk.visitors.index', ['quick' => 'checked_out_today']))
            ->assertOk()->assertSee('Gone Guest')->assertDontSee('Inside Guest');
    }

    /* ── Dashboard ───────────────────────────────────────────────── */

    public function test_dashboard_patient_visitor_metrics(): void
    {
        ['patient' => $patient, 'ward' => $ward] = $this->admittedPatient();
        $this->makeVisitor(['patient_id' => $patient->id, 'ward_id' => $ward->id, 'time_in' => now()->subHour()]);
        $this->makeVisitor(['patient_id' => $patient->id, 'ward_id' => $ward->id, 'time_in' => now()->subHours(6)]); // overdue
        $this->makeVisitor(['visitor_name' => 'Facility One']); // facility

        $metrics = app(\App\Services\FrontDesk\FrontDeskDashboardService::class)->metrics();

        $this->assertSame(2, $metrics['patient_visitors_inside_count']);
        $this->assertSame(1, $metrics['facility_visitors_inside_count']);
        $this->assertSame(1, $metrics['overdue_patient_visitors_count']);
        $this->assertSame(1, $metrics['wards_with_visitors']);
        $this->assertCount(1, $metrics['top_wards_by_active_visitors']);
    }

    /* ── Audit ───────────────────────────────────────────────────── */

    public function test_visitor_pass_view_is_audited(): void
    {
        $log = $this->makeVisitor();

        $this->actingAs($this->user)->get(route('admin.front-desk.visitors.pass', $log))->assertOk();

        $this->assertTrue(ActivityLog::where('event', 'FRONT_DESK_VISITOR_PASS_PRINTED')->exists());
    }

    /* ── Localisation ────────────────────────────────────────────── */

    public function test_en_fr_keys_and_view_cache(): void
    {
        foreach (['en', 'fr'] as $locale) {
            app()->setLocale($locale);
            $this->assertNotSame('front_desk.pass.title', __('front_desk.pass.title'));
            $this->assertNotSame('front_desk.warnings.patient_discharged', __('front_desk.warnings.patient_discharged'));
            $this->assertNotSame('front_desk.quick.inside', __('front_desk.quick.inside'));
        }
        app()->setLocale('en');

        $this->artisan('view:clear')->assertExitCode(0);
        $this->artisan('view:cache')->assertExitCode(0);
    }

    /* ── Helpers ─────────────────────────────────────────────────── */

    /**
     * @return array{patient: Patient, admission: Admission, ward: Ward, bed: Bed}
     */
    private static int $seq = 0;

    private function admittedPatient(string $diagnosis = self::SECRET_DX): array
    {
        $n = ++self::$seq;
        $ward = Ward::create([
            'name' => 'Test Ward ' . $n,
            'code' => 'TW' . $n,
            'department_id' => $this->department->id,
            'capacity' => 20,
            'is_active' => true,
        ]);
        $bed = Bed::create([
            'ward_id' => $ward->id,
            'bed_number' => 'B' . $n,
            'status' => 'occupied',
        ]);
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'current_department_id' => $this->department->id,
            'created_by' => $this->user->id,
        ]);
        $admission = Admission::create([
            'admission_number' => 'ADM-' . $n,
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
            'bed_id' => $bed->id,
            'admitted_by' => $this->user->id,
            'admitting_diagnosis' => $diagnosis,
            'admission_date' => now()->subDay(),
            'status' => 'admitted',
        ]);

        return compact('patient', 'admission', 'ward', 'bed');
    }

    private function makeVisitor(array $overrides = []): FrontDeskVisitorLog
    {
        return FrontDeskVisitorLog::create(array_merge([
            'visitor_context' => 'facility',
            'visitor_name' => 'Test Visitor',
            'time_in' => now(),
            'status' => 'checked_in',
            'checked_in_by' => $this->user->id,
        ], $overrides));
    }
}
