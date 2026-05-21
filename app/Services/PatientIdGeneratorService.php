<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

/**
 * Generates a unique, concurrency-safe Patient ID.
 *
 * Configuration lives in config/patient.php.
 * The sequence counter is stored in patient_number_sequences and protected by
 * a database row-level lock (lockForUpdate) so concurrent registrations never
 * produce duplicate numbers.
 */
class PatientIdGeneratorService
{
    public function generate(): string
    {
        $prefix       = config('patient.id_prefix', 'UHMS');
        $pattern      = config('patient.id_pattern', '{PREFIX}-{SEQUENCE}/{YEAR}');
        $seqLength    = max(1, (int) config('patient.id_sequence_length', 6));
        $resetPeriod  = config('patient.id_reset_period', 'yearly');

        $periodKey = $this->resolvePeriodKey($resetPeriod);

        $sequence = DB::transaction(function () use ($prefix, $resetPeriod, $periodKey) {
            // Attempt to lock the existing row; if absent, insert it first.
            $row = DB::table('patient_number_sequences')
                ->where('prefix', $prefix)
                ->where('period_type', $resetPeriod)
                ->where('period_key', $periodKey)
                ->lockForUpdate()
                ->first();

            if ($row === null) {
                DB::table('patient_number_sequences')->insert([
                    'prefix'        => $prefix,
                    'period_type'   => $resetPeriod,
                    'period_key'    => $periodKey,
                    'last_sequence' => 0,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);

                // Re-acquire the lock after insert.
                $row = DB::table('patient_number_sequences')
                    ->where('prefix', $prefix)
                    ->where('period_type', $resetPeriod)
                    ->where('period_key', $periodKey)
                    ->lockForUpdate()
                    ->first();
            }

            $next = $row->last_sequence + 1;

            DB::table('patient_number_sequences')
                ->where('id', $row->id)
                ->update(['last_sequence' => $next, 'updated_at' => now()]);

            return $next;
        });

        $now = Carbon::now();

        $id = str_replace(
            ['{PREFIX}', '{YEAR}', '{YY}', '{MONTH}', '{DAY}', '{SEQUENCE}'],
            [
                $prefix,
                $now->format('Y'),
                $now->format('y'),
                $now->format('m'),
                $now->format('d'),
                str_pad((string) $sequence, $seqLength, '0', STR_PAD_LEFT),
            ],
            $pattern
        );

        // Guarantee uniqueness in case of schema conflicts.
        if (DB::table('patients')->where('patient_number', $id)->exists()) {
            // Append a collision suffix rather than leaving a gap in the sequence.
            $id = $id . '-' . strtoupper(substr(uniqid(), -4));
        }

        return $id;
    }

    private function resolvePeriodKey(string $resetPeriod): string
    {
        $now = Carbon::now();

        return match ($resetPeriod) {
            'yearly'  => $now->format('Y'),
            'monthly' => $now->format('Y-m'),
            'daily'   => $now->format('Y-m-d'),
            default   => 'GLOBAL',  // 'never'
        };
    }
}
