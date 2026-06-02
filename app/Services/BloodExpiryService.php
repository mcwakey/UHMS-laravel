<?php

namespace App\Services;

use App\Models\BloodUnit;
use Illuminate\Support\Collection;

class BloodExpiryService
{
    public function expireDueUnits(): int
    {
        return BloodUnit::query()
            ->whereDate('expiry_date', '<', now()->toDateString())
            ->whereNotIn('status', [
                BloodUnit::STATUS_ISSUED,
                BloodUnit::STATUS_TRANSFUSED,
                BloodUnit::STATUS_EXPIRED,
                BloodUnit::STATUS_DISCARDED,
            ])
            ->update(['status' => BloodUnit::STATUS_EXPIRED]);
    }

    public function expiringSoon(int $days = 7): Collection
    {
        return BloodUnit::query()
            ->with('storageLocation')
            ->whereIn('status', [BloodUnit::STATUS_AVAILABLE, BloodUnit::STATUS_RESERVED, BloodUnit::STATUS_CROSSMATCHED])
            ->whereBetween('expiry_date', [now()->toDateString(), now()->addDays($days)->toDateString()])
            ->orderBy('expiry_date')
            ->get();
    }
}
