<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearManualTestDataCommand extends Command
{
    protected $signature = 'uhms:clear-manual-test-data {--force : Delete without interactive confirmation}';

    protected $description = 'Clear only UHMS manual test records identified by MT-* prefixes and @uhms.test users.';

    private const PREFIX = 'MT-';

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('Manual test cleanup is disabled in production.');

            return self::FAILURE;
        }

        $summary = $this->summary();
        foreach ($summary as $label => $count) {
            $this->line(str_pad($label.':', 34).$count);
        }

        if (! $this->option('force') && ! $this->confirm('Delete only manual test records listed above?', false)) {
            $this->warn('Cleanup cancelled.');

            return self::SUCCESS;
        }

        DB::transaction(function () {
            $manualUserIds = Schema::hasColumn('users', 'email')
                ? DB::table('users')->where('email', 'like', '%@uhms.test')->pluck('id')->all()
                : [];

            if ($manualUserIds && Schema::hasTable('model_has_roles')) {
                DB::table('model_has_roles')
                    ->where('model_type', \App\Models\User::class)
                    ->whereIn('model_id', $manualUserIds)
                    ->delete();
            }

            if ($manualUserIds && Schema::hasTable('department_user')) {
                DB::table('department_user')->whereIn('user_id', $manualUserIds)->delete();
            }

            foreach ($this->deletePlan() as [$table, $column]) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                DB::table($table)->where($column, 'like', self::PREFIX.'%')->delete();
            }

            if (Schema::hasColumn('users', 'email')) {
                DB::table('users')->where('email', 'like', '%@uhms.test')->delete();
            }
        });

        $this->info('Manual test records cleared.');

        return self::SUCCESS;
    }

    private function summary(): array
    {
        return [
            'Manual test patients' => $this->countPrefix('patients', 'patient_number'),
            'Manual test visits' => $this->countPrefix('visits', 'visit_number'),
            'Manual test invoices' => $this->countPrefix('invoices', 'invoice_number'),
            'Manual test appointments' => $this->countPrefix('appointments', 'appointment_number'),
            'Manual test users' => Schema::hasColumn('users', 'email') ? DB::table('users')->where('email', 'like', '%@uhms.test')->count() : 0,
            'Manual test lab requests' => $this->countPrefix('lab_requests', 'request_number'),
            'Manual test prescriptions' => $this->countPrefix('prescriptions', 'prescription_number'),
            'Manual test emergency cases' => $this->countPrefix('emergency_cases', 'emergency_number'),
        ];
    }

    private function countPrefix(string $table, string $column): int
    {
        if (! Schema::hasColumn($table, $column)) {
            return 0;
        }

        return (int) DB::table($table)->where($column, 'like', self::PREFIX.'%')->count();
    }

    private function deletePlan(): array
    {
        return [
            ['activity_log', 'description'],
            ['sms_messages', 'provider_batch_reference'],
            ['payment_provider_transactions', 'payment_reference'],
            ['payroll_records', 'pay_period'],
            ['payroll_runs', 'pay_period'],
            ['employee_attendance', 'notes'],
            ['employees', 'employee_number'],
            ['emergency_case_logs', 'title'],
            ['emergency_cases', 'emergency_number'],
            ['admissions', 'admission_number'],
            ['prescription_items', 'drug_name'],
            ['prescriptions', 'prescription_number'],
            ['lab_results', 'remarks'],
            ['lab_request_items', 'name'],
            ['lab_requests', 'request_number'],
            ['payments', 'payment_number'],
            ['invoice_items', 'description'],
            ['invoices', 'invoice_number'],
            ['appointments', 'appointment_number'],
            ['visit_status_logs', 'notes'],
            ['queue_entries', 'notes'],
            ['visits', 'visit_number'],
            ['patient_insurances', 'membership_number'],
            ['patients', 'patient_number'],
            ['service_catalog', 'code'],
            ['insurance_providers', 'code'],
            ['departments', 'code'],
        ];
    }
}
