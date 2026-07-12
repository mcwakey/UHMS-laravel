<?php

namespace Tests\Feature;

use App\Data\Billing\VisitPaymentArrangementData;
use App\Enums\VisitType;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitPaymentArrangement;
use App\Services\Billing\VisitPaymentArrangementService;
use Database\Seeders\PaymentTimingSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class VisitPaymentArrangementPermissionTest extends TestCase
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
            'visits.payment_arrangement.view', 'visits.payment_arrangement.request',
            'visits.payment_arrangement.approve', 'visits.payment_arrangement.reject',
            'visits.payment_arrangement.withdraw', 'visits.payment_arrangement.revoke',
            'visits.payment_arrangement.history', 'visits.payment_arrangement.report',
        ] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->visit = Visit::factory()->create(['patient_id' => Patient::factory()->create()->id, 'visit_type' => VisitType::OUTPATIENT->value]);
    }

    private function user(array $permissions): User
    {
        $u = User::factory()->create();
        $u->givePermissionTo($permissions);

        return $u;
    }

    private function pending(): VisitPaymentArrangement
    {
        return app(VisitPaymentArrangementService::class)->request(
            $this->visit,
            VisitPaymentArrangementData::fromValidated(['requested_policy' => 'pay_after_all_services', 'request_reason' => 'x', 'effective_from' => now()->toDateString()]),
            $this->user(['visits.payment_arrangement.request']),
        );
    }

    public function test_worklist_requires_view_permission(): void
    {
        $this->actingAs($this->user([]))->get(route('admin.billing.visit-payment-arrangements.index'))->assertForbidden();
        $this->actingAs($this->user(['visits.payment_arrangement.view']))->get(route('admin.billing.visit-payment-arrangements.index'))->assertOk();
    }

    public function test_request_requires_request_permission(): void
    {
        $this->actingAs($this->user(['visits.payment_arrangement.view']))
            ->post(route('admin.billing.visits.payment-arrangements.store', $this->visit), [
                'requested_policy' => 'pay_after_all_services', 'request_reason' => 'x', 'effective_from' => now()->toDateString(),
            ])->assertForbidden();

        $this->assertDatabaseCount('visit_payment_arrangements', 0);
    }

    public function test_accountant_style_user_cannot_approve(): void
    {
        $arrangement = $this->pending();
        // has request/withdraw but NOT approve
        $this->actingAs($this->user(['visits.payment_arrangement.request', 'visits.payment_arrangement.withdraw']))
            ->post(route('admin.billing.visit-payment-arrangements.approve', $arrangement))
            ->assertForbidden();
    }

    public function test_approver_with_permission_can_approve(): void
    {
        $arrangement = $this->pending();
        $this->actingAs($this->user(['visits.payment_arrangement.approve']))
            ->post(route('admin.billing.visit-payment-arrangements.approve', $arrangement), ['decision_reason' => 'ok'])
            ->assertRedirect();

        $this->assertSame('approved', $arrangement->fresh()->status->value);
    }

    public function test_report_requires_report_permission(): void
    {
        $this->actingAs($this->user(['visits.payment_arrangement.view']))->get(route('admin.billing.visit-payment-arrangements.report'))->assertForbidden();
        $this->actingAs($this->user(['visits.payment_arrangement.report']))->get(route('admin.billing.visit-payment-arrangements.report'))->assertOk();
    }
}
