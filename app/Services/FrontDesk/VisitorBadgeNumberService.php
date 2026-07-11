<?php

namespace App\Services\FrontDesk;

use App\Models\FrontDeskVisitorLog;
use Illuminate\Support\Facades\DB;

/**
 * Generates unique visitor pass / badge numbers in the form `VIS-YYYYMMDD-0001`,
 * sequential and unique per day. Manually-entered badge numbers are never
 * overwritten (see VisitorLogService::create()).
 */
class VisitorBadgeNumberService
{
    /**
     * Generate the next badge number for today. A short retry loop guards against
     * the race where two check-ins compute the same sequence concurrently
     * (the `badge_number` column carries no DB unique constraint, so we re-check).
     */
    public function generate(): string
    {
        $prefix = (string) config('front_desk.visitors.badge_prefix', 'VIS');
        $date = now()->format('Ymd');

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $candidate = $this->next($prefix, $date);

            if (! FrontDeskVisitorLog::where('badge_number', $candidate)->exists()) {
                return $candidate;
            }
        }

        // Extremely unlikely fall-through: suffix with a random block to stay unique.
        return $this->next($prefix, $date) . '-' . str_pad((string) random_int(0, 999), 3, '0', STR_PAD_LEFT);
    }

    private function next(string $prefix, string $date): string
    {
        $last = DB::table('front_desk_visitor_logs')
            ->where('badge_number', 'like', "{$prefix}-{$date}-%")
            ->orderByDesc('badge_number')
            ->value('badge_number');

        $sequence = $last ? ((int) substr($last, -4)) + 1 : 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }
}
