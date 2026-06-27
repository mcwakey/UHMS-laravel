<?php

namespace Database\Seeders\ManualTesting;

use Illuminate\Support\Facades\DB;

class ManualAccountingSeeder extends ManualTestingSeederBase
{
    public function run(): void
    {
        if (! $this->hasTable('journal_entries') || $this->countManual('journal_entries', 'journal_number') > 0) {
            return;
        }

        $creator = DB::table('users')->where('email', 'accounts.user@uhms.test')->value('id');
        $fiscalYearId = DB::table('fiscal_years')->value('id');
        $periodId = DB::table('accounting_periods')->value('id');
        if (! $fiscalYearId || ! $periodId) {
            return;
        }

        $rows = [];
        foreach (['posted', 'draft', 'reversed', 'closing'] as $index => $status) {
            $rows[] = [
                'journal_number' => $this->ref('JE', $index + 1),
                'entry_date' => today()->subDays($index * 15),
                'fiscal_year_id' => $fiscalYearId,
                'accounting_period_id' => $periodId,
                'reference_number' => $this->ref('JE', $index + 1),
                'reference_type' => 'manual_test',
                'reference_id' => $index + 1,
                'source_module' => 'manual_test',
                'description' => 'Manual accounting '.$status.' entry',
                'status' => $status,
                'posted_at' => $status === 'posted' ? now()->subDays($index) : null,
                'posted_by' => $status === 'posted' ? $creator : null,
                'created_by' => $creator,
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ];
        }
        $this->insert('journal_entries', $rows);
    }
}
