<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('credit_notes')
            ->where('status', 'cancelled')
            ->where('is_reversal', false)
            ->orderBy('id')
            ->each(function ($original): void {
                if (DB::table('credit_notes')->where('reverses_credit_note_id', $original->id)->exists()) {
                    return;
                }

                $timestamp = $original->cancelled_at ?: $original->updated_at ?: now();
                $reason = $original->cancellation_reason ?: 'Legacy reversal';

                DB::table('credit_notes')->insert([
                    'credit_note_number' => 'REV-LEGACY-' . $original->id,
                    'invoice_id' => $original->invoice_id,
                    'patient_id' => $original->patient_id,
                    'type' => $original->type,
                    'status' => 'reversal',
                    'is_reversal' => true,
                    'reverses_credit_note_id' => $original->id,
                    'amount' => $original->amount,
                    'reason' => 'Reversal of ' . $original->credit_note_number,
                    'notes' => $reason,
                    'issued_by' => $original->cancelled_by ?: $original->issued_by,
                    'journal_entry_id' => $original->reversal_journal_entry_id,
                    'accounting_posted_at' => $original->reversed_at,
                    'accounting_status' => $original->reversal_journal_entry_id ? 'posted' : 'pending',
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);

                DB::table('credit_notes')
                    ->where('id', $original->id)
                    ->update([
                        'status' => 'reversed',
                        'reversed_at' => $original->reversed_at ?: $timestamp,
                        'reversed_by' => $original->reversed_by ?: $original->cancelled_by,
                        'reversal_reason' => $original->reversal_reason ?: $reason,
                        'updated_at' => now(),
                    ]);
            });
    }

    public function down(): void
    {
        $legacyReversals = DB::table('credit_notes')
            ->where('is_reversal', true)
            ->where('credit_note_number', 'like', 'REV-LEGACY-%')
            ->get();

        foreach ($legacyReversals as $reversal) {
            DB::table('credit_notes')
                ->where('id', $reversal->reverses_credit_note_id)
                ->update(['status' => 'cancelled', 'updated_at' => now()]);

            DB::table('credit_notes')->where('id', $reversal->id)->delete();
        }
    }
};
