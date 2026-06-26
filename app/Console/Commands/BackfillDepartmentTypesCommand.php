<?php

namespace App\Console\Commands;

use App\Enums\DepartmentType;
use App\Models\Department;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Suggests precise department types from department names and (optionally)
 * backfills the HIGH-confidence matches. Dry-run by default.
 *
 *   php artisan departments:backfill-types            # dry-run (report only)
 *   php artisan departments:backfill-types --apply    # persist high-confidence changes
 *
 * Medium-confidence and unmapped rows are NEVER auto-applied — they are listed
 * for manual review. When a department type changes, the denormalised
 * service_catalog.department_type is re-synced for that department's services.
 */
class BackfillDepartmentTypesCommand extends Command
{
    protected $signature = 'departments:backfill-types {--apply : Persist high-confidence changes (otherwise dry-run)}';

    protected $description = 'Suggest/backfill precise department types from department names (dry-run by default).';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $departments = Department::orderBy('name')->get();

        if ($departments->isEmpty()) {
            $this->warn('No departments found.');

            return self::SUCCESS;
        }

        $rows = [];
        $changes = []; // [Department, DepartmentType $new, ?string $oldValue]
        $counts = ['ok' => 0, 'high' => 0, 'review' => 0, 'unmapped' => 0];

        foreach ($departments as $department) {
            $current = $department->type instanceof DepartmentType ? $department->type : null;
            $old = $current?->value ?? 'null';
            $suggestion = $this->suggest($department->name ?? '');

            if ($suggestion === null) {
                $counts['unmapped']++;
                $rows[] = [$department->id, $department->name, $department->code, $old, '—', 'none', 'no keyword match', 'unmapped → review'];

                continue;
            }

            [$type, $confidence, $reason] = $suggestion;

            if ($current === $type) {
                $counts['ok']++;
                $rows[] = [$department->id, $department->name, $department->code, $old, $type->value, $confidence, $reason, 'ok (no change)'];

                continue;
            }

            if ($confidence === 'high') {
                $counts['high']++;
                $changes[] = [$department, $type, $current?->value];
                $rows[] = [$department->id, $department->name, $department->code, $old, $type->value, 'high', $reason, $apply ? 'applied' : 'would apply'];
            } else {
                $counts['review']++;
                $rows[] = [$department->id, $department->name, $department->code, $old, $type->value, $confidence, $reason, 'review (manual)'];
            }
        }

        if ($apply && $changes) {
            DB::transaction(function () use ($changes) {
                foreach ($changes as [$department, $type, $oldValue]) {
                    $department->type = $type->value;
                    $department->save();

                    // Re-sync the denormalised copy on services, but only where it
                    // still mirrors the old department type (don't clobber overrides).
                    DB::table('service_catalog')
                        ->where('department_id', $department->id)
                        ->where(function ($q) use ($oldValue) {
                            $q->whereNull('department_type');
                            if ($oldValue !== null) {
                                $q->orWhere('department_type', $oldValue);
                            }
                        })
                        ->update(['department_type' => $type->value]);
                }
            });
        }

        $this->table(['ID', 'Name', 'Code', 'Old', 'Suggested', 'Confidence', 'Reason', 'Action'], $rows);
        $this->newLine();
        $this->info(sprintf(
            '%s — %d unchanged, %d high-confidence %s, %d medium (review), %d unmapped.',
            $apply ? 'APPLIED' : 'DRY-RUN',
            $counts['ok'],
            $counts['high'],
            $apply ? 'applied' : 'to apply',
            $counts['review'],
            $counts['unmapped'],
        ));

        if (! $apply && ($counts['high'] > 0)) {
            $this->comment('Re-run with --apply to persist the high-confidence changes. Medium/unmapped rows are never auto-applied.');
        }

        return self::SUCCESS;
    }

    /**
     * First matching rule wins. Ordered most-specific → least-specific.
     *
     * @return array{0: DepartmentType, 1: string, 2: string}|null
     */
    private function suggest(string $name): ?array
    {
        $rules = [
            ['/mortuary|morgue/i',                                      DepartmentType::MORTUARY,       'high',   'mortuary keyword'],
            ['/ambulance/i',                                            DepartmentType::AMBULANCE,      'high',   'ambulance keyword'],
            ['/blood\s*bank|transfusion/i',                             DepartmentType::BLOOD_BANK,     'high',   'blood bank keyword'],
            ['/emergency|casualty/i',                                   DepartmentType::EMERGENCY,      'high',   'emergency keyword'],
            ['/maternity|antenatal|postnatal|\banc\b|labou?r\s*ward|delivery/i', DepartmentType::MATERNITY, 'medium', 'maternity keyword (verify OPD vs ward)'],
            ['/\bward\b|\bicu\b|\bnicu\b|inpatient|admission/i',        DepartmentType::INPATIENT,      'high',   'ward/inpatient keyword'],
            ['/theatre|operating\s*(room|theatre)|\bot\b/i',            DepartmentType::THEATRE,        'high',   'theatre keyword'],
            ['/radiolog|x-?ray|ultrasound|imaging|\busg\b|\bct\b|\bmri\b|scan/i', DepartmentType::RADIOLOGY, 'high', 'radiology/imaging keyword'],
            ['/laborator|patholog|\blab\b/i',                           DepartmentType::INVESTIGATION,  'high',   'laboratory keyword'],
            ['/pharmac|dispensary/i',                                   DepartmentType::PHARMACY,       'high',   'pharmacy keyword'],
            ['/record|folder|archive/i',                                DepartmentType::RECORDS,        'high',   'records keyword'],
            ['/billing|cashier|finance|account|claim|revenue/i',        DepartmentType::FINANCE,        'medium', 'finance keyword'],
            ['/store|procurement|inventory|warehouse|supply/i',         DepartmentType::STORES,         'high',   'stores keyword'],
            ['/maintenance|biomedical|\bit\b|security|laundry|housekeep|cssd/i', DepartmentType::SUPPORT, 'high', 'support keyword'],
            ['/human\s*resource|\bhr\b|management|administration|governance|settings/i', DepartmentType::ADMINISTRATIVE, 'medium', 'admin/HR keyword'],
            ['/physio|dressing|injection|treatment\s*room|wound/i',     DepartmentType::TREATMENT,      'medium', 'treatment keyword'],
            ['/nursing\s*station|nurse\s*station/i',                    DepartmentType::NURSING,        'medium', 'nursing keyword'],
            ['/opd|consult|clinic|medicine|p[ae]diatric|orthop|dental|eye|ophthal|\bent\b|psychiat|dermatolog|cardiolog|gyn|obstetric|family\s*planning|surgery/i', DepartmentType::CONSULTATION, 'medium', 'consultation/clinic keyword'],
        ];

        foreach ($rules as [$pattern, $type, $confidence, $reason]) {
            if (preg_match($pattern, $name)) {
                return [$type, $confidence, $reason];
            }
        }

        return null;
    }
}
