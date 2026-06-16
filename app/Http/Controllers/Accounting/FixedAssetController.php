<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\AssetCategory;
use App\Models\AssetDepreciationRun;
use App\Models\AssetLocation;
use App\Models\FixedAsset;
use App\Services\FixedAssetService;
use Illuminate\Http\Request;

class FixedAssetController extends Controller
{
    public function index()
    {
        return view('accounting.fixed-assets.index', [
            'assets' => FixedAsset::with(['category', 'location', 'custodian'])->orderByDesc('created_at')->paginate(15),
            'categories' => AssetCategory::orderBy('name')->get(),
            'locations' => AssetLocation::orderBy('name')->get(),
            'accounts' => Account::active()->orderBy('code')->get(['id', 'code', 'name', 'type', 'subtype', 'is_cash_account', 'is_bank_account']),
            'periods' => AccountingPeriod::orderByDesc('start_date')->limit(18)->get(),
            'runs' => AssetDepreciationRun::with('journalEntry')->orderByDesc('period_end')->limit(10)->get(),
        ]);
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'code' => ['required', 'string', 'max:50', 'unique:asset_categories,code'],
            'asset_cost_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'accumulated_depreciation_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'depreciation_expense_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'disposal_gain_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'disposal_loss_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'useful_life_months' => ['required', 'integer', 'min:1'],
            'default_residual_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        AssetCategory::create($data + ['depreciation_method' => 'straight_line']);

        return back()->with('success', 'Asset category created.');
    }

    public function storeLocation(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'code' => ['required', 'string', 'max:50', 'unique:asset_locations,code'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        AssetLocation::create($data);

        return back()->with('success', 'Asset location created.');
    }

    public function store(Request $request, FixedAssetService $service)
    {
        $data = $request->validate([
            'asset_category_id' => ['required', 'integer', 'exists:asset_categories,id'],
            'asset_location_id' => ['nullable', 'integer', 'exists:asset_locations,id'],
            'name' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:1000'],
            'acquisition_date' => ['required', 'date'],
            'placed_in_service_date' => ['nullable', 'date'],
            'cost' => ['required', 'numeric', 'min:0.01'],
            'residual_value' => ['nullable', 'numeric', 'min:0'],
            'useful_life_months' => ['nullable', 'integer', 'min:1'],
        ]);

        $asset = $service->createAsset($data, $request->user());

        return back()->with('success', "Asset {$asset->asset_number} created.");
    }

    public function capitalize(Request $request, FixedAsset $asset, FixedAssetService $service)
    {
        $data = $request->validate([
            'credit_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'capitalization_date' => ['nullable', 'date'],
        ]);

        $service->capitalize($asset, Account::findOrFail($data['credit_account_id']), $request->user(), $data['capitalization_date'] ?? null);

        return back()->with('success', 'Asset capitalized.');
    }

    public function runDepreciation(Request $request, FixedAssetService $service)
    {
        $data = $request->validate(['accounting_period_id' => ['required', 'integer', 'exists:accounting_periods,id']]);
        $run = $service->runDepreciation(AccountingPeriod::findOrFail($data['accounting_period_id']), $request->user());

        return back()->with('success', "Depreciation run {$run->run_number} posted.");
    }

    public function dispose(Request $request, FixedAsset $asset, FixedAssetService $service)
    {
        $data = $request->validate([
            'disposal_date' => ['required', 'date'],
            'proceeds_amount' => ['nullable', 'numeric', 'min:0'],
            'proceeds_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $service->dispose($asset, $data, $request->user());

        return back()->with('success', 'Asset disposed.');
    }

    public function verify(Request $request, FixedAsset $asset, FixedAssetService $service)
    {
        $data = $request->validate([
            'verification_date' => ['required', 'date'],
            'condition_status' => ['required', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $service->verify($asset, $data, $request->user());

        return back()->with('success', 'Asset verification recorded.');
    }
}
