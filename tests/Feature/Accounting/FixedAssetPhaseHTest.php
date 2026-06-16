<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\AssetCategory;
use App\Models\AssetLocation;
use App\Models\FixedAsset;
use App\Models\User;
use App\Services\FixedAssetService;
use Database\Seeders\AccountingChartSeeder;
use Database\Seeders\ModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FixedAssetPhaseHTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private AssetCategory $category;
    private AccountingPeriod $period;

    private const PERMISSIONS = [
        'accounting.fixed_assets.view',
        'accounting.fixed_assets.manage',
        'accounting.fixed_assets.capitalize',
        'accounting.fixed_assets.depreciate',
        'accounting.fixed_assets.dispose',
        'accounting.fixed_assets.verify',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed([ModuleSeeder::class, AccountingChartSeeder::class]);

        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $this->user = User::factory()->create();
        $this->user->givePermissionTo(self::PERMISSIONS);
        $this->actingAs($this->user);
        $this->period = AccountingPeriod::orderBy('start_date')->firstOrFail();
        $this->category = AssetCategory::create([
            'name' => 'Medical Equipment',
            'code' => 'MED-EQ',
            'asset_cost_account_id' => $this->accountId('1410'),
            'accumulated_depreciation_account_id' => $this->accountId('1490'),
            'depreciation_expense_account_id' => $this->accountId('5850'),
            'disposal_gain_account_id' => $this->accountId('4980'),
            'disposal_loss_account_id' => $this->accountId('5860'),
            'useful_life_months' => 12,
            'default_residual_rate' => 0,
        ]);
    }

    private function accountId(string $code): int
    {
        return Account::where('code', $code)->value('id');
    }

    private function draftAsset(float $cost = 1200): FixedAsset
    {
        return app(FixedAssetService::class)->createAsset([
            'asset_category_id' => $this->category->id,
            'name' => 'Analyzer',
            'acquisition_date' => $this->period->start_date->toDateString(),
            'placed_in_service_date' => $this->period->start_date->toDateString(),
            'cost' => $cost,
            'useful_life_months' => 12,
        ], $this->user);
    }

    public function test_asset_capitalization_posts_fixed_asset_journal(): void
    {
        $asset = $this->draftAsset();

        $asset = app(FixedAssetService::class)->capitalize($asset, Account::where('code', '1120')->firstOrFail(), $this->user);

        $this->assertSame(FixedAsset::STATUS_ACTIVE, $asset->status);
        $this->assertNotNull($asset->capitalization_journal_entry_id);
        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $asset->capitalization_journal_entry_id,
            'account_id' => $this->accountId('1410'),
            'debit' => 1200,
            'credit' => 0,
        ]);
        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $asset->capitalization_journal_entry_id,
            'account_id' => $this->accountId('1120'),
            'debit' => 0,
            'credit' => 1200,
        ]);
    }

    public function test_depreciation_run_is_straight_line_and_idempotent_by_period(): void
    {
        $asset = app(FixedAssetService::class)->capitalize($this->draftAsset(), Account::where('code', '1120')->firstOrFail(), $this->user);

        $run = app(FixedAssetService::class)->runDepreciation($this->period, $this->user);

        $this->assertEqualsWithDelta(100, $run->total_depreciation, 0.001);
        $this->assertEqualsWithDelta(100, $asset->fresh()->accumulated_depreciation, 0.001);
        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $run->journal_entry_id,
            'account_id' => $this->accountId('5850'),
            'debit' => 100,
            'credit' => 0,
        ]);
        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $run->journal_entry_id,
            'account_id' => $this->accountId('1490'),
            'debit' => 0,
            'credit' => 100,
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(FixedAssetService::class)->runDepreciation($this->period, $this->user);
    }

    public function test_disposal_posts_loss_or_gain_and_marks_asset_disposed(): void
    {
        $asset = app(FixedAssetService::class)->capitalize($this->draftAsset(), Account::where('code', '1120')->firstOrFail(), $this->user);
        app(FixedAssetService::class)->runDepreciation($this->period, $this->user);

        $asset = app(FixedAssetService::class)->dispose($asset->fresh(), [
            'disposal_date' => $this->period->end_date->toDateString(),
            'proceeds_amount' => 900,
            'proceeds_account_id' => $this->accountId('1110'),
            'reason' => 'Sold old equipment',
        ], $this->user);

        $this->assertSame(FixedAsset::STATUS_DISPOSED, $asset->status);
        $disposal = $asset->disposals()->firstOrFail();
        $this->assertEqualsWithDelta(1100, $disposal->carrying_amount, 0.001);
        $this->assertEqualsWithDelta(200, $disposal->loss_amount, 0.001);
        $this->assertEqualsWithDelta(0, $disposal->gain_amount, 0.001);
        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $disposal->journal_entry_id,
            'account_id' => $this->accountId('5860'),
            'debit' => 200,
            'credit' => 0,
        ]);
    }

    public function test_fixed_asset_workbench_renders(): void
    {
        AssetLocation::create(['name' => 'Main Store', 'code' => 'MAIN']);

        $this->get(route('admin.accounting.fixed-assets.index'))
            ->assertOk()
            ->assertSee('Fixed Assets')
            ->assertSee('Register Asset');
    }
}
