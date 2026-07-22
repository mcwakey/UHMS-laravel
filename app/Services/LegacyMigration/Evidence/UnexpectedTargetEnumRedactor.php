<?php

namespace App\Services\LegacyMigration\Evidence;

final class UnexpectedTargetEnumRedactor
{
    /** @param list<object> $rows @param list<string> $allowed @return array<string,mixed> */
    public function redact(array $rows, array $allowed): array
    {
        $unexpectedCount = 0;
        $unexpectedDistinctCount = 0;
        $observed = array_map(function (object $row) use ($allowed, &$unexpectedCount, &$unexpectedDistinctCount): array {
            $value = (string) $row->category_value;
            if ($value === '__NULL__' || in_array($value, $allowed, true)) {
                return ['value' => $value, 'count' => (int) $row->aggregate_count, 'classification' => 'allow_listed'];
            }
            $unexpectedCount += (int) $row->aggregate_count;
            $unexpectedDistinctCount++;

            return ['count' => (int) $row->aggregate_count, 'classification' => 'redacted_unexpected_count_only'];
        }, $rows);

        return [
            'observed_aggregate_values' => $observed,
            'unexpected_value_count' => $unexpectedCount,
            'unexpected_distinct_value_count' => $unexpectedDistinctCount,
            'unexpected_value_reporting' => 'count_only_no_digest',
            'all_non_null_values_compatible' => $unexpectedDistinctCount === 0,
        ];
    }
}
