<?php

namespace Tests\Feature;

use App\Enums\VisitType;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Database\Seeders\PaymentTimingSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class VisitPaymentPolicyPermissionTest extends TestCase
{
    use RefreshDatabase;

    private Visit $visit;

    protected function setUp(): void
    {
        parent::setUp();
        config(['audit_streaming.async_writes' => false]);
        User::factory()->create(); // id 1
        (new PaymentTimingSettingsSeeder)->run();
        foreach ([
            'visits.payment_policy.view', 'visits.payment_policy.history',
            'visits.payment_policy.report', 'visits.payment_policy.refresh',
        ] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->visit = Visit::factory()->create([
            'patient_id' => Patient::factory()->create()->id,
            'visit_type' => VisitType::OUTPATIENT->value,
        ]);
    }

    private function user(array $permissions): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user;
    }

    public function test_view_permission_required_for_worklist_and_detail(): void
    {
        $this->actingAs($this->user([]))
            ->get(route('admin.billing.visit-payment-policies.index'))->assertForbidden();

        $this->actingAs($this->user(['visits.payment_policy.view']))
            ->get(route('admin.billing.visit-payment-policies.index'))->assertOk();

        $this->actingAs($this->user(['visits.payment_policy.view']))
            ->get(route('admin.billing.visit-payment-policies.show', $this->visit))->assertOk();
    }

    public function test_report_requires_report_permission(): void
    {
        $this->actingAs($this->user(['visits.payment_policy.view']))
            ->get(route('admin.billing.visit-payment-policies.report'))->assertForbidden();

        $this->actingAs($this->user(['visits.payment_policy.report']))
            ->get(route('admin.billing.visit-payment-policies.report'))->assertOk();
    }

    public function test_refresh_requires_refresh_permission(): void
    {
        $this->actingAs($this->user(['visits.payment_policy.view']))
            ->post(route('admin.billing.visit-payment-policies.refresh', $this->visit))->assertForbidden();

        $this->actingAs($this->user(['visits.payment_policy.refresh']))
            ->post(route('admin.billing.visit-payment-policies.refresh', $this->visit))
            ->assertRedirect();
    }

    public function test_unauthorised_user_cannot_reach_visit_policy_pages(): void
    {
        $this->actingAs($this->user([]))
            ->get(route('admin.billing.visit-payment-policies.show', $this->visit))->assertForbidden();
    }
}
