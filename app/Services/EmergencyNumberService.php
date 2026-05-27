<?php

namespace App\Services;

use App\Models\EmergencyCase;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;

class EmergencyNumberService
{
    public function generateCaseNumber(): string
    {
        return DB::transaction(function () {
            $prefix = 'ER-'.now()->format('Y').'-';
            $last = EmergencyCase::query()
                ->where('emergency_number', 'like', $prefix.'%')
                ->lockForUpdate()
                ->orderByDesc('id')
                ->value('emergency_number');

            $sequence = $last ? ((int) substr($last, -6)) + 1 : 1;

            do {
                $number = $prefix.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
                $sequence++;
            } while (EmergencyCase::where('emergency_number', $number)->exists());

            return $number;
        });
    }

    public function generateTemporaryPatientNumber(): string
    {
        return DB::transaction(function () {
            $prefix = 'TEMP-ER-'.now()->format('Y').'-';
            $last = Patient::query()
                ->where('patient_number', 'like', $prefix.'%')
                ->lockForUpdate()
                ->orderByDesc('id')
                ->value('patient_number');

            $sequence = $last ? ((int) substr($last, -6)) + 1 : 1;

            do {
                $number = $prefix.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
                $sequence++;
            } while (Patient::where('patient_number', $number)->exists());

            return $number;
        });
    }
}
