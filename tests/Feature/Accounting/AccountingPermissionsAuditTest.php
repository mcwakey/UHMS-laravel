<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\AccountingPeriodService;
use App\Services\JournalEntryService;
use Database\Seeders\AccountingChartSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Backend permission enforcement for accounting actions. We hit real routes —
 * not hidden buttons — so the `can:` middleware is what's being verified.
 */
class AccountingPermissionsAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(AccountingChartSeeder::class);
    }

    private function userWith(array $permissions = []): User
    {
        $role = Role::findOrCreate('AcctRole-'.uniqid(), 'web');
        foreach ($permissions as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function accountId(string $code): int
    {
        return Account::where('code', $code)->value('id');
    }

    private function postedJournal(User $actor): JournalEntry
    {
        $this->actingAs($actor);
        $entry = app(JournalEntryService::class)->createDraft([
            'entry_date' => today()->toDateString(), 'description' => 'Perm fixture',
            'lines' => [
                ['account_id' => $this->accountId('5300'), 'debit' => 100, 'credit' => 0],
                ['account_id' => $this->accountId('1110'), 'debit' => 0, 'credit' => 100],
            ],
        ]);

        return app(JournalEntryService::class)->post($entry, $actor);
    }

    public function test_unauthorized_user_cannot_create_accounts(): void
    {
        $this->actingAs($this->userWith())
            ->post(route('admin.accounting.accounts.store'), [
                'code' => '9999', 'name' => 'Hacked', 'type' => 'ASSET', 'normal_balance' => 'debit',
            ])->assertForbidden();
    }

    public function test_authorized_user_can_reach_account_creation(): void
    {
        $response = $this->actingAs($this->userWith(['accounting.accounts.view', 'accounting.accounts.create']))
            ->get(route('admin.accounting.accounts.create'));

        $this->assertNotSame(403, $response->getStatusCode());
    }

    public function test_unauthorized_user_cannot_view_trial_balance(): void
    {
        $this->actingAs($this->userWith())
            ->get(route('admin.accounting.trial-balance'))
            ->assertForbidden();
    }

    public function test_unauthorized_user_cannot_post_journal(): void
    {
        $admin = $this->userWith(['accounting.journals.view', 'accounting.journals.create', 'accounting.journals.post']);
        $entry = app(JournalEntryService::class);
        $this->actingAs($admin);
        $draft = $entry->createDraft([
            'entry_date' => today()->toDateString(), 'description' => 'Draft',
            'lines' => [
                ['account_id' => $this->accountId('5300'), 'debit' => 10, 'credit' => 0],
                ['account_id' => $this->accountId('1110'), 'debit' => 0, 'credit' => 10],
            ],
        ]);

        $this->actingAs($this->userWith())
            ->post(route('admin.accounting.journals.post', $draft))
            ->assertForbidden();
    }

    public function test_unauthorized_user_cannot_reverse_journal(): void
    {
        $admin = $this->userWith(['accounting.journals.view', 'accounting.journals.create', 'accounting.journals.post', 'accounting.journals.reverse']);
        $posted = $this->postedJournal($admin);

        $this->actingAs($this->userWith())
            ->post(route('admin.accounting.journals.reverse', $posted), ['reason' => 'x'])
            ->assertForbidden();
    }

    public function test_unauthorized_user_cannot_close_or_reopen_period(): void
    {
        $period = AccountingPeriod::first();

        $this->actingAs($this->userWith())
            ->patch(route('admin.accounting.periods.close', $period))
            ->assertForbidden();

        $this->actingAs($this->userWith())
            ->patch(route('admin.accounting.periods.reopen', $period), ['reason' => 'x'])
            ->assertForbidden();
    }

    public function test_unauthorized_user_cannot_reopen_or_year_end_close_fiscal_year(): void
    {
        $year = FiscalYear::first();

        $this->actingAs($this->userWith())
            ->patch(route('admin.accounting.fiscal-years.reopen', $year), ['reason' => 'x'])
            ->assertForbidden();

        $this->actingAs($this->userWith())
            ->post(route('admin.accounting.fiscal-years.year-end-close', $year))
            ->assertForbidden();
    }

    public function test_authorized_user_can_reopen_period_via_route(): void
    {
        $admin = $this->userWith(['accounting.periods.view', 'accounting.periods.manage', 'accounting.periods.reopen']);
        $period = AccountingPeriod::whereDate('start_date', '<=', today())->whereDate('end_date', '>=', today())->first();
        app(AccountingPeriodService::class)->closePeriod($period, $admin);

        $this->actingAs($admin)
            ->patch(route('admin.accounting.periods.reopen', $period), ['reason' => 'Late adjustment'])
            ->assertRedirect();

        $this->assertSame('open', $period->fresh()->status->value);
    }

    public function test_reopen_period_route_requires_reason(): void
    {
        $admin = $this->userWith(['accounting.periods.view', 'accounting.periods.manage', 'accounting.periods.reopen']);
        $period = AccountingPeriod::first();
        app(AccountingPeriodService::class)->closePeriod($period, $admin);

        $this->actingAs($admin)
            ->patch(route('admin.accounting.periods.reopen', $period), ['reason' => ''])
            ->assertSessionHasErrors('reason');
    }

    public function test_unauthorized_user_cannot_retry_failed_posting(): void
    {
        $this->actingAs($this->userWith())
            ->post(route('admin.accounting.postings.retry'), ['source_type' => 'invoice', 'source_id' => 1])
            ->assertForbidden();
    }
}
