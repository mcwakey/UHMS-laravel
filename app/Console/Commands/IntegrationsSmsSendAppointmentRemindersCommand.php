<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\SmsNotificationEvent;
use App\Services\Integrations\Sms\SmsNotificationEventService;
use Illuminate\Console\Command;

/**
 * Sends appointment-reminder SMS for upcoming appointments. Strictly opt-in: the
 * SmsNotificationEventService skips everything unless the appointment-reminder
 * toggle is on, a provider is active and the patient has a valid phone. Dedup is
 * handled per appointment/event so re-running is safe.
 */
class IntegrationsSmsSendAppointmentRemindersCommand extends Command
{
    protected $signature = 'integrations:sms-send-appointment-reminders
        {--date= : Single appointment date (Y-m-d). Defaults to tomorrow}
        {--from= : Range start date (Y-m-d)}
        {--to= : Range end date (Y-m-d)}
        {--dry-run : Report what would be sent without sending}';

    protected $description = 'Send appointment-reminder SMS for upcoming appointments (opt-in; never blocks scheduling).';

    public function handle(SmsNotificationEventService $events): int
    {
        $from = $this->option('from') ?: ($this->option('date') ?: now()->addDay()->toDateString());
        $to = $this->option('to') ?: ($this->option('date') ?: $from);
        $dryRun = (bool) $this->option('dry-run');

        $appointments = Appointment::query()
            ->with('patient')
            ->whereBetween('appointment_date', [$from, $to])
            ->whereNotIn('status', ['cancelled', 'completed', 'no_show'])
            ->get();

        $summary = ['candidates' => $appointments->count(), 'sent' => 0, 'skipped' => 0];

        foreach ($appointments as $appointment) {
            $phone = $appointment->patient?->phone;
            if ($dryRun) {
                $this->line(sprintf('  #%d %s %s → %s', $appointment->id, $appointment->appointment_date, $appointment->start_time, $phone ?: '(no phone)'));
                continue;
            }

            $event = $events->dispatch([
                'event_type' => SmsNotificationEvent::TYPE_APPOINTMENT_REMINDER,
                'source_type' => 'appointment',
                'source_id' => $appointment->id,
                'phone' => $phone,
                'data' => [
                    'patient_name' => $appointment->patient?->first_name,
                    'appointment_date' => (string) $appointment->appointment_date,
                    'appointment_time' => (string) $appointment->start_time,
                    'hospital_name' => config('app.name', 'UHMS'),
                ],
            ]);

            $event->status === SmsNotificationEvent::STATUS_SENT ? $summary['sent']++ : $summary['skipped']++;
        }

        $this->info('Appointment reminder run complete' . ($dryRun ? ' (dry-run)' : ''));
        foreach ($summary as $key => $value) {
            $this->line(sprintf('  %-11s %d', $key, $value));
        }

        return self::SUCCESS;
    }
}
