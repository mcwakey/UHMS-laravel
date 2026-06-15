<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Parses a bank statement CSV into normalised rows. Configurable column mapping
 * and date format; supports separate debit/credit columns or a single signed
 * amount column. Returns rows plus per-row errors — it never persists anything.
 *
 * Mapping keys (all optional except a date and at least one amount source):
 *   columns: [
 *     transaction_date, value_date, reference, description,
 *     debit, credit, amount (signed), balance_after, external_transaction_id
 *   ]  (values are zero-based column indexes or header names)
 *   date_format: e.g. 'd/m/Y' (default 'Y-m-d')
 *   has_header: bool (default true)
 *   amount_sign: 'debit_positive' | 'credit_positive' (for signed amount column)
 */
class BankStatementCsvParser
{
    public function parse(string $contents, array $mapping = []): array
    {
        $columns = $mapping['columns'] ?? [];
        $dateFormat = $mapping['date_format'] ?? 'Y-m-d';
        $hasHeader = $mapping['has_header'] ?? true;
        $amountSign = $mapping['amount_sign'] ?? 'credit_positive';

        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents); // strip UTF-8 BOM
        $allRows = array_map('str_getcsv', preg_split('/\r\n|\r|\n/', trim($contents)));
        $allRows = array_values(array_filter($allRows, fn ($r) => $r !== null && $r !== [null] && implode('', array_map('strval', $r)) !== ''));

        $header = [];
        if ($hasHeader && ! empty($allRows)) {
            $header = array_map(fn ($h) => strtolower(trim((string) $h)), array_shift($allRows));
        }

        $resolve = function (array $row, $key) use ($columns, $header) {
            if (! array_key_exists($key, $columns) || $columns[$key] === null || $columns[$key] === '') {
                return null;
            }
            $col = $columns[$key];
            if (is_int($col) || ctype_digit((string) $col)) {
                return $row[(int) $col] ?? null;
            }
            $idx = array_search(strtolower((string) $col), $header, true);

            return $idx === false ? null : ($row[$idx] ?? null);
        };

        $rows = [];
        $errors = [];
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($allRows as $i => $raw) {
            $lineNumber = $i + 1;
            $rawDate = $resolve($raw, 'transaction_date');
            $transactionDate = $this->parseDate($rawDate, $dateFormat);

            if (! $transactionDate) {
                $errors[] = ['line' => $lineNumber, 'error' => __('accounting.csv_invalid_date', ['value' => (string) $rawDate])];
                continue;
            }

            [$debit, $credit] = $this->resolveAmounts($raw, $resolve, $amountSign);

            if ($debit <= 0 && $credit <= 0) {
                $errors[] = ['line' => $lineNumber, 'error' => __('accounting.csv_no_amount')];
                continue;
            }
            if ($debit > 0 && $credit > 0) {
                $errors[] = ['line' => $lineNumber, 'error' => __('accounting.csv_both_amounts')];
                continue;
            }

            $reference = $this->clean($resolve($raw, 'reference'));
            $valueDate = $this->parseDate($resolve($raw, 'value_date'), $dateFormat);

            $rows[] = [
                'line_number' => $lineNumber,
                'transaction_date' => $transactionDate->toDateString(),
                'value_date' => $valueDate?->toDateString(),
                'reference' => $reference,
                'normalized_reference' => $this->normaliseReference($reference),
                'description' => $this->clean($resolve($raw, 'description')),
                'debit_amount' => $debit,
                'credit_amount' => $credit,
                'balance_after' => $this->parseAmount($resolve($raw, 'balance_after')),
                'external_transaction_id' => $this->clean($resolve($raw, 'external_transaction_id')),
            ];

            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        return [
            'rows' => $rows,
            'errors' => $errors,
            'totals' => [
                'debit' => round($totalDebit, 2),
                'credit' => round($totalCredit, 2),
                'count' => count($rows),
            ],
        ];
    }

    protected function resolveAmounts(array $raw, callable $resolve, string $amountSign): array
    {
        $debitCol = $resolve($raw, 'debit');
        $creditCol = $resolve($raw, 'credit');

        if ($debitCol !== null || $creditCol !== null) {
            return [
                max(0.0, $this->parseAmount($debitCol) ?? 0.0),
                max(0.0, $this->parseAmount($creditCol) ?? 0.0),
            ];
        }

        $signed = $this->parseAmount($resolve($raw, 'amount'));
        if ($signed === null) {
            return [0.0, 0.0];
        }

        if ($amountSign === 'debit_positive') {
            $signed = -$signed;
        }

        // credit_positive: positive = credit (money in), negative = debit (money out).
        return $signed >= 0 ? [0.0, round($signed, 2)] : [round(abs($signed), 2), 0.0];
    }

    protected function parseDate($value, string $format): ?Carbon
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        foreach (array_unique([$format, 'Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'd M Y', 'd-M-Y']) as $fmt) {
            try {
                $parsed = Carbon::createFromFormat($fmt, $value);
                if ($parsed !== false) {
                    return $parsed->startOfDay();
                }
            } catch (\Throwable) {
                // try next format
            }
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function parseAmount($value): ?float
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $negative = str_starts_with($value, '(') && str_ends_with($value, ')');
        $value = preg_replace('/[^0-9\.\-]/', '', str_replace(',', '', $value));
        if ($value === '' || $value === '-' || $value === '.') {
            return null;
        }

        $amount = round((float) $value, 2);

        return $negative ? -abs($amount) : $amount;
    }

    protected function clean($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : substr($value, 0, 255);
    }

    public function normaliseReference(?string $reference): ?string
    {
        if ($reference === null) {
            return null;
        }

        return strtoupper(preg_replace('/[^a-z0-9]/i', '', $reference)) ?: null;
    }
}
