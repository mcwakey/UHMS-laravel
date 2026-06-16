<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\AssetCategory;
use App\Models\AssetDepreciationRun;
use App\Models\AssetLocation;
use App\Models\FixedAsset;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FixedAssetService
{
    public function __construct(protected JournalEntryService $journals) {}

    public function createAsset(array $data, User $user): FixedAsset
    {
        $category = AssetCategory::findOrFail($data['asset_category_id']);
        $cost = round((float) $data['cost'], 2);
        $residual = array_key_exists('residual_value', $data)
            ? round((float) $data['residual_value'], 2)
            : round($cost * ((float) $category->default_residual_rate / 100), 2);

        return FixedAsset::create([
            'asset_number' => $data['asset_number'] ?? $this->nextAssetNumber(),
            'asset_category_id' => $category->id,
            'asset_location_id' => $data['asset_location_id'] ?? null,
            'custodian_id' => $data['custodian_id'] ?? null,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'acquisition_date' => $data['acquisition_date'],
            'placed_in_service_date' => $data['placed_in_service_date'] ?? null,
            'cost' => $cost,
            'residual_value' => $residual,
            'useful_life_months' => $data['useful_life_months'] ?? $category->useful_life_months,
            'depreciation_method' => $data['depreciation_method'] ?? $category->depreciation_method,
            'status' => FixedAsset::STATUS_DRAFT,
            'created_by' => $user->id,
        ]);
    }

    public function capitalize(FixedAsset $asset, Account $creditAccount, User $user, ?string $date = null): FixedAsset
    {
        if ($asset->status !== FixedAsset::STATUS_DRAFT) {
            throw ValidationException::withMessages(['asset' => 'Only draft assets can be capitalized.']);
        }

        $asset->load('category');
        $date ??= $asset->placed_in_service_date?->toDateString() ?: $asset->acquisition_date->toDateString();

        return DB::transaction(function () use ($asset, $creditAccount, $user, $date) {
            $journal = $this->journals->createDraft([
                'entry_date' => $date,
                'description' => 'Fixed asset capitalization: '.$asset->asset_number.' '.$asset->name,
                'source_module' => 'FIXED_ASSET',
                'reference_type' => FixedAsset::class,
                'reference_id' => $asset->id,
                'lines' => [
                    [
                        'account_id' => $asset->category->asset_cost_account_id,
                        'debit' => (float) $asset->cost,
                        'credit' => 0,
                        'description' => 'Capitalize fixed asset cost',
                    ],
                    [
                        'account_id' => $creditAccount->id,
                        'debit' => 0,
                        'credit' => (float) $asset->cost,
                        'description' => 'Asset acquisition funding / clearing',
                    ],
                ],
            ]);
            $journal = $this->journals->post($journal, $user);

            $asset->update([
                'status' => FixedAsset::STATUS_ACTIVE,
                'placed_in_service_date' => $date,
                'capitalization_journal_entry_id' => $journal->id,
                'capitalized_at' => now(),
                'capitalized_by' => $user->id,
            ]);
            $asset->assetAcquisitions()->create([
                'source_type' => FixedAsset::class,
                'source_id' => $asset->id,
                'source_reference' => $asset->asset_number,
                'amount' => $asset->cost,
                'journal_entry_id' => $journal->id,
            ]);

            return $asset->refresh();
        });
    }

    public function runDepreciation(AccountingPeriod $period, User $user): AssetDepreciationRun
    {
        if (AssetDepreciationRun::where('accounting_period_id', $period->id)->exists()) {
            throw ValidationException::withMessages(['period' => 'Depreciation has already been run for this accounting period.']);
        }

        return DB::transaction(function () use ($period, $user) {
            $run = AssetDepreciationRun::create([
                'accounting_period_id' => $period->id,
                'run_number' => $this->nextRunNumber(),
                'period_start' => $period->start_date,
                'period_end' => $period->end_date,
                'status' => 'draft',
                'created_by' => $user->id,
            ]);

            $assets = FixedAsset::with('category')
                ->where('status', FixedAsset::STATUS_ACTIVE)
                ->whereDate('placed_in_service_date', '<=', $period->end_date)
                ->get();

            $journalLines = [];
            $total = 0.0;
            foreach ($assets as $asset) {
                $amount = $this->periodDepreciation($asset);
                if ($amount <= 0) {
                    continue;
                }

                $accumulatedAfter = round((float) $asset->accumulated_depreciation + $amount, 2);
                $run->lines()->create([
                    'fixed_asset_id' => $asset->id,
                    'depreciable_amount' => max(0, (float) $asset->cost - (float) $asset->residual_value),
                    'depreciation_amount' => $amount,
                    'accumulated_after' => $accumulatedAfter,
                ]);
                $asset->update(['accumulated_depreciation' => $accumulatedAfter]);
                $total = round($total + $amount, 2);

                $journalLines[] = [
                    'account_id' => $asset->category->depreciation_expense_account_id,
                    'debit' => $amount,
                    'credit' => 0,
                    'description' => 'Depreciation: '.$asset->asset_number,
                ];
                $journalLines[] = [
                    'account_id' => $asset->category->accumulated_depreciation_account_id,
                    'debit' => 0,
                    'credit' => $amount,
                    'description' => 'Accumulated depreciation: '.$asset->asset_number,
                ];
            }

            $journal = null;
            if ($total > 0) {
                $journal = $this->journals->createDraft([
                    'entry_date' => $period->end_date->toDateString(),
                    'description' => 'Fixed asset depreciation: '.$period->name,
                    'source_module' => 'FIXED_ASSET_DEPRECIATION',
                    'reference_type' => AssetDepreciationRun::class,
                    'reference_id' => $run->id,
                    'lines' => $journalLines,
                ]);
                $journal = $this->journals->post($journal, $user);
            }

            $run->update([
                'status' => 'posted',
                'total_depreciation' => $total,
                'journal_entry_id' => $journal?->id,
            ]);

            return $run->refresh()->load('lines.asset', 'journalEntry');
        });
    }

    public function transfer(FixedAsset $asset, ?AssetLocation $location, ?User $custodian, User $actor, ?string $date = null, ?string $reason = null): FixedAsset
    {
        $asset->assetTransfers()->create([
            'from_location_id' => $asset->asset_location_id,
            'to_location_id' => $location?->id,
            'from_custodian_id' => $asset->custodian_id,
            'to_custodian_id' => $custodian?->id,
            'transfer_date' => $date ?? today()->toDateString(),
            'reason' => $reason,
            'created_by' => $actor->id,
        ]);

        $asset->update(['asset_location_id' => $location?->id, 'custodian_id' => $custodian?->id]);

        return $asset->refresh();
    }

    public function verify(FixedAsset $asset, array $data, User $user): void
    {
        $asset->assetVerifications()->create([
            'verification_date' => $data['verification_date'] ?? today()->toDateString(),
            'condition_status' => $data['condition_status'] ?? 'good',
            'notes' => $data['notes'] ?? null,
            'verified_by' => $user->id,
        ]);
    }

    public function dispose(FixedAsset $asset, array $data, User $user): FixedAsset
    {
        if ($asset->status !== FixedAsset::STATUS_ACTIVE) {
            throw ValidationException::withMessages(['asset' => 'Only active assets can be disposed.']);
        }

        $asset->load('category');
        $proceeds = round((float) ($data['proceeds_amount'] ?? 0), 2);
        $carrying = $asset->carrying_amount;
        $gain = max(0, round($proceeds - $carrying, 2));
        $loss = max(0, round($carrying - $proceeds, 2));
        $date = $data['disposal_date'] ?? today()->toDateString();

        return DB::transaction(function () use ($asset, $data, $user, $proceeds, $carrying, $gain, $loss, $date) {
            $lines = [];
            if ($proceeds > 0) {
                $lines[] = [
                    'account_id' => $data['proceeds_account_id'],
                    'debit' => $proceeds,
                    'credit' => 0,
                    'description' => 'Fixed asset disposal proceeds',
                ];
            }
            if ((float) $asset->accumulated_depreciation > 0) {
                $lines[] = [
                    'account_id' => $asset->category->accumulated_depreciation_account_id,
                    'debit' => (float) $asset->accumulated_depreciation,
                    'credit' => 0,
                    'description' => 'Remove accumulated depreciation',
                ];
            }
            if ($loss > 0) {
                $lines[] = [
                    'account_id' => $asset->category->disposal_loss_account_id,
                    'debit' => $loss,
                    'credit' => 0,
                    'description' => 'Loss on fixed asset disposal',
                ];
            }
            $lines[] = [
                'account_id' => $asset->category->asset_cost_account_id,
                'debit' => 0,
                'credit' => (float) $asset->cost,
                'description' => 'Remove fixed asset cost',
            ];
            if ($gain > 0) {
                $lines[] = [
                    'account_id' => $asset->category->disposal_gain_account_id,
                    'debit' => 0,
                    'credit' => $gain,
                    'description' => 'Gain on fixed asset disposal',
                ];
            }

            $journal = $this->journals->createDraft([
                'entry_date' => $date,
                'description' => 'Fixed asset disposal: '.$asset->asset_number.' '.$asset->name,
                'source_module' => 'FIXED_ASSET_DISPOSAL',
                'reference_type' => FixedAsset::class,
                'reference_id' => $asset->id,
                'lines' => $lines,
            ]);
            $journal = $this->journals->post($journal, $user);

            $asset->disposals()->create([
                'disposal_date' => $date,
                'proceeds_amount' => $proceeds,
                'carrying_amount' => $carrying,
                'gain_amount' => $gain,
                'loss_amount' => $loss,
                'proceeds_account_id' => $data['proceeds_account_id'] ?? null,
                'journal_entry_id' => $journal->id,
                'reason' => $data['reason'] ?? null,
                'created_by' => $user->id,
            ]);
            $asset->update(['status' => FixedAsset::STATUS_DISPOSED, 'disposed_at' => $date]);

            return $asset->refresh();
        });
    }

    private function periodDepreciation(FixedAsset $asset): float
    {
        if ($asset->depreciation_method !== 'straight_line') {
            throw ValidationException::withMessages(['method' => 'Only straight-line depreciation is enabled in this phase.']);
        }

        $depreciable = max(0, (float) $asset->cost - (float) $asset->residual_value);
        $remaining = max(0, $depreciable - (float) $asset->accumulated_depreciation);
        $monthly = round($depreciable / max(1, (int) $asset->useful_life_months), 2);

        return round(min($monthly, $remaining), 2);
    }

    private function nextAssetNumber(): string
    {
        $last = FixedAsset::where('asset_number', 'like', 'FA-%')->orderByDesc('asset_number')->value('asset_number');
        $next = $last ? ((int) substr($last, -6)) + 1 : 1;

        return 'FA-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    private function nextRunNumber(): string
    {
        $last = AssetDepreciationRun::where('run_number', 'like', 'DEP-%')->orderByDesc('run_number')->value('run_number');
        $next = $last ? ((int) substr($last, -6)) + 1 : 1;

        return 'DEP-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
