<?php

namespace App\Services\Maternity\Testing;

use App\Enums\ConsultationMaternityLinkRole;
use App\Enums\DepartmentType;
use App\Enums\Gender;
use App\Enums\PregnancyProfileStatus;
use App\Enums\VisitStatus;
use App\Models\AntenatalVisit;
use App\Models\ConsultationSpecialtyEntry;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\Department;
use App\Models\Patient;
use App\Models\PregnancyProfile;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Services\Consultation\Maternity\ConsultationMaternityLinkService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Phase 14R.7 — isolated, removable O&G/Maternity pilot data.
 *
 * Every record carries the marker `MT-OBGYN-14R7-` and is recorded in a batch
 * manifest, so cleanup can delete exactly what this service created and nothing
 * that merely resembles it.
 *
 * This service is NEVER referenced by DatabaseSeeder, the installation seeders
 * or normal demo seeding — it runs only from its guarded console command.
 *
 * Deliberately follows the existing MaternityManualTestDataService conventions
 * (marker prefix, `@uhms.test` accounts, factory-created patients) rather than
 * inventing a parallel fixture system.
 */
class ObgynMaternityPilotDataService
{
    public const MARKER = 'MT-OBGYN-14R7-';
    public const MANIFEST_DISK = 'local';
    public const MANIFEST_DIR = 'manual-testing/obgyn-maternity';

    /** Scenario codes this service can produce. */
    public const SCENARIOS = [
        'O1' => 'Obstetrics, no maternity context',
        'O2' => 'Obstetrics, linked profile + ANC',
        'O3' => 'Ambiguous pregnancy profiles',
        'G1' => 'Gynaecology without pregnancy',
        'G2' => 'Positive pregnancy test, unlinked',
        'G3' => 'Gynaecology linked, LMP adoption eligible',
        'G4' => 'Gynaecology linked, LMP conflict / scan dating',
        'R1' => 'Reconciliation: safe_to_link',
        'R2' => 'Reconciliation: safe_to_migrate',
        'R3' => 'Reconciliation: conflict_requires_review',
        'R4' => 'Reconciliation: historical_only',
        'R5' => 'Reconciliation: insufficient_context',
    ];

    private array $manifest = [];

    /**
     * Seed the requested scenarios and write a manifest.
     *
     * @param  list<string>|null  $scenarios  null = all
     * @return array<string, mixed> the manifest
     */
    public function seed(?array $scenarios = null, ?int $departmentId = null): array
    {
        $batchId = self::MARKER.now()->format('Ymd-His').'-'.Str::lower(Str::random(4));
        $scenarios = $scenarios ?: array_keys(self::SCENARIOS);

        $this->manifest = [
            'batch_id' => $batchId,
            'marker' => self::MARKER,
            'created_at' => now()->toIso8601String(),
            'scenarios' => [],
            'records' => [],
            'users' => [],
            'urls' => [],
        ];

        return DB::transaction(function () use ($batchId, $scenarios, $departmentId) {
            $context = $this->baseContext($batchId, $departmentId);

            foreach ($scenarios as $code) {
                $method = 'scenario'.$code;

                if (! method_exists($this, $method)) {
                    continue;
                }

                $this->manifest['scenarios'][$code] = [
                    'code' => $code,
                    'description' => self::SCENARIOS[$code] ?? $code,
                    'records' => $this->{$method}($context, $batchId),
                ];
            }

            $this->writeManifest($batchId);

            return $this->manifest;
        });
    }

    /* ── Base context ──────────────────────────────────────────────────── */

    /**
     * @return array<string, mixed>
     */
    private function baseContext(string $batchId, ?int $departmentId): array
    {
        $consultationDepartment = $departmentId
            ? Department::find($departmentId)
            : Department::firstOrCreate(
                ['code' => 'MT-OBGYN'],
                [
                    'name' => 'MT-OBGYN Pilot Consultation',
                    'type' => DepartmentType::CONSULTATION->value,
                    'status' => 'active',
                ]
            );

        $user = User::firstOrCreate(
            ['email' => 'mt-obgyn-pilot@uhms.test'],
            [
                'first_name' => 'MT',
                'last_name' => 'ObGyn Pilot',
                'phone' => '0200000014',
                'password' => Hash::make('password'),
                'department_id' => $consultationDepartment->id,
                'status' => 'active',
            ]
        );

        $this->manifest['users'][] = ['id' => $user->id, 'email' => $user->email];
        $this->record('departments', $consultationDepartment->id);

        return [
            'department' => $consultationDepartment,
            'user' => $user,
            'obstetrics' => ConsultationSpecialtyProfile::firstOrCreate(
                ['code' => 'obstetrics'],
                ['name' => 'Obstetrics', 'is_active' => true, 'sort_order' => 40]
            ),
            'gynaecology' => ConsultationSpecialtyProfile::firstOrCreate(
                ['code' => 'gynecology'],
                ['name' => 'Gynecology', 'is_active' => true, 'sort_order' => 50]
            ),
        ];
    }

    /* ── Scenarios ─────────────────────────────────────────────────────── */

    /** O1 — Obstetrics consultation with NO maternity context at all. */
    private function scenarioO1(array $ctx, string $batchId): array
    {
        [$patient, $visit, $route] = $this->patientWithRoute($ctx, 'O1');

        return $this->refs($patient, $visit, $route);
    }

    /** O2 — explicit profile + one ANC visit; readiness should be ready. */
    private function scenarioO2(array $ctx, string $batchId): array
    {
        [$patient, $visit, $route] = $this->patientWithRoute($ctx, 'O2');

        $profile = $this->profile($patient, $visit, $ctx, [
            'gravida' => 2, 'para' => 1,
            'last_menstrual_period' => now()->subWeeks(24)->toDateString(),
            'estimated_due_date' => now()->addWeeks(16)->toDateString(),
            'gestational_age_weeks' => 24, 'gestational_age_days' => 0,
            'dating_method' => 'lmp',
        ]);

        $anc = $this->ancVisit($profile, $patient, $visit, $ctx);
        $this->link($route, $profile, $ctx);

        return $this->refs($patient, $visit, $route) + [
            'pregnancy_profile' => $profile->id,
            'antenatal_visit' => $anc->id,
        ];
    }

    /** O3 — two active profiles, nothing linked: the resolver must say ambiguous. */
    private function scenarioO3(array $ctx, string $batchId): array
    {
        [$patient, $visit, $route] = $this->patientWithRoute($ctx, 'O3');

        $first = $this->profile($patient, $visit, $ctx, ['gravida' => 1]);
        $second = $this->profile($patient, $visit, $ctx, [
            'gravida' => 3,
            'profile_status' => PregnancyProfileStatus::HIGH_RISK->value,
        ]);

        // Both reachable from the same visit → genuinely ambiguous.
        $this->ancVisit($first, $patient, $visit, $ctx);
        $this->ancVisit($second, $patient, $visit, $ctx);

        return $this->refs($patient, $visit, $route) + [
            'pregnancy_profiles' => [$first->id, $second->id],
        ];
    }

    /** G1 — ordinary Gynaecology consultation, no pregnancy anywhere. */
    private function scenarioG1(array $ctx, string $batchId): array
    {
        [$patient, $visit, $route] = $this->patientWithRoute($ctx, 'G1');

        return $this->refs($patient, $visit, $route);
    }

    /** G2 — a persisted POSITIVE free-text pregnancy test, still unlinked. */
    private function scenarioG2(array $ctx, string $batchId): array
    {
        [$patient, $visit, $route] = $this->patientWithRoute($ctx, 'G2');

        $entry = $this->entry($route, $ctx['gynaecology'], 'sexual_sti_history', [
            'pregnancy_test' => 'Positive',
        ]);

        return $this->refs($patient, $visit, $route) + ['specialty_entry' => $entry->id];
    }

    /** G3 — saved menstrual LMP + linked profile with a NULL LMP: adoptable. */
    private function scenarioG3(array $ctx, string $batchId): array
    {
        [$patient, $visit, $route] = $this->patientWithRoute($ctx, 'G3');

        $lmp = now()->subWeeks(10)->toDateString();
        $entry = $this->entry($route, $ctx['gynaecology'], 'menstrual_history', ['lmp' => $lmp]);

        $profile = $this->profile($patient, $visit, $ctx, ['last_menstrual_period' => null]);
        $this->link($route, $profile, $ctx);

        return $this->refs($patient, $visit, $route) + [
            'pregnancy_profile' => $profile->id,
            'specialty_entry' => $entry->id,
            'consultation_lmp' => $lmp,
        ];
    }

    /** G4 — scan-dated profile: adoption must be unavailable. */
    private function scenarioG4(array $ctx, string $batchId): array
    {
        [$patient, $visit, $route] = $this->patientWithRoute($ctx, 'G4');

        $entry = $this->entry($route, $ctx['gynaecology'], 'menstrual_history', [
            'lmp' => now()->subWeeks(12)->toDateString(),
        ]);

        $profile = $this->profile($patient, $visit, $ctx, [
            'last_menstrual_period' => now()->subWeeks(14)->toDateString(),
            'dating_method' => 'early_ultrasound',
        ]);
        $this->link($route, $profile, $ctx);

        return $this->refs($patient, $visit, $route) + [
            'pregnancy_profile' => $profile->id,
            'specialty_entry' => $entry->id,
        ];
    }

    /* ── Reconciliation scenarios (R1–R5) ──────────────────────────────── */

    /** R1 — values match the linked profile exactly → safe_to_link. */
    private function scenarioR1(array $ctx, string $batchId): array
    {
        [$patient, $visit, $route] = $this->patientWithRoute($ctx, 'R1');
        $profile = $this->profile($patient, $visit, $ctx, ['gravida' => 2, 'para' => 1]);
        $this->link($route, $profile, $ctx);

        $entry = $this->entry($route, $ctx['obstetrics'], 'obstetric_history', [
            'gravida' => 2, 'para' => 1,
        ]);

        return $this->refs($patient, $visit, $route) + [
            'pregnancy_profile' => $profile->id, 'specialty_entry' => $entry->id,
        ];
    }

    /** R2 — parseable ANC values with no ANC visit yet → safe_to_migrate. */
    private function scenarioR2(array $ctx, string $batchId): array
    {
        [$patient, $visit, $route] = $this->patientWithRoute($ctx, 'R2');
        $profile = $this->profile($patient, $visit, $ctx);
        $this->link($route, $profile, $ctx);

        $entry = $this->entry($route, $ctx['obstetrics'], 'antenatal_vitals', [
            'blood_pressure' => '120/80',
            'fundal_height' => '32 cm',
        ]);

        return $this->refs($patient, $visit, $route) + [
            'pregnancy_profile' => $profile->id, 'specialty_entry' => $entry->id,
        ];
    }

    /** R3 — gravida disagrees with the profile → conflict_requires_review. */
    private function scenarioR3(array $ctx, string $batchId): array
    {
        [$patient, $visit, $route] = $this->patientWithRoute($ctx, 'R3');
        $profile = $this->profile($patient, $visit, $ctx, ['gravida' => 2, 'para' => 1]);
        $this->link($route, $profile, $ctx);

        $entry = $this->entry($route, $ctx['obstetrics'], 'obstetric_history', [
            'gravida' => 7, 'para' => 1,
        ]);

        return $this->refs($patient, $visit, $route) + [
            'pregnancy_profile' => $profile->id, 'specialty_entry' => $entry->id,
        ];
    }

    /** R4 — completed consultation, no profile at all → historical_only. */
    private function scenarioR4(array $ctx, string $batchId): array
    {
        [$patient, $visit, $route] = $this->patientWithRoute($ctx, 'R4');

        $route->forceFill([
            'status' => VisitConsultationRoute::STATUS_COMPLETED,
            'completed_at' => now()->subMonths(6),
            'completed_by' => $ctx['user']->id,
        ])->save();

        $entry = $this->entry($route, $ctx['obstetrics'], 'obstetric_history', ['gravida' => 3]);

        return $this->refs($patient, $visit, $route) + ['specialty_entry' => $entry->id];
    }

    /** R5 — an empty entry → insufficient_context. */
    private function scenarioR5(array $ctx, string $batchId): array
    {
        [$patient, $visit, $route] = $this->patientWithRoute($ctx, 'R5');
        $entry = $this->entry($route, $ctx['obstetrics'], 'obstetric_history', []);

        return $this->refs($patient, $visit, $route) + ['specialty_entry' => $entry->id];
    }

    /* ── Builders ──────────────────────────────────────────────────────── */

    /**
     * @return array{0: Patient, 1: Visit, 2: VisitConsultationRoute}
     */
    private function patientWithRoute(array $ctx, string $scenario): array
    {
        $suffix = $scenario.'-'.Str::upper(Str::random(4));

        $patient = Patient::factory()->create([
            'patient_number' => self::MARKER.$suffix,
            'first_name' => 'Pilot',
            'last_name' => 'ObGyn '.$scenario,
            'gender' => Gender::FEMALE,
            'registered_by' => $ctx['user']->id,
        ]);

        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'current_department_id' => $ctx['department']->id,
            'created_by' => $ctx['user']->id,
            'status' => VisitStatus::CONSULTING,
        ]);

        $route = VisitConsultationRoute::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'department_id' => $ctx['department']->id,
            'status' => VisitConsultationRoute::STATUS_ACTIVE,
            'routed_by' => $ctx['user']->id,
            'started_at' => now(),
            'activated_at' => now(),
        ]);

        $this->record('patients', $patient->id);
        $this->record('visits', $visit->id);
        $this->record('consultation_routes', $route->id);

        $this->manifest['urls'][$scenario] = $this->url($visit);

        return [$patient, $visit, $route];
    }

    private function profile(Patient $patient, Visit $visit, array $ctx, array $overrides = []): PregnancyProfile
    {
        $profile = PregnancyProfile::create(array_merge([
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
            'department_id' => $ctx['department']->id,
            'created_by' => $ctx['user']->id,
            'gravida' => 2,
            'para' => 1,
            'last_menstrual_period' => now()->subWeeks(20)->toDateString(),
            'estimated_due_date' => now()->addWeeks(20)->toDateString(),
            'gestational_age_weeks' => 20,
            'gestational_age_days' => 0,
            'profile_status' => PregnancyProfileStatus::ACTIVE->value,
        ], $overrides));

        $this->record('pregnancy_profiles', $profile->id);

        return $profile;
    }

    private function ancVisit(PregnancyProfile $profile, Patient $patient, Visit $visit, array $ctx): AntenatalVisit
    {
        $anc = AntenatalVisit::create([
            'pregnancy_profile_id' => $profile->id,
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
            'department_id' => $ctx['department']->id,
            'recorded_by' => $ctx['user']->id,
            'visit_date' => now()->subDays(2),
            'visit_number' => 1,
            'status' => 'recorded',
        ]);

        $this->record('antenatal_visits', $anc->id);

        return $anc;
    }

    private function entry(
        VisitConsultationRoute $route,
        ConsultationSpecialtyProfile $profile,
        string $sectionKey,
        array $values,
    ): ConsultationSpecialtyEntry {
        $entry = ConsultationSpecialtyEntry::create([
            'consultation_id' => $route->id,
            'consultation_specialty_profile_id' => $profile->id,
            'section_key' => $sectionKey,
            'entry' => $values,
            'created_by' => $route->routed_by,
        ]);

        $this->record('consultation_specialty_entries', $entry->id);

        return $entry;
    }

    private function link(VisitConsultationRoute $route, PregnancyProfile $profile, array $ctx): void
    {
        $link = app(ConsultationMaternityLinkService::class)->link(
            $route, $profile, $ctx['user'], ConsultationMaternityLinkRole::PRIMARY
        );

        $this->record('consultation_maternity_links', $link->id);
    }

    /* ── Manifest ──────────────────────────────────────────────────────── */

    private function record(string $table, int $id): void
    {
        $this->manifest['records'][$table] ??= [];

        if (! in_array($id, $this->manifest['records'][$table], true)) {
            $this->manifest['records'][$table][] = $id;
        }
    }

    /** @return array<string, mixed> */
    private function refs(Patient $patient, Visit $visit, VisitConsultationRoute $route): array
    {
        return [
            'patient' => $patient->id,
            'patient_number' => $patient->patient_number,
            'visit' => $visit->id,
            'consultation_route' => $route->id,
        ];
    }

    private function url(Visit $visit): ?string
    {
        try {
            return route('admin.consultations.show', $visit);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * The manifest carries identifiers, routes, scenario codes and timestamps
     * ONLY — no clinical narrative and no secrets.
     */
    private function writeManifest(string $batchId): void
    {
        Storage::disk(self::MANIFEST_DISK)->put(
            self::MANIFEST_DIR.'/'.$batchId.'.json',
            json_encode($this->manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}'
        );
    }

    /** @return array<string, mixed>|null */
    public function readManifest(string $batchId): ?array
    {
        $path = self::MANIFEST_DIR.'/'.$batchId.'.json';

        if (! Storage::disk(self::MANIFEST_DISK)->exists($path)) {
            return null;
        }

        $decoded = json_decode(Storage::disk(self::MANIFEST_DISK)->get($path), true);

        return is_array($decoded) ? $decoded : null;
    }

    /** @return list<string> */
    public function listBatches(): array
    {
        return collect(Storage::disk(self::MANIFEST_DISK)->files(self::MANIFEST_DIR))
            ->map(fn (string $path) => pathinfo($path, PATHINFO_FILENAME))
            ->filter(fn (string $name) => str_starts_with($name, self::MARKER))
            ->values()
            ->all();
    }
}
