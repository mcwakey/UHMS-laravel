<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\AccountingSetting;
use App\Models\FiscalYear;
use App\Models\PayrollRun;
use App\Models\PayrollStatutorySettlement;
use App\Models\TaxAccountMapping;
use App\Models\TaxLedgerEntry;
use App\Models\TaxType;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TaxLedgerService
{
    public function ensureDefaults(): void
    {
        $defaults = [
            ['PAYE', 'PAYE', 'liability', 'Ghana Revenue Authority', 'paye_payable_account_id', null, '2310', null],
            ['PENSION', 'Pension / SSNIT', 'liability', 'SSNIT', 'pension_payable_account_id', null, '2320', null],
            ['WHT', 'Withholding Tax', 'liability', 'Ghana Revenue Authority', 'withholding_tax_payable_account_id', null, '2330', null],
            ['OUTPUT_VAT', 'Output VAT / Levy', 'liability', 'Ghana Revenue Authority', 'output_tax_payable_account_id', null, '2340', null],
            ['INPUT_VAT', 'Input VAT / Levy', 'receivable', 'Ghana Revenue Authority', null, 'input_tax_receivable_account_id', null, '1250'],
        ];

        foreach ($defaults as [$code, $name, $category, $authority, $payableKey, $receivableKey, $payableCode, $receivableCode]) {
            $type = TaxType::firstOrCreate(
                ['code' => $code],
                ['name' => $name, 'category' => $category, 'authority_name' => $authority, 'return_frequency' => 'monthly']
            );
            TaxAccountMapping::firstOrCreate(
                ['tax_type_id' => $type->id, 'effective_from' => '2026-01-01'],
                [
                    'payable_account_id' => $payableKey ? $this->settingAccount($payableKey, $payableCode) : null,
                    'receivable_account_id' => $receivableKey ? $this->settingAccount($receivableKey, $receivableCode) : null,
                    'is_active' => true,
                ]
            );
        }
    }

    public function recordSourceEvent(string $taxCode, array $data): TaxLedgerEntry
    {
        $this->ensureDefaults();
        $taxType = TaxType::where('code', strtoupper($taxCode))->firstOrFail();
        $date = $data['entry_date'];
        $period = AccountingPeriod::query()
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();
        $fiscalYear = $period?->fiscalYear ?: FiscalYear::query()
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();

        return TaxLedgerEntry::updateOrCreate(
            [
                'tax_type_id' => $taxType->id,
                'source_type' => $data['source_type'],
                'source_id' => $data['source_id'] ?? null,
                'direction' => $data['direction'] ?? 'payable',
            ],
            [
                'fiscal_year_id' => $fiscalYear?->id,
                'accounting_period_id' => $period?->id,
                'entry_date' => $date,
                'source_reference' => $data['source_reference'] ?? null,
                'tax_base_amount' => round((float) ($data['tax_base_amount'] ?? 0), 2),
                'tax_amount' => round((float) $data['tax_amount'], 2),
                'remaining_amount' => round((float) $data['tax_amount'], 2),
                'status' => TaxLedgerEntry::STATUS_OPEN,
                'journal_entry_id' => $data['journal_entry_id'] ?? null,
                'metadata_snapshot' => isset($data['metadata']) ? json_encode($data['metadata']) : null,
            ]
        );
    }

    public function syncPayrollRun(PayrollRun $run): array
    {
        $records = $run->records()->get();
        $entries = [];
        $paye = round((float) $records->sum('tax'), 2);
        if ($paye > 0) {
            $entries[] = $this->recordSourceEvent('PAYE', [
                'entry_date' => $run->period_end?->toDateString() ?: today()->toDateString(),
                'source_type' => PayrollRun::class,
                'source_id' => $run->id,
                'source_reference' => $run->run_number ?? $run->pay_period,
                'direction' => 'payable',
                'tax_base_amount' => round((float) $records->sum('chargeable_income'), 2),
                'tax_amount' => $paye,
                'journal_entry_id' => $run->journal_entry_id,
                'metadata' => ['pay_period' => $run->pay_period],
            ]);
        }

        $pension = round((float) $records->sum('ssnit_employee') + (float) $records->sum('ssnit_employer'), 2);
        if ($pension > 0) {
            $entries[] = $this->recordSourceEvent('PENSION', [
                'entry_date' => $run->period_end?->toDateString() ?: today()->toDateString(),
                'source_type' => PayrollRun::class,
                'source_id' => $run->id,
                'source_reference' => $run->run_number ?? $run->pay_period,
                'direction' => 'payable',
                'tax_base_amount' => round((float) $records->sum('gross_pay'), 2),
                'tax_amount' => $pension,
                'journal_entry_id' => $run->journal_entry_id,
                'metadata' => ['pay_period' => $run->pay_period],
            ]);
        }

        return $entries;
    }

    public function allocateSettlement(PayrollStatutorySettlement $settlement, ?User $user = null): void
    {
        $code = $settlement->liability_type === PayrollStatutorySettlement::TYPE_PAYE ? 'PAYE' : 'PENSION';
        $this->applyPaymentToOpenLedger($code, (float) $settlement->amount, $user);
    }

    public function applyPaymentToOpenLedger(string $taxCode, float $amount, ?User $user = null): float
    {
        $this->ensureDefaults();
        $taxType = TaxType::where('code', strtoupper($taxCode))->firstOrFail();
        $remainingPayment = round($amount, 2);

        DB::transaction(function () use ($taxType, &$remainingPayment) {
            $entries = TaxLedgerEntry::query()
                ->where('tax_type_id', $taxType->id)
                ->where('direction', 'payable')
                ->where('remaining_amount', '>', 0)
                ->orderBy('entry_date')
                ->lockForUpdate()
                ->get();

            foreach ($entries as $entry) {
                if ($remainingPayment <= 0) {
                    break;
                }
                $allocation = min($remainingPayment, (float) $entry->remaining_amount);
                $newRemaining = round((float) $entry->remaining_amount - $allocation, 2);
                $entry->update([
                    'remaining_amount' => $newRemaining,
                    'status' => $newRemaining <= 0 ? TaxLedgerEntry::STATUS_SETTLED : TaxLedgerEntry::STATUS_OPEN,
                ]);
                $remainingPayment = round($remainingPayment - $allocation, 2);
            }
        });

        return $remainingPayment;
    }

    public function summary(?string $taxCode = null): array
    {
        $this->ensureDefaults();
        $rows = TaxType::query()
            ->when($taxCode, fn ($query) => $query->where('code', strtoupper($taxCode)))
            ->withSum('ledgerEntries as ledger_total', 'tax_amount')
            ->withSum('ledgerEntries as open_total', 'remaining_amount')
            ->orderBy('code')
            ->get()
            ->map(fn (TaxType $type) => [
                'tax_type' => $type,
                'ledger_total' => round((float) $type->ledger_total, 2),
                'open_total' => round((float) $type->open_total, 2),
            ]);

        return [
            'rows' => $rows,
            'totals' => [
                'ledger_total' => round($rows->sum('ledger_total'), 2),
                'open_total' => round($rows->sum('open_total'), 2),
            ],
        ];
    }

    private function settingAccount(string $key, ?string $fallbackCode = null): ?int
    {
        return AccountingSetting::where('key', $key)->value('account_id')
            ?: ($fallbackCode ? Account::where('code', $fallbackCode)->value('id') : null);
    }
}
