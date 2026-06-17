<?php

namespace Tests\Feature\Accounting;

use App\Models\Invoice;
use App\Models\InvoiceReceivable;
use App\Models\Patient;
use App\Models\ReceivableCase;
use App\Models\ReceivableDispute;
use App\Models\ReceivablePromise;
use App\Models\ReceivableStatementRun;
use App\Models\User;
use App\Models\Visit;
use App\Services\ReceivableCaseService;
use App\Services\ReceivableRecommendationService;
use App\Services\ReceivableStatementService;
use App\Services\ReceivableWorkbenchService;
use Database\Seeders\ModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ReceivableWorkbenchPhaseJTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private const PERMISSIONS = [
        'receivables.workbench.view',
        'receivables.cases.view',
        'receivables.cases.manage',
        'receivables.cases.assign',
        'receivables.followups.create',
        'receivables.promises.manage',
        'receivables.disputes.manage',
        'receivables.dunning.generate',
        'receivables.statements.generate',
        'receivables.statements.approve',
        'receivables.recommendations.writeoff',
        'receivables.recommendations.creditnote',
        'receivables.reports.view',
        'receivables.reports.export',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(ModuleSeeder::class);

        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_receivables_dashboard_is_permission_protected(): void
    {
        $this->get(route('admin.accounting.receivables.index'))->assertForbidden();

        $this->user->givePermissionTo('receivables.workbench.view');

        $this->get(route('admin.accounting.receivables.index'))
            ->assertOk()
            ->assertSee('Receivables Workbench');
    }

    public function test_aging_and_payer_balances_group_open_receivables(): void
    {
        $this->user->givePermissionTo('receivables.workbench.view');
        $patient = $this->receivable(100, 'patient', today()->subDays(10));
        $insurance = $this->receivable(250, 'insurance', today()->subDays(45));

        $dashboard = app(ReceivableWorkbenchService::class)->dashboard();

        $this->assertEqualsWithDelta(350, $dashboard['metrics']['total_ar'], 0.001);
        $this->assertEqualsWithDelta(100, $dashboard['metrics']['b1_30'], 0.001);
        $this->assertEqualsWithDelta(250, $dashboard['metrics']['b31_60'], 0.001);
        $this->assertCount(2, $dashboard['payer_balances']);
        $this->assertSame($patient->payerName(), collect($dashboard['payer_balances'])->firstWhere('payer_type', 'patient')['payer_name']);
        $this->assertSame($insurance->payer_type, collect($dashboard['payer_balances'])->firstWhere('payer_type', 'insurance')['payer_type']);
    }

    public function test_case_workflow_tracks_assignment_followup_promise_dispute_and_dunning_without_reducing_balance(): void
    {
        $receivable = $this->receivable(500, 'patient', today()->subDays(20));
        $collector = User::factory()->create();

        $service = app(ReceivableCaseService::class);
        $case = $service->openFromReceivable($receivable, ['assigned_to' => $collector->id], $this->user);
        $service->addFollowup($case, [
            'followup_type' => 'phone',
            'followup_date' => today()->toDateString(),
            'next_followup_date' => today()->addDays(7)->toDateString(),
            'contact_channel' => 'phone',
            'summary' => 'Called payer',
            'outcome' => 'payment_promised',
        ], $this->user);
        $service->addPromise($case->fresh(), [
            'promised_by' => 'Payer',
            'promise_date' => today()->toDateString(),
            'expected_payment_date' => today()->addDays(5)->toDateString(),
            'promised_amount' => 200,
        ], $this->user);
        $service->addDispute($case->fresh(), [
            'source_type' => InvoiceReceivable::class,
            'source_id' => $receivable->id,
            'dispute_reason' => 'Payer disputes item',
            'disputed_amount' => 50,
        ], $this->user);
        $service->generateDunningNotice($case->fresh(), [
            'notice_level' => 'friendly_reminder',
            'notice_date' => today()->toDateString(),
        ], $this->user);

        $this->assertSame($collector->id, $case->fresh()->assigned_to);
        $this->assertEqualsWithDelta(500, $receivable->fresh()->balance, 0.001);
        $this->assertDatabaseHas('receivable_followups', ['receivable_case_id' => $case->id, 'outcome' => 'payment_promised']);
        $this->assertDatabaseHas('receivable_promises', ['receivable_case_id' => $case->id, 'promised_amount' => 200]);
        $this->assertDatabaseHas('receivable_disputes', ['receivable_case_id' => $case->id, 'disputed_amount' => 50]);
        $this->assertDatabaseHas('receivable_dunning_notices', ['receivable_case_id' => $case->id, 'status' => 'generated']);
    }

    public function test_statement_and_recommendations_are_snapshots_not_postings(): void
    {
        $receivable = $this->receivable(750, 'patient', today()->subDays(5));
        $case = app(ReceivableCaseService::class)->openFromReceivable($receivable, [], $this->user);

        $statement = app(ReceivableStatementService::class)->generate(
            'patient',
            $receivable->payer_id,
            today()->subMonth()->toDateString(),
            today()->toDateString(),
            $this->user
        );
        app(ReceivableRecommendationService::class)->recommendWriteoff($case, [
            'source_type' => InvoiceReceivable::class,
            'source_id' => $receivable->id,
            'recommended_amount' => 100,
            'reason' => 'Old balance review',
        ], $this->user);

        $this->assertInstanceOf(ReceivableStatementRun::class, $statement);
        $this->assertEqualsWithDelta(750, $statement->closing_balance, 0.001);
        $this->assertEqualsWithDelta(750, $receivable->fresh()->balance, 0.001);
        $this->assertDatabaseHas('receivable_writeoff_recommendations', [
            'receivable_case_id' => $case->id,
            'recommended_amount' => 100,
            'status' => 'recommended',
        ]);
    }

    private function receivable(float $amount, string $payerType, $dueDate): InvoiceReceivable
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);
        $visit = Visit::factory()->create(['patient_id' => $patient->id, 'created_by' => $this->user->id]);
        $invoice = Invoice::create([
            'invoice_number' => 'INV-PJ-'.uniqid(),
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'billing_type' => $payerType === 'patient' ? 'cash' : $payerType,
            'subtotal' => $amount,
            'total_amount' => $amount,
            'balance' => $amount,
            'status' => 'pending',
            'due_date' => $dueDate,
            'created_by' => $this->user->id,
        ]);

        return InvoiceReceivable::create([
            'invoice_id' => $invoice->id,
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
            'payer_type' => $payerType,
            'payer_id' => $payerType === 'patient' ? $patient->id : null,
            'original_amount' => $amount,
            'allocated_amount' => $amount,
            'balance' => $amount,
            'aging_start_date' => $dueDate,
            'due_date' => $dueDate,
            'status' => 'pending',
            'created_by' => $this->user->id,
        ]);
    }
}
