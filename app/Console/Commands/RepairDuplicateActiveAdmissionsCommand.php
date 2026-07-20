<?php

namespace App\Console\Commands;

use App\Enums\AdmissionStatus;
use App\Models\Admission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairDuplicateActiveAdmissionsCommand extends Command
{
    protected $signature = 'admissions:repair-duplicate-active
        {--patient= : Limit to a patient id or patient number}
        {--apply : Apply the repair instead of only reporting what would change}';

    protected $description = 'Close duplicate active admissions when a discharged canonical admission exists for the same patient, visit, and bed.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $patient = trim((string) $this->option('patient'));
        $checked = 0;
        $repairable = 0;
        $updated = 0;
        $skipped = 0;

        $groups = Admission::query()
            ->select('patient_id', 'visit_id', 'bed_id')
            ->when($patient !== '', function ($query) use ($patient) {
                $query->whereHas('patient', function ($patientQuery) use ($patient) {
                    $patientQuery
                        ->where('patient_number', $patient)
                        ->when(ctype_digit($patient), fn ($q) => $q->orWhere('id', (int) $patient));
                });
            })
            ->groupBy('patient_id', 'visit_id', 'bed_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($groups as $group) {
            $checked++;

            $admissions = Admission::query()
                ->with('patient')
                ->where('patient_id', $group->patient_id)
                ->where('visit_id', $group->visit_id)
                ->where('bed_id', $group->bed_id)
                ->orderBy('admission_date')
                ->orderBy('id')
                ->get();

            $canonical = $admissions
                ->filter(fn (Admission $admission) => $admission->status === AdmissionStatus::DISCHARGED && $admission->actual_discharge_date !== null)
                ->sortByDesc(fn (Admission $admission) => $admission->actual_discharge_date?->timestamp ?? 0)
                ->first();

            $duplicates = $admissions->filter(fn (Admission $admission) => $admission->is_active);

            if (! $canonical || $duplicates->isEmpty()) {
                $skipped++;
                continue;
            }

            $repairable++;
            $patientLabel = $canonical->patient?->patient_number ?: "patient {$group->patient_id}";
            $duplicateNumbers = $duplicates->pluck('admission_number')->implode(', ');

            $this->line(sprintf(
                '%s visit=%s bed=%s canonical=%s close=[%s]',
                $patientLabel,
                $group->visit_id,
                $group->bed_id,
                $canonical->admission_number,
                $duplicateNumbers
            ));

            if (! $apply) {
                continue;
            }

            DB::transaction(function () use ($duplicates, $canonical, &$updated) {
                foreach ($duplicates as $duplicate) {
                    $duplicate->update([
                        'status' => AdmissionStatus::DISCHARGED,
                        'actual_discharge_date' => $canonical->actual_discharge_date,
                        'discharged_by' => $canonical->discharged_by,
                        'discharge_summary' => $duplicate->discharge_summary ?: $canonical->discharge_summary,
                        'discharge_instructions' => $duplicate->discharge_instructions ?: $canonical->discharge_instructions,
                    ]);

                    $updated++;
                }
            });
        }

        $mode = $apply ? 'applied' : 'dry-run';
        $this->info("Duplicate admission repair {$mode}: checked={$checked} repairable={$repairable} updated={$updated} skipped={$skipped}");

        if (! $apply && $repairable > 0) {
            $this->comment('Run again with --apply to close the listed duplicate active admissions.');
        }

        return self::SUCCESS;
    }
}
