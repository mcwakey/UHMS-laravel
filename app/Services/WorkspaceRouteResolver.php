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
        $user = $this->request->user();
        if (! $user) {
            return false;
        }

        $type = $this->departments->currentDepartment($user, $this->request)?->type;

        return $type instanceof DepartmentType
            ? $type === DepartmentType::RECORDS
            : (string) $type === DepartmentType::RECORDS->value;
    }

    public function dashboardRouteName(): string
    {
        return $this->isRecords() ? 'records.dashboard' : 'admin.my-dashboard';
    }

    public function routeName(string $genericRoute): string
    {
        if (! $this->isRecords()) {
            return $genericRoute;
        }

        $candidate = Str::startsWith($genericRoute, 'admin.')
            ? Str::after($genericRoute, 'admin.')
            : $genericRoute;
        $candidate = 'records.'.$candidate;

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

    /** @return array<string, mixed> */
    public function viewContext(): array
    {
        if (! $this->isRecords()) {
            return [
                'workspaceKey' => 'generic',
                'workspaceRoutePrefix' => 'admin.',
                'breadcrumbs' => null,
            ];
        }

        return [
            'workspaceKey' => 'records',
            'workspaceRoutePrefix' => 'records.',
            'workspaceTitle' => __('records.workspace.title'),
            'workspaceDepartment' => $this->departments->currentDepartment($this->request->user(), $this->request),
            'breadcrumbs' => $this->breadcrumbs(),
        ];
    }

    /** @return list<array{label:string,url:?string}> */
    public function breadcrumbs(): array
    {
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
}
