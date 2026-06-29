<?php

namespace Tests\Feature\Journey;

use App\Enums\JourneyDelayCause;
use App\Enums\VisitStatus;
use App\Models\Department;
use App\Models\User;
use App\Models\Visit;
use App\Services\Journey\JourneyActionLinkResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JourneyActionLinkTest extends TestCase
{
    use RefreshDatabase;

    private function department(string $type): Department
    {
        return Department::create(['name' => $type, 'code' => 'L'.uniqid(), 'type' => $type, 'status' => 'active']);
    }

    private function userIn(string $type): User
    {
        return User::factory()->create(['department_id' => $this->department($type)->id]);
    }

    private function visit(VisitStatus $status, string $type): Visit
    {
        $creator = User::factory()->create();

        return Visit::factory()->create([
            'status' => $status, 'current_department_id' => $this->department($type)->id, 'created_by' => $creator->id,
        ]);
    }

    private function labRequest(Visit $visit): void
    {
        DB::table('lab_requests')->insert([
            'request_number' => 'LR-'.uniqid(), 'requested_by' => $visit->created_by,
            'visit_id' => $visit->id, 'patient_id' => $visit->patient_id,
            'target_department_id' => $visit->current_department_id, 'status' => 'pending',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function resolve(JourneyDelayCause $cause, Visit $visit, User $user): ?string
    {
        return app(JourneyActionLinkResolver::class)->resolve($cause, $visit, $user);
    }

    public function test_lab_result_link_for_investigation_user(): void
    {
        $visit = $this->visit(VisitStatus::LAB, 'investigation');
        $this->labRequest($visit);

        $url = $this->resolve(JourneyDelayCause::AWAITING_LAB_RESULT, $visit->fresh(), $this->userIn('investigation'));

        $this->assertNotNull($url);
        $this->assertStringContainsString('lab/requests', $url);
    }

    public function test_dispensing_link_for_pharmacy_user(): void
    {
        $url = $this->resolve(JourneyDelayCause::AWAITING_DISPENSING, $this->visit(VisitStatus::PHARMACY, 'pharmacy'), $this->userIn('pharmacy'));

        $this->assertNotNull($url);
        $this->assertStringContainsString('pharmacy/dispensing', $url);
    }

    public function test_bed_link_for_ward_user(): void
    {
        $url = $this->resolve(JourneyDelayCause::AWAITING_BED, $this->visit(VisitStatus::ADMITTING, 'inpatient'), $this->userIn('inpatient'));

        $this->assertNotNull($url);
        $this->assertStringContainsString('admissions', $url);
    }

    public function test_payment_link_for_finance_user(): void
    {
        $url = $this->resolve(JourneyDelayCause::AWAITING_PAYMENT, $this->visit(VisitStatus::BILLING, 'finance'), $this->userIn('finance'));

        $this->assertNotNull($url);
        $this->assertStringContainsString('billing/invoices', $url);
    }

    public function test_consultation_user_can_open_the_visit_for_request_actions(): void
    {
        $url = $this->resolve(JourneyDelayCause::AWAITING_CONSULTATION, $this->visit(VisitStatus::WAITING, 'consultation'), $this->userIn('consultation'));

        $this->assertNotNull($url);
        $this->assertStringContainsString('visits', $url);
    }

    public function test_unauthorized_user_gets_no_link(): void
    {
        $visit = $this->visit(VisitStatus::LAB, 'investigation');
        $this->labRequest($visit);

        // A store keeper has stock_access, not investigation_access → no lab deep link.
        $url = $this->resolve(JourneyDelayCause::AWAITING_LAB_RESULT, $visit->fresh(), $this->userIn('stores'));

        $this->assertNull($url);
    }
}
