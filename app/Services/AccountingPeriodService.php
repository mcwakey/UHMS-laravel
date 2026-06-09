<?php

namespace App\Services;

use App\Enums\Accounting\PeriodStatus;
use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\AccountingPeriod;
use App\Models\FiscalYear;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class AccountingPeriodService
{
    public function resolveOpenPeriodForDate(Carbon|string $date): AccountingPeriod
    {
        $date = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();

        $period = AccountingPeriod::query()
            ->with('fiscalYear')
            ->where('status', PeriodStatus::OPEN->value)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->whereHas('fiscalYear', fn ($query) => $query->where('status', PeriodStatus::OPEN->value))
            ->orderBy('start_date')
            ->first();

        if (! $period) {
            throw ValidationException::withMessages([
                'entry_date' => 'No open accounting period exists for ' . Carbon::parse($date)->format('d M Y') . '.',
            ]);
        }

        return $period;
    }

    public function ensureDateIsPostable(Carbon|string $date): AccountingPeriod
    {
        return $this->resolveOpenPeriodForDate($date);
    }

    public function closePeriod(AccountingPeriod $period, User $user): AccountingPeriod
    {
        if ($period->status === PeriodStatus::CLOSED) {
            return $period;
        }

        $old = $period->getOriginal();
        $period->update([
            'status' => PeriodStatus::CLOSED,
            'closed_by' => $user->id,
            'closed_at' => now(),
        ]);

        app(ActivityLogService::class)->log(LogModule::ACCOUNTING, 'ACCOUNTING_PERIOD_CLOSED', [
            'severity' => LogSeverity::WARNING,
            'accounting_period_id' => $period->id,
            'fiscal_year_id' => $period->fiscal_year_id,
            'old_values' => $old,
            'new_values' => $period->getAttributes(),
        ], $period, 'Accounting period closed: ' . $period->name);

        return $period;
    }

    public function createFiscalYear(array $data, User $user): FiscalYear
    {
        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);

        if ($end->lt($start)) {
            throw ValidationException::withMessages(['end_date' => 'Fiscal year end date must be after the start date.']);
        }

        $year = FiscalYear::create([
            'name' => $data['name'],
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'status' => PeriodStatus::OPEN,
            'created_by' => $user->id,
        ]);

        app(ActivityLogService::class)->log(LogModule::ACCOUNTING, 'FISCAL_YEAR_CREATED', [
            'severity' => LogSeverity::NOTICE,
            'fiscal_year_id' => $year->id,
            'new_values' => $year->getAttributes(),
        ], $year, 'Fiscal year created: ' . $year->name);

        return $year;
    }

    public function closeFiscalYear(FiscalYear $year, User $user): FiscalYear
    {
        if ($year->periods()->where('status', PeriodStatus::OPEN->value)->exists()) {
            throw ValidationException::withMessages([
                'fiscal_year' => 'Close all accounting periods before closing the fiscal year.',
            ]);
        }

        $old = $year->getOriginal();
        $year->update([
            'status' => PeriodStatus::CLOSED,
            'closed_by' => $user->id,
            'closed_at' => now(),
        ]);

        app(ActivityLogService::class)->log(LogModule::ACCOUNTING, 'FISCAL_YEAR_CLOSED', [
            'severity' => LogSeverity::WARNING,
            'fiscal_year_id' => $year->id,
            'old_values' => $old,
            'new_values' => $year->getAttributes(),
        ], $year, 'Fiscal year closed: ' . $year->name);

        return $year;
    }

    public function createPeriod(array $data, User $user): AccountingPeriod
    {
        $year = FiscalYear::findOrFail($data['fiscal_year_id']);
        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);

        if ($end->lt($start)) {
            throw ValidationException::withMessages(['end_date' => 'Period end date must be after the start date.']);
        }

        if ($start->lt($year->start_date) || $end->gt($year->end_date)) {
            throw ValidationException::withMessages([
                'start_date' => 'The period date range must sit inside the selected fiscal year.',
            ]);
        }

        $period = AccountingPeriod::create([
            'fiscal_year_id' => $year->id,
            'name' => $data['name'],
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'status' => PeriodStatus::OPEN,
            'created_by' => $user->id,
        ]);

        app(ActivityLogService::class)->log(LogModule::ACCOUNTING, 'ACCOUNTING_PERIOD_CREATED', [
            'severity' => LogSeverity::NOTICE,
            'accounting_period_id' => $period->id,
            'fiscal_year_id' => $period->fiscal_year_id,
            'new_values' => $period->getAttributes(),
        ], $period, 'Accounting period created: ' . $period->name);

        return $period;
    }
}
