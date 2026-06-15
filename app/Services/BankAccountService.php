<?php

namespace App\Services;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\BankAccount;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Manages the bank account register. Stores a masked account number for display
 * and a hash for duplicate/reference checks; never persists the clear number.
 */
class BankAccountService
{
    public function create(array $data, User $actor): BankAccount
    {
        $payload = $this->normalise($data);
        $payload['created_by'] = $actor->id;
        $payload['updated_by'] = $actor->id;

        $account = BankAccount::create($payload);
        $this->audit($account, 'BANK_ACCOUNT_CREATED', $actor, [], $account->getAttributes());

        return $account;
    }

    public function update(BankAccount $account, array $data, User $actor): BankAccount
    {
        $old = $account->getAttributes();
        $payload = $this->normalise($data, $account);
        $payload['updated_by'] = $actor->id;

        $account->update($payload);
        $this->audit($account, 'BANK_ACCOUNT_UPDATED', $actor, $old, $account->getAttributes());

        return $account->refresh();
    }

    public function disable(BankAccount $account, User $actor): BankAccount
    {
        if (! $account->is_active) {
            return $account;
        }

        $old = $account->getAttributes();
        $account->update(['is_active' => false, 'updated_by' => $actor->id]);
        $this->audit($account, 'BANK_ACCOUNT_DISABLED', $actor, $old, $account->getAttributes(), LogSeverity::WARNING);

        return $account->refresh();
    }

    public function activate(BankAccount $account, User $actor): BankAccount
    {
        if ($account->is_active) {
            return $account;
        }

        $old = $account->getAttributes();
        $account->update(['is_active' => true, 'updated_by' => $actor->id]);
        $this->audit($account, 'BANK_ACCOUNT_UPDATED', $actor, $old, $account->getAttributes());

        return $account->refresh();
    }

    /**
     * Guard new imports against inactive accounts.
     */
    public function assertCanReceiveImports(BankAccount $account): void
    {
        if (! $account->is_active) {
            throw ValidationException::withMessages([
                'bank_account_id' => __('accounting.bank_account_inactive_no_import'),
            ]);
        }
    }

    protected function normalise(array $data, ?BankAccount $existing = null): array
    {
        $payload = collect($data)->only([
            'name', 'bank_name', 'branch_name', 'account_name',
            'currency', 'gl_account_id', 'opening_date', 'opening_balance',
            'is_active', 'notes',
        ])->toArray();

        if (! empty($data['currency'])) {
            $payload['currency'] = strtoupper($data['currency']);
        }

        // Only (re)derive the masked/hash values when a clear number is supplied.
        $clear = $data['account_number'] ?? null;
        if (! empty($clear)) {
            $payload['account_number_masked'] = $this->mask($clear);
            $payload['account_number_hash'] = $this->hash($clear);
        }

        return $payload;
    }

    public function mask(string $accountNumber): string
    {
        $digits = preg_replace('/\s+/', '', $accountNumber);
        $last4 = Str::substr($digits, -4);

        return str_repeat('•', max(0, Str::length($digits) - 4)) . $last4;
    }

    public function hash(string $accountNumber): string
    {
        return hash('sha256', preg_replace('/\s+/', '', $accountNumber));
    }

    protected function audit(
        BankAccount $account,
        string $event,
        User $actor,
        array $old = [],
        array $new = [],
        LogSeverity $severity = LogSeverity::NOTICE,
    ): void {
        app(ActivityLogService::class)->log(LogModule::ACCOUNTING, $event, [
            'severity' => $severity,
            'account_id' => $account->gl_account_id,
            'causer' => $actor,
            'old_values' => $old,
            'new_values' => $new,
            'metadata' => ['bank_account_id' => $account->id],
        ], $account, str_replace('_', ' ', ucfirst(strtolower($event))) . ': ' . $account->name);
    }
}
