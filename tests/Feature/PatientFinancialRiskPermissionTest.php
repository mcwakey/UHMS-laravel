<?php

namespace Tests\Feature;

use App\Data\Billing\PatientFinancialRiskData;
use App\Enums\PatientFinancialRiskLevel;
use App\Models\Patient;
use App\Models\User;
use App\Services\Billing\PatientFinancialRiskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PatientFinancialRiskPermissionTest extends TestCase
{
    use RefreshDatabase;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        config(['audit_streaming.async_writes' => false]);
        User::factory()->create(); // id 1 for PatientFactory registered_by
        foreach ([
            'patients.view', 'patients.financial_risk.view', 'patients.financial_risk.manage',
            'patients.financial_risk.review', 'patients.financial_risk.clear',
            'patients.financial_risk.history', 'patients.financial_risk.report',
        ] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->patient = Patient::factory()->create();
    }

    private function user(array $permissions): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user;
    }

    private function classify(): void
    {
        app(PatientFinancialRiskService::class)->createOrClassify(
            $this->patient,
            PatientFinancialRiskData::fromValidated([
                'risk_level' => PatientFinancialRiskLevel::HIGH_RISK->value,
                'primary_reason' => 'other',
                'reason_details' => 'CONFIDENTIALmarker42',
                'effective_from' => now()->toDateString(),
            ]),
            $this->user(['patients.financial_risk.manage']),
        );
    }

    public function test_authorised_user_sees_financial_risk_section(): void
    {
        $this->classify();
        $user = $this->user(['patients.view', 'patients.financial_risk.view', 'patients.financial_risk.history']);

        $this->actingAs($user)->get(route('admin.patients.show', $this->patient))
            ->assertOk()
            ->assertSee('CONFIDENTIALmarker42');
    }

    public function test_unauthorised_user_never_receives_financial_risk_data_in_page_source(): void
    {
        $this->classify();
        $user = $this->user(['patients.view']); // no financial_risk.view

        $response = $this->actingAs($user)->get(route('admin.patients.show', $this->patient))->assertOk();
        $response->assertDontSee('CONFIDENTIALmarker42');
        $response->assertDontSee('financial-risk'); // no tab/pane leaked
    }

    public function test_manage_permission_is_required_to_classify(): void
    {
        $this->actingAs($this->user(['patients.view']))
            ->post(route('admin.patients.financial-risk.store', $this->patient), [
                'risk_level' => 'watchlist', 'primary_reason' => 'other', 'reason_details' => 'x',
                'effective_from' => now()->toDateString(),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('patient_financial_risk_profiles', 0);
    }

    public function test_worklist_requires_view_and_report_requires_report_permission(): void
    {
        $this->actingAs($this->user(['patients.view']))
            ->get(route('admin.billing.financial-risk.index'))->assertForbidden();

        $this->actingAs($this->user(['patients.financial_risk.view']))
            ->get(route('admin.billing.financial-risk.index'))->assertOk();

        $this->actingAs($this->user(['patients.financial_risk.view']))
            ->get(route('admin.billing.financial-risk.report'))->assertForbidden();

        $this->actingAs($this->user(['patients.financial_risk.report', 'patients.financial_risk.view']))
            ->get(route('admin.billing.financial-risk.report'))->assertOk();
    }

    public function test_clear_requires_clear_permission(): void
    {
        $this->classify();
        $profile = $this->patient->activeFinancialRiskProfile;

        $this->actingAs($this->user(['patients.financial_risk.review']))
            ->post(route('admin.patients.financial-risk.clear', [$this->patient, $profile]), ['reason' => 'done'])
            ->assertForbidden();
    }
}
