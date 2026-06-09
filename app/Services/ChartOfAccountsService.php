<?php

namespace App\Services;

use App\Enums\Accounting\AccountType;
use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\Account;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ChartOfAccountsService
{
    public function create(array $data, User $user): Account
    {
        $type = AccountType::from($data['type']);
        $account = Account::create($this->payload($data, $type, $user));

        app(ActivityLogService::class)->log(LogModule::ACCOUNTING, 'ACCOUNT_CREATED', [
            'severity' => LogSeverity::NOTICE,
            'account_id' => $account->id,
            'new_values' => $account->getAttributes(),
        ], $account, 'Account created: ' . $account->display_name);

        return $account;
    }

    public function update(Account $account, array $data, User $user): Account
    {
        if (($data['parent_id'] ?? null) && (int) $data['parent_id'] === $account->id) {
            throw ValidationException::withMessages(['parent_id' => 'An account cannot be its own parent.']);
        }

        $old = $account->getOriginal();
        $type = AccountType::from($data['type']);
        $account->update($this->payload($data, $type, $user, updating: true));

        app(ActivityLogService::class)->log(LogModule::ACCOUNTING, 'ACCOUNT_UPDATED', [
            'severity' => LogSeverity::NOTICE,
            'account_id' => $account->id,
            'old_values' => $old,
            'new_values' => $account->getAttributes(),
        ], $account, 'Account updated: ' . $account->display_name);

        return $account;
    }

    public function disable(Account $account, User $user): Account
    {
        $old = $account->getOriginal();
        $account->update(['is_active' => false, 'updated_by' => $user->id]);

        app(ActivityLogService::class)->log(LogModule::ACCOUNTING, 'ACCOUNT_DISABLED', [
            'severity' => LogSeverity::WARNING,
            'account_id' => $account->id,
            'old_values' => $old,
            'new_values' => $account->getAttributes(),
        ], $account, 'Account disabled: ' . $account->display_name);

        return $account;
    }

    public function activate(Account $account, User $user): Account
    {
        $old = $account->getOriginal();
        $account->update(['is_active' => true, 'updated_by' => $user->id]);

        app(ActivityLogService::class)->log(LogModule::ACCOUNTING, 'ACCOUNT_REACTIVATED', [
            'severity' => LogSeverity::NOTICE,
            'account_id' => $account->id,
            'old_values' => $old,
            'new_values' => $account->getAttributes(),
        ], $account, 'Account reactivated: ' . $account->display_name);

        return $account;
    }

    private function payload(array $data, AccountType $type, User $user, bool $updating = false): array
    {
        $normalBalance = $data['normal_balance'] ?? $type->normalBalance()->value;

        if ($normalBalance !== $type->normalBalance()->value) {
            throw ValidationException::withMessages([
                'normal_balance' => $type->label() . ' accounts must have a ' . $type->normalBalance()->label() . ' normal balance.',
            ]);
        }

        return [
            'code' => $data['code'],
            'name' => $data['name'],
            'type' => $type,
            'subtype' => $data['subtype'] ?? null,
            'parent_id' => $data['parent_id'] ?? null,
            'description' => $data['description'] ?? null,
            'is_cash_account' => (bool) ($data['is_cash_account'] ?? false),
            'is_bank_account' => (bool) ($data['is_bank_account'] ?? false),
            'is_control_account' => (bool) ($data['is_control_account'] ?? false),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'opening_balance' => $data['opening_balance'] ?? 0,
            'normal_balance' => $normalBalance,
            $updating ? 'updated_by' : 'created_by' => $user->id,
        ];
    }
}
