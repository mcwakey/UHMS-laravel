<?php

namespace App\Services;

use App\Enums\DepartmentType;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Visit;
use App\Services\Department\DepartmentContextSwitcherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Resolves browser URLs for the currently active department workspace.
 * Domain services stay route-agnostic; controllers and shared views use this
 * service at their browser boundary.
 */
class WorkspaceRouteResolver
{
    public function __construct(
        private DepartmentContextSwitcherService $departments,
        private Request $request,
    ) {}

    public function isRecords(): bool
    {
        return $this->isType(DepartmentType::RECORDS);
    }

    public function isNursing(): bool
    {
        return $this->isType(DepartmentType::NURSING);
    }

    public function isConsultation(): bool
    {
        $user = $this->request->user();

        return ($user?->isConsultationUser() ?? false) || $this->isType(DepartmentType::CONSULTATION);
    }

    public function isDepartmentWorkspace(): bool
    {
        return $this->isRecords() || $this->isNursing() || $this->isConsultation();
    }

    public function dashboardRouteName(): string
    {
        return match (true) {
            $this->isRecords() => 'records.dashboard',
            $this->isNursing() => 'nursing.dashboard',
            $this->isConsultation() => 'doctor.dashboard',
            default => 'admin.my-dashboard',
        };
    }

    public function routeName(string $genericRoute): string
    {
        $prefix = match (true) {
            $this->isRecords() => 'records.',
            $this->isNursing() => 'nursing.',
            $this->isConsultation() => 'doctor.',
            default => null,
        };

        if ($prefix === null) {
            return $genericRoute;
        }

        $candidate = Str::startsWith($genericRoute, 'admin.')
            ? Str::after($genericRoute, 'admin.')
            : $genericRoute;
        $candidate = $prefix.$candidate;

        return Route::has($candidate) ? $candidate : $genericRoute;
    }

    public function route(string $genericRoute, mixed $parameters = [], bool $absolute = true): string
    {
        return route($this->routeName($genericRoute), $parameters, $absolute);
    }

    public function dashboard(): string
    {
        return route($this->dashboardRouteName());
    }

    public function patientIndex(): string
    {
        return $this->route('admin.patients.index');
    }

    public function patientCreate(): string
    {
        return $this->route('admin.patients.create');
    }

    public function patientShow(Patient $patient): string
    {
        return $this->route('admin.patients.show', $patient);
    }

    public function patientEdit(Patient $patient): string
    {
        return $this->route('admin.patients.edit', $patient);
    }

    public function visitIndex(): string
    {
        return $this->route('admin.visits.index');
    }

    public function visitCreate(array $parameters = []): string
    {
        return $this->route('admin.visits.create', $parameters);
    }

    public function visitShow(Visit $visit): string
    {
        return $this->route('admin.visits.show', $visit);
    }

    public function visitEdit(Visit $visit): string
    {
        return $this->route('admin.visits.edit', $visit);
    }

    public function appointmentIndex(): string
    {
        return $this->route('admin.appointments.index');
    }

    public function appointmentShow(Appointment $appointment): string
    {
        return $this->route('admin.appointments.show', $appointment);
    }

    public function opdQueue(): string
    {
        return $this->isNursing() ? route('nursing.opd.queue') : $this->visitIndex();
    }

    public function opdVisitShow(Visit $visit): string
    {
        return $this->isNursing() ? route('nursing.opd.show', $visit) : $this->visitShow($visit);
    }

    public function visitActionUrl(int $visitId, string $fallback): string
    {
        return $this->isNursing() ? route('nursing.opd.show', $visitId) : $fallback;
    }

    public function taskIndex(): string
    {
        return $this->isNursing() ? route('nursing.tasks.index') : $this->dashboard();
    }

    public function treatmentIndex(): string
    {
        return $this->isNursing() ? route('nursing.treatments.index') : $this->dashboard();
    }

    public function handoffIndex(): string
    {
        return $this->isNursing() ? route('nursing.handoffs.index') : $this->route('admin.journey.worklist');
    }

    public function handoffRouteName(string $action = 'index'): string
    {
        if ($this->isNursing()) {
            return $action === 'refresh' ? 'nursing.handoffs.refresh' : 'nursing.handoffs.'.$action;
        }

        return $action === 'index' ? 'admin.journey.worklist' : ($action === 'refresh'
            ? 'admin.journey.worklist.refresh'
            : 'admin.journey.handoffs.'.$action);
    }

    /** @return array<string, mixed> */
    public function viewContext(): array
    {
        if (! $this->isDepartmentWorkspace()) {
            return [
                'workspaceKey' => 'generic',
                'workspaceRoutePrefix' => 'admin.',
                'breadcrumbs' => null,
            ];
        }

        if ($this->isConsultation()) {
            return [
                'workspaceKey' => 'doctor',
                'workspaceRoutePrefix' => 'doctor.',
                'workspaceTitle' => __('doctor.workspace.title'),
                'workspaceDepartment' => $this->departments->currentDepartment($this->request->user(), $this->request),
                'workspaceScope' => 'consultation',
                'breadcrumbs' => $this->breadcrumbs(),
            ];
        }

        $key = $this->isNursing() ? 'nursing' : 'records';

        return [
            'workspaceKey' => $key,
            'workspaceRoutePrefix' => $key.'.',
            'workspaceTitle' => __($key.'.workspace.title'),
            'workspaceDepartment' => $this->departments->currentDepartment($this->request->user(), $this->request),
            'workspaceScope' => $key === 'nursing' ? 'opd' : null,
            'breadcrumbs' => $this->breadcrumbs(),
        ];
    }

    /** @return list<array{label:string,url:?string}> */
    public function breadcrumbs(): array
    {
        if ($this->isNursing()) {
            return $this->nursingBreadcrumbs();
        }

        if ($this->isConsultation()) {
            return $this->doctorBreadcrumbs();
        }

        $name = (string) $this->request->route()?->getName();
        $crumbs = [[
            'label' => __('records.breadcrumbs.records'),
            'url' => $name === 'records.dashboard' ? null : route('records.dashboard'),
        ]];

        $resource = match (true) {
            Str::startsWith($name, 'records.patients') => ['patients', 'records.patients.index'],
            Str::startsWith($name, 'records.visits') => ['visits', 'records.visits.index'],
            Str::startsWith($name, 'records.appointments') => ['appointments', 'records.appointments.index'],
            Str::startsWith($name, 'records.front-desk') => ['front_desk', 'records.front-desk.index'],
            Str::startsWith($name, 'records.triage'), Str::startsWith($name, 'records.vitals') => ['triage', 'records.triage.index'],
            Str::startsWith($name, 'records.service-renderings') => ['service_rendering', 'records.service-renderings.index'],
            Str::startsWith($name, 'records.claims') => ['claims', 'records.claims.index'],
            Str::startsWith($name, 'records.reports') => ['reports', 'records.reports.index'],
            default => null,
        };

        if (! $resource) {
            return $crumbs;
        }

        [$key, $indexRoute] = $resource;
        $isIndex = $name === $indexRoute;
        $crumbs[] = [
            'label' => __('records.breadcrumbs.'.$key),
            'url' => $isIndex ? null : route($indexRoute),
        ];

        if (! $isIndex) {
            $action = Str::afterLast($name, '.');
            $detailKey = match ($action) {
                'create' => 'register_'.$key,
                'show' => Str::singular($key).'_details',
                'edit' => 'edit_'.Str::singular($key),
                'patients' => 'patient_report',
                'visits' => 'visit_report',
                'attendance' => 'attendance_report',
                'claims' => 'claims_report',
                'insurance-claims' => 'insurance_claims_report',
                'daily-collection' => 'daily_collection_report',
                default => null,
            };
            if ($detailKey) {
                $crumbs[] = ['label' => __('records.breadcrumbs.'.$detailKey), 'url' => null];
            }
        }

        return $crumbs;
    }

    /** @return list<array{label:string,url:?string}> */
    private function nursingBreadcrumbs(): array
    {
        $name = (string) $this->request->route()?->getName();
        $crumbs = [[
            'label' => __('nursing.breadcrumbs.nursing'),
            'url' => $name === 'nursing.dashboard' ? null : route('nursing.dashboard'),
        ]];

        $resource = match (true) {
            Str::startsWith($name, 'nursing.opd') => ['opd_queue', 'nursing.opd.queue'],
            Str::startsWith($name, 'nursing.patients') => ['patients', 'nursing.patients.index'],
            Str::startsWith($name, 'nursing.visits') => ['visits', 'nursing.visits.index'],
            Str::startsWith($name, 'nursing.triage') => ['triage', 'nursing.triage.index'],
            Str::startsWith($name, 'nursing.vitals') => ['vitals', 'nursing.vitals.create'],
            Str::startsWith($name, 'nursing.tasks') => ['tasks', 'nursing.tasks.index'],
            Str::startsWith($name, 'nursing.treatments') => ['treatments', 'nursing.treatments.index'],
            Str::startsWith($name, 'nursing.consultations') => ['consultations', 'nursing.consultations.index'],
            Str::startsWith($name, 'nursing.service-renderings') => ['service_renderings', 'nursing.service-renderings.index'],
            Str::startsWith($name, 'nursing.handoffs') => ['handoffs', 'nursing.handoffs.index'],
            Str::startsWith($name, 'nursing.reports') => ['reports', 'nursing.reports.index'],
            default => null,
        };

        if (! $resource) {
            return $crumbs;
        }

        [$key, $indexRoute] = $resource;
        $isIndex = $name === $indexRoute || ($key === 'opd_queue' && in_array($name, [
            'nursing.opd.index', 'nursing.opd.active', 'nursing.opd.completed',
        ], true));
        $crumbs[] = ['label' => __('nursing.breadcrumbs.'.$key), 'url' => $isIndex ? null : route($indexRoute)];

        if (! $isIndex) {
            $crumbs[] = ['label' => __('nursing.breadcrumbs.details'), 'url' => null];
        }

        return $crumbs;
    }

    /** @return list<array{label:string,url:?string}> */
    private function doctorBreadcrumbs(): array
    {
        $name = (string) $this->request->route()?->getName();
        $crumbs = [[
            'label' => __('doctor.breadcrumbs.doctor'),
            'url' => $name === 'doctor.dashboard' ? null : route('doctor.dashboard'),
        ]];

        $resource = match (true) {
            Str::startsWith($name, 'doctor.patients'), Str::startsWith($name, 'admin.patients') => ['patients', 'doctor.patients.index'],
            Str::startsWith($name, 'doctor.appointments'), Str::startsWith($name, 'admin.appointments') => ['appointments', 'doctor.appointments.index'],
            Str::startsWith($name, 'doctor.visits'), Str::startsWith($name, 'admin.visits') => ['visits', 'doctor.visits.index'],
            Str::startsWith($name, 'doctor.consultations'), Str::startsWith($name, 'admin.consultations') => ['consultations', 'doctor.consultations.index'],
            Str::startsWith($name, 'doctor.admissions'), Str::startsWith($name, 'admin.admissions') => ['admissions', 'doctor.admissions.index'],
            Str::startsWith($name, 'doctor.prescriptions'), Str::startsWith($name, 'admin.prescriptions') => ['prescriptions', 'doctor.prescriptions.index'],
            Str::startsWith($name, 'doctor.lab.requests'), Str::startsWith($name, 'admin.lab.requests') => ['investigation_requests', 'doctor.lab.requests.index'],
            Str::startsWith($name, 'doctor.lab.results'), Str::startsWith($name, 'admin.lab.results') => ['investigation_results', 'doctor.lab.results.index'],
            Str::startsWith($name, 'doctor.theatre'), Str::startsWith($name, 'admin.theatre') => ['procedures', 'doctor.theatre.index'],
            Str::startsWith($name, 'doctor.journey'), Str::startsWith($name, 'admin.journey') => ['handoffs', 'doctor.journey.worklist'],
            Str::startsWith($name, 'doctor.icd-codes'), Str::startsWith($name, 'admin.icd-codes') => ['icd_codes', 'doctor.icd-codes.index'],
            Str::startsWith($name, 'doctor.procedure-catalogue'), Str::startsWith($name, 'admin.procedure-catalogue') => ['procedure_catalogue', 'doctor.procedure-catalogue.index'],
            Str::startsWith($name, 'doctor.investigation-catalogue'), Str::startsWith($name, 'admin.investigation-catalogue') => ['investigation_catalogue', 'doctor.investigation-catalogue.index'],
            Str::startsWith($name, 'doctor.patterns'), Str::startsWith($name, 'admin.patterns') => ['patterns', 'doctor.patterns.index'],
            Str::startsWith($name, 'doctor.reports'), Str::startsWith($name, 'admin.reports') => ['reports', 'doctor.reports.visits'],
            default => null,
        };

        if (! $resource) {
            return $crumbs;
        }

        [$key, $indexRoute] = $resource;
        $isIndex = $name === $indexRoute || Str::endsWith($name, '.index');
        $crumbs[] = [
            'label' => __('doctor.breadcrumbs.'.$key),
            'url' => $isIndex || ! Route::has($indexRoute) ? null : route($indexRoute),
        ];

        if (! $isIndex) {
            $action = Str::afterLast($name, '.');
            $detailKey = match ($action) {
                'create' => 'create',
                'edit' => 'edit',
                'calendar' => 'calendar',
                'history' => 'history',
                default => 'details',
            };

            $crumbs[] = ['label' => __('doctor.breadcrumbs.'.$detailKey), 'url' => null];
        }

        return $crumbs;
    }

    private function isType(DepartmentType $expected): bool
    {
        $user = $this->request->user();
        if (! $user) {
            return false;
        }

        $type = $this->departments->currentDepartment($user, $this->request)?->type;

        return $type instanceof DepartmentType
            ? $type === $expected
            : (string) $type === $expected->value;
    }
}
