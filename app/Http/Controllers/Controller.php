<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Normalise a combined date-range filter ("YYYY-MM-DD to YYYY-MM-DD", as
     * produced by the reportrange picker partial) into discrete `date_from` /
     * `date_to` keys that list services understand. Existing explicit
     * date_from/date_to values are preserved when no range is supplied.
     */
    protected static function withParsedDateRange(array $filters, string $key = 'date_range'): array
    {
        $range = trim((string) ($filters[$key] ?? ''));
        if ($range === '') {
            return $filters;
        }

        $parts = array_values(array_filter(preg_split('/\s+to\s+/', $range)));
        $filters['date_from'] = $parts[0] ?? null;
        $filters['date_to'] = $parts[1] ?? ($parts[0] ?? null);

        return $filters;
    }
}
