<?php

namespace App\Services;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Exceptions\AccountingAccountMappingNotFoundException;
use App\Models\AccountingAccountMapping;
use App\Models\User;
use Carbon\Carbon;

class AccountingAccountMappingService
{
    public function resolve(
        string $scope,
        string $key,
        string $value,
        Carbon|string|null $date = null,
        ?int $facilityId = null,
        ?int $departmentId = null,
        ?int $branchId = null,
        ?string $currency = null,
    ): array {
        $date = Carbon::parse($date ?? now())->toDateString();

        $candidates = AccountingAccountMapping::query()
            ->with('account')
            ->where('mapping_scope', $scope)
            ->where('mapping_key', $key)
            ->where('mapping_value', $value)
            ->activeOn($date)
            ->where(fn ($q) => $q->whereNull('facility_id')->when($facilityId, fn ($q) => $q->orWhere('facility_id', $facilityId)))
            ->where(fn ($q) => $q->whereNull('department_id')->when($departmentId, fn ($q) => $q->orWhere('department_id', $departmentId)))
            ->where(fn ($q) => $q->whereNull('branch_id')->when($branchId, fn ($q) => $q->orWhere('branch_id', $branchId)))
            ->where(fn ($q) => $q->whereNull('currency')->when($currency, fn ($q) => $q->orWhere('currency', strtoupper($currency))))
            ->get();

        $ranked = $candidates
            ->map(fn (AccountingAccountMapping $mapping) => [
                'mapping' => $mapping,
                'specificity' => $this->specificity($mapping, $facilityId, $departmentId, $branchId, $currency),
            ])
            ->sort(function (array $left, array $right) {
                return [$right['mapping']->priority, $right['specificity'], $right['mapping']->id]
                    <=> [$left['mapping']->priority, $left['specificity'], $left['mapping']->id];
            })
            ->values();

        if ($ranked->isEmpty()) {
            throw new AccountingAccountMappingNotFoundException(
                "No active accounting mapping exists for {$scope}/{$key}/{$value} on {$date}."
            );
        }

        $winner = $ranked->first();
        $conflicts = $ranked->filter(fn (array $row) => $row['mapping']->priority === $winner['mapping']->priority
            && $row['specificity'] === $winner['specificity']);

        if ($conflicts->count() > 1) {
            throw new AccountingAccountMappingNotFoundException(
                "Conflicting accounting mappings exist for {$scope}/{$key}/{$value} on {$date}."
            );
        }

        if (! $winner['mapping']->account || ! $winner['mapping']->account->is_active) {
            throw new AccountingAccountMappingNotFoundException(
                "The matched accounting mapping for {$scope}/{$key}/{$value} points to an inactive or missing account."
            );
        }

        return [
            'account' => $winner['mapping']->account,
            'mapping' => $winner['mapping'],
            'explanation' => sprintf(
                'Matched mapping #%d at priority %d with specificity %d for %s.',
                $winner['mapping']->id,
                $winner['mapping']->priority,
                $winner['specificity'],
                $date,
            ),
        ];
    }

    public function create(array $data, User $actor): AccountingAccountMapping
    {
        $mapping = AccountingAccountMapping::create(array_merge($data, [
            'currency' => empty($data['currency']) ? null : strtoupper($data['currency']),
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]));
        $this->audit($mapping, 'ACCOUNTING_ACCOUNT_MAPPING_CREATED', $actor);

        return $mapping;
    }

    public function update(AccountingAccountMapping $mapping, array $data, User $actor): AccountingAccountMapping
    {
        $old = $mapping->getAttributes();
        $mapping->update(array_merge($data, [
            'currency' => empty($data['currency']) ? null : strtoupper($data['currency']),
            'updated_by' => $actor->id,
        ]));
        $this->audit($mapping, 'ACCOUNTING_ACCOUNT_MAPPING_UPDATED', $actor, $old);

        return $mapping->refresh();
    }

    public function disable(AccountingAccountMapping $mapping, User $actor): AccountingAccountMapping
    {
        if (! $mapping->is_active) {
            return $mapping;
        }

        $old = $mapping->getAttributes();
        $mapping->update(['is_active' => false, 'updated_by' => $actor->id]);
        $this->audit($mapping, 'ACCOUNTING_ACCOUNT_MAPPING_DISABLED', $actor, $old);

        return $mapping->refresh();
    }

    protected function specificity(
        AccountingAccountMapping $mapping,
        ?int $facilityId,
        ?int $departmentId,
        ?int $branchId,
        ?string $currency,
    ): int {
        return collect([
            $mapping->facility_id !== null && $mapping->facility_id === $facilityId,
            $mapping->department_id !== null && $mapping->department_id === $departmentId,
            $mapping->branch_id !== null && $mapping->branch_id === $branchId,
            $mapping->currency !== null && $mapping->currency === strtoupper((string) $currency),
        ])->filter()->count();
    }

    protected function audit(
        AccountingAccountMapping $mapping,
        string $event,
        User $actor,
        array $old = [],
    ): void {
        app(ActivityLogService::class)->log(LogModule::ACCOUNTING, $event, [
            'severity' => LogSeverity::NOTICE,
            'account_id' => $mapping->account_id,
            'causer' => $actor,
            'old_values' => $old,
            'new_values' => $mapping->getAttributes(),
            'metadata' => ['accounting_account_mapping_id' => $mapping->id],
        ], $mapping, str_replace('_', ' ', ucfirst(strtolower($event))));
    }
}
