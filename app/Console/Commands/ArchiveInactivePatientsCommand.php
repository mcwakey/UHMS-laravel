<?php

namespace App\Console\Commands;

use App\Models\ArchivedPatient;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ArchiveInactivePatientsCommand extends Command
{
    protected $signature = 'patients:archive-inactive
        {--days=365 : Archive patients with no activity for this many days}
        {--dry-run : Show how many patients would be archived without changing data}';

    protected $description = 'Archive inactive patient folders into the archived_patients table.';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days)->endOfDay();
        $dryRun = (bool) $this->option('dry-run');
        $archived = 0;

        $query = Patient::query()
            ->select('patients.*')
            ->whereIn('status', ['active', 'inactive'])
            ->where(function ($query) {
                $query->whereNull('is_deceased')->orWhere('is_deceased', false);
            })
            ->where(function ($query) {
                $query->whereNull('merge_status')->orWhere('merge_status', '!=', 'MERGED');
            })
            ->whereNull('merged_to_patient_id')
            ->whereDoesntHave('archivedRecord')
            ->addSelect([
                'latest_visit_date' => Visit::query()
                    ->select('visit_date')
                    ->whereColumn('patient_id', 'patients.id')
                    ->latest('visit_date')
                    ->limit(1),
                'latest_appointment_date' => Appointment::query()
                    ->select('appointment_date')
                    ->whereColumn('patient_id', 'patients.id')
                    ->latest('appointment_date')
                    ->limit(1),
            ]);

        $query->chunkById(200, function ($patients) use ($cutoff, $dryRun, &$archived) {
            foreach ($patients as $patient) {
                $lastActivityAt = $this->lastActivityAt($patient);

                if ($lastActivityAt->gt($cutoff)) {
                    continue;
                }

                $archived++;

                if ($dryRun) {
                    continue;
                }

                DB::transaction(function () use ($patient, $lastActivityAt) {
                    ArchivedPatient::updateOrCreate(
                        ['patient_id' => $patient->id],
                        [
                            'patient_number' => $patient->patient_number,
                            'full_name' => $patient->full_name,
                            'status_before_archive' => $patient->status,
                            'last_activity_at' => $lastActivityAt,
                            'archived_at' => now(),
                            'reason' => 'inactive',
                            'payload' => [
                                'patient' => $patient->withoutRelations()->attributesToArray(),
                                'latest_visit_date' => $patient->latest_visit_date,
                                'latest_appointment_date' => $patient->latest_appointment_date,
                            ],
                        ]
                    );

                    $patient->forceFill([
                        'status' => 'archived',
                        'is_active' => false,
                    ])->save();
                });
            }
        });

        $message = $dryRun
            ? "Patients that would be archived: {$archived}"
            : "Patients archived: {$archived}";

        $this->info($message);

        return self::SUCCESS;
    }

    private function lastActivityAt(Patient $patient): Carbon
    {
        $dates = collect([
            $patient->updated_at,
            $patient->created_at,
            $patient->latest_visit_date ? Carbon::parse($patient->latest_visit_date)->endOfDay() : null,
            $patient->latest_appointment_date ? Carbon::parse($patient->latest_appointment_date)->endOfDay() : null,
        ])->filter();

        return $dates->max() ?: now();
    }
}
