<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait GeneratesNumbers
{
    /**
     * Generate a unique formatted number.
     *
     * @param string $prefix e.g. 'PT', 'VST', 'INV'
     * @param string $table  The table to check for uniqueness
     * @param string $column The column that stores the number
     */
    public static function generateNumber(string $prefix, string $table, string $column): string
    {
        $date = now()->format('Ymd');
        $pattern = "{$prefix}-{$date}-%";

        $lastNumber = DB::table($table)
            ->where($column, 'like', $pattern)
            ->orderByDesc($column)
            ->value($column);

        if ($lastNumber) {
            $lastSequence = (int) substr($lastNumber, -4);
            $nextSequence = $lastSequence + 1;
        } else {
            $nextSequence = 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $date, $nextSequence);
    }
}
