<?php

namespace Database\Seeders\ManualTesting;

use Illuminate\Support\Facades\DB;

class ManualAuditSeeder extends ManualTestingSeederBase
{
    public function run(): void
    {
        if (! $this->hasTable('activity_log') || $this->countManual('activity_log', 'description') > 0) {
            return;
        }

        $causerId = DB::table('users')->where('email', 'admin.manual@uhms.test')->value('id');
        $events = ['patient creation', 'visit creation', 'billing', 'payment', 'lab result validation', 'pharmacy dispensing', 'payroll approval', 'permission update', 'login activity'];
        $rows = [];
        for ($i = 1; $i <= $this->target('audit_logs'); $i++) {
            $event = $events[$i % count($events)];
            $rows[] = [
                'log_name' => 'manual_test',
                'description' => $this->ref('AUD', $i).' '.$event,
                'subject_type' => 'manual_test',
                'subject_id' => $i,
                'causer_type' => \App\Models\User::class,
                'causer_id' => $causerId,
                'event' => 'manual_test',
                'properties' => $this->metadata(['event' => $event]),
                'created_at' => now()->subMinutes($i * 7),
                'updated_at' => $this->now(),
            ];
        }
        $this->insert('activity_log', $rows);
    }
}
