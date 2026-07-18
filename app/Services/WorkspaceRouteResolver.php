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

    public function isEmergency(): bool
    {
        return $this->isType(DepartmentType::EMERGENCY);
    }

    public function isInpatient(): bool
    {
        return $this->isType(DepartmentType::INPATIENT);
    }

    public function isInvestigation(): bool
    {
        return $this->isType(DepartmentType::INVESTIGATION);
    }

    public function isPharmacy(): bool
    {
        return $this->isType(DepartmentType::PHARMACY);
    }

    public function isStores(): bool
    {
        return $this->isType(DepartmentType::STORES);
    }

    public function isConsultation(): bool
    {
        $user = $this->request->user();

        return ($user?->isConsultationUser() ?? false) || $this->isType(DepartmentType::CONSULTATION);
    }

    public function isDepartmentWorkspace(): bool
    {
        return $this->isRecords() || $this->isNursing() || $this->isEmergency() || $this->isInpatient() || $this->isInvestigation() || $this->isPharmacy() || $this->isStores() || $this->isConsultation();
    }

    public function dashboardRouteName(): string
    {
        return match (true) {
            $this->isRecords() => 'records.dashboard',
            $this->isNursing() => 'nursing.dashboard',
            $this->isEmergency() => 'emergency.dashboard',
            $this->isInpatient() => 'inpatient.dashboard',
            $this->isInvestigation() => 'investigations.dashboard',
            $this->isPharmacy() => 'pharmacy.dashboard',
            $this->isStores() => 'stores.dashboard',
            $this->isConsultation() => 'doctor.dashboard',
            default => 'admin.my-dashboard',
        };
    }

    public function routeName(string $genericRoute): string
    {
        $prefix = match (true) {
            $this->isRecords() => 'records.',
            $this->isNursing() => 'nursing.',
            $this->isEmergency() => 'emergency.',
            $this->isInpatient() => 'inpatient.',
            $this->isInvestigation() => 'investigations.',
            $this->isPharmacy() => 'pharmacy.',
            $this->isStores() => 'stores.',
            $this->isConsultation() => 'doctor.',
            default => null,
        };

        if ($prefix === null) {
            return $genericRoute;
        }

        if ($this->isInpatient()) {
            $candidate = match ($genericRoute) {
                'admin.wards.beds' => 'inpatient.beds.index',
                'admin.wards.beds.store' => 'inpatient.beds.store',
                'admin.wards.beds.update' => 'inpatient.beds.update',
                'admin.wards.beds.status' => 'inpatient.beds.status',
                'admin.wards.bed-map' => 'inpatient.beds.availability',
                'admin.wards.consumables.index' => 'inpatient.consumables.index',
                'admin.journey.worklist' => 'inpatient.handoffs.index',
                'admin.journey.worklist.refresh' => 'inpatient.handoffs.refresh',
                default => Str::startsWith($genericRoute, 'admin.consultations.')
                    ? 'inpatient.sessions.'.Str::after($genericRoute, 'admin.consultations.')
                    : (Str::startsWith($genericRoute, 'admin.')
                        ? 'inpatient.'.Str::after($genericRoute, 'admin.')
                        : 'inpatient.'.$genericRoute),
            };
        } elseif ($this->isInvestigation()) {
            $candidate = match ($genericRoute) {
                'admin.journey.worklist' => 'investigations.handoffs.index',
                'admin.journey.worklist.refresh' => 'investigations.handoffs.refresh',
                default => Str::startsWith($genericRoute, 'admin.investigations.')
                    // admin.investigations.items/stock map to investigations.items/stock
                    // (the generic fallback would double the "investigations." prefix).
                    ? 'investigations.'.Str::after($genericRoute, 'admin.investigations.')
                    : (Str::startsWith($genericRoute, 'admin.')
                        ? 'investigations.'.Str::after($genericRoute, 'admin.')
                        : 'investigations.'.$genericRoute),
            };
        } elseif ($this->isPharmacy()) {
            $candidate = match ($genericRoute) {
                'admin.journey.worklist' => 'pharmacy.handoffs.index',
                'admin.journey.worklist.refresh' => 'pharmacy.handoffs.refresh',
                default => Str::startsWith($genericRoute, 'admin.pharmacy.')
                    // admin.pharmacy.dispensing/drugs/history map straight to
                    // pharmacy.* (the generic fallback would double the prefix).
                    ? 'pharmacy.'.Str::after($genericRoute, 'admin.pharmacy.')
                    : (Str::startsWith($genericRoute, 'admin.')
                        ? 'pharmacy.'.Str::after($genericRoute, 'admin.')
                        : 'pharmacy.'.$genericRoute),
            };
        } elseif ($this->isStores()) {
            $candidate = match ($genericRoute) {
                'admin.journey.worklist' => 'stores.handoffs.index',
                'admin.journey.worklist.refresh' => 'stores.handoffs.refresh',
                default => Str::startsWith($genericRoute, 'admin.store.')
                    // admin.store.* maps straight to stores.* (the generic
                    // fallback would produce stores.store.*).
                    ? 'stores.'.Str::after($genericRoute, 'admin.store.')
                    : (Str::startsWith($genericRoute, 'admin.')
                        ? 'stores.'.Str::after($genericRoute, 'admin.')
                        : 'stores.'.$genericRoute),
            };
        } elseif ($this->isEmergency() && Str::startsWith($genericRoute, 'admin.emergency.')) {
            $candidate = match ($genericRoute) {
                'admin.emergency.triage.show' => 'emergency.case-triage.show',
                default => 'emergency.'.Str::after($genericRoute, 'admin.emergency.'),
            };
        } else {
            $candidate = Str::startsWith($genericRoute, 'admin.')
                ? Str::after($genericRoute, 'admin.')
                : $genericRoute;
            $candidate = $prefix.$candidate;
        }

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
        return match (true) {
            $this->isNursing() => route('nursing.opd.queue'),
            $this->isEmergency() => route('emergency.queue.index'),
            $this->isInpatient() => route('inpatient.admissions.active'),
            default => $this->visitIndex(),
        };
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
        return match (true) {
            $this->isNursing() && Route::has('nursing.tasks.index') => route('nursing.tasks.index'),
            $this->isInpatient() && Route::has('inpatient.tasks.index') => route('inpatient.tasks.index'),
            default => $this->dashboard(),
        };
    }

    public function treatmentIndex(): string
    {
        return match (true) {
            $this->isNursing() && Route::has('nursing.treatments.index') => route('nursing.treatments.index'),
            $this->isInpatient() && Route::has('inpatient.treatments.index') => route('inpatient.treatments.index'),
            default => $this->dashboard(),
        };
    }

    public function handoffIndex(): string
    {
        return match (true) {
            $this->isNursing() => route('nursing.handoffs.index'),
            $this->isEmergency() => route('emergency.journey.worklist'),
            $this->isInpatient() => route('inpatient.handoffs.index'),
            $this->isInvestigation() => route('investigations.handoffs.index'),
            $this->isPharmacy() => route('pharmacy.handoffs.index'),
            $this->isStores() => route('stores.handoffs.index'),
            default => $this->route('admin.journey.worklist'),
        };
    }

    public function handoffRouteName(string $action = 'index'): string
    {
        if ($this->isNursing()) {
            return $action === 'refresh' ? 'nursing.handoffs.refresh' : 'nursing.handoffs.'.$action;
        }

        if ($this->isEmergency()) {
            return $action === 'refresh' ? 'emergency.journey.worklist.refresh' : 'emergency.journey.worklist';
        }

        if ($this->isInpatient()) {
            return $action === 'refresh' ? 'inpatient.handoffs.refresh' : ($action === 'index'
                ? 'inpatient.handoffs.index'
                : 'inpatient.handoffs.'.$action);
        }

        if ($this->isInvestigation()) {
            $candidate = 'investigations.handoffs.'.$action;

            return Route::has($candidate) ? $candidate : ($action === 'refresh'
                ? 'admin.journey.worklist.refresh'
                : 'admin.journey.handoffs.'.$action);
        }

        if ($this->isPharmacy()) {
            $candidate = 'pharmacy.handoffs.'.$action;

            return Route::has($candidate) ? $candidate : ($action === 'refresh'
                ? 'admin.journey.worklist.refresh'
                : 'admin.journey.handoffs.'.$action);
        }

        if ($this->isStores()) {
            $candidate = 'stores.handoffs.'.$action;

            return Route::has($candidate) ? $candidate : ($action === 'refresh'
                ? 'admin.journey.worklist.refresh'
                : 'admin.journey.handoffs.'.$action);
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

        if ($this->isEmergency()) {
            return [
                'workspaceKey' => 'emergency',
                'workspaceRoutePrefix' => 'emergency.',
                'workspaceTitle' => __('emergency.workspace.title'),
                'workspaceDepartment' => $this->departments->currentDepartment($this->request->user(), $this->request),
                'workspaceScope' => 'emergency',
                'breadcrumbs' => $this->breadcrumbs(),
            ];
        }

        if ($this->isInpatient()) {
            return [
                'workspaceKey' => 'inpatient',
                'workspaceRoutePrefix' => 'inpatient.',
                'workspaceTitle' => __('inpatient.workspace.title'),
                'workspaceDepartment' => $this->departments->currentDepartment($this->request->user(), $this->request),
                'workspaceScope' => 'normal_admission',
                'breadcrumbs' => $this->breadcrumbs(),
            ];
        }

        if ($this->isInvestigation()) {
            return [
                'workspaceKey' => 'investigations',
                'workspaceRoutePrefix' => 'investigations.',
                'workspaceTitle' => __('investigations.workspace.title'),
                'workspaceDepartment' => $this->departments->currentDepartment($this->request->user(), $this->request),
                'workspaceScope' => 'investigation',
                'breadcrumbs' => $this->breadcrumbs(),
            ];
        }

        if ($this->isPharmacy()) {
            return [
                'workspaceKey' => 'pharmacy',
                'workspaceRoutePrefix' => 'pharmacy.',
                'workspaceTitle' => __('pharmacy.workspace.title'),
                'workspaceDepartment' => $this->departments->currentDepartment($this->request->user(), $this->request),
                'workspaceScope' => 'medication_fulfilment',
                'breadcrumbs' => $this->breadcrumbs(),
            ];
        }

        if ($this->isStores()) {
            return [
                'workspaceKey' => 'stores',
                'workspaceRoutePrefix' => 'stores.',
                'workspaceTitle' => __('stores.workspace.title'),
                'workspaceDepartment' => $this->departments->currentDepartment($this->request->user(), $this->request),
                'workspaceScope' => 'inventory_operations',
                'breadcrumbs' => $this->breadcrumbs(),
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

        if ($this->isEmergency()) {
            return $this->emergencyBreadcrumbs();
        }

        if ($this->isInpatient()) {
            return $this->inpatientBreadcrumbs();
        }

        if ($this->isInvestigation()) {
            return $this->investigationsBreadcrumbs();
        }

        if ($this->isPharmacy()) {
            return $this->pharmacyBreadcrumbs();
        }

        if ($this->isStores()) {
            return $this->storesBreadcrumbs();
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

    /** @return list<array{label:string,url:?string}> */
    private function emergencyBreadcrumbs(): array
    {
        $name = (string) $this->request->route()?->getName();
        $crumbs = [[
            'label' => __('emergency.breadcrumbs.emergency'),
            'url' => in_array($name, ['emergency.dashboard', 'emergency.dashboard.expanded', 'emergency.board'], true)
                ? null
                : route('emergency.dashboard'),
        ]];

        $resource = match (true) {
            Str::startsWith($name, 'emergency.queue') => ['queue', 'emergency.queue.index'],
            Str::startsWith($name, 'emergency.patients') => ['patients', 'emergency.patients.index'],
            Str::startsWith($name, 'emergency.visits') => ['visits', 'emergency.visits.index'],
            Str::startsWith($name, 'emergency.cases'), Str::startsWith($name, 'emergency.resuscitation'), Str::startsWith($name, 'emergency.observations') => ['cases', 'emergency.cases.index'],
            Str::startsWith($name, 'emergency.triage'), Str::startsWith($name, 'emergency.vitals') => ['triage', 'emergency.triage.index'],
            Str::startsWith($name, 'emergency.consultations') => ['consultations', 'emergency.consultations.index'],
            Str::startsWith($name, 'emergency.medications'), Str::startsWith($name, 'emergency.medication-board'), Str::startsWith($name, 'emergency.mar-chart') => ['medications', 'emergency.medications.index'],
            Str::startsWith($name, 'emergency.lab.requests'), Str::startsWith($name, 'emergency.investigations') => ['investigations', 'emergency.lab.requests.index'],
            Str::startsWith($name, 'emergency.theatre'), Str::startsWith($name, 'emergency.procedures') => ['procedures', 'emergency.theatre.index'],
            Str::startsWith($name, 'emergency.admissions') => ['admissions', 'emergency.admissions.index'],
            Str::startsWith($name, 'emergency.journey') => ['handoffs', 'emergency.journey.worklist'],
            Str::startsWith($name, 'emergency.bays') => ['bays', 'emergency.bays.index'],
            Str::startsWith($name, 'emergency.consumables') => ['consumables', 'emergency.consumables.index'],
            Str::startsWith($name, 'emergency.reports') => ['reports', 'emergency.reports.index'],
            default => null,
        };

        if (! $resource) {
            return $crumbs;
        }

        [$key, $indexRoute] = $resource;
        $isIndex = $name === $indexRoute || in_array($name, [
            'emergency.dashboard', 'emergency.dashboard.expanded', 'emergency.board',
            'emergency.queue.critical', 'emergency.queue.resuscitation', 'emergency.queue.urgent',
            'emergency.queue.observation', 'emergency.queue.awaiting-disposition',
        ], true);

        $crumbs[] = [
            'label' => __('emergency.breadcrumbs.'.$key),
            'url' => $isIndex || ! Route::has($indexRoute) ? null : route($indexRoute),
        ];

        if (! $isIndex) {
            $action = Str::afterLast($name, '.');
            $crumbs[] = ['label' => __('emergency.breadcrumbs.'.match ($action) {
                'create' => 'create',
                'edit' => 'edit',
                'history' => 'history',
                default => 'details',
            }), 'url' => null];
        }

        return $crumbs;
    }

    /** @return list<array{label:string,url:?string}> */
    private function investigationsBreadcrumbs(): array
    {
        $name = (string) $this->request->route()?->getName();
        $crumbs = [[
            'label' => __('investigations.breadcrumbs.investigations'),
            'url' => in_array($name, ['investigations.dashboard', 'investigations.dashboard.redirect'], true)
                ? null
                : route('investigations.dashboard'),
        ]];

        $resource = match (true) {
            Str::startsWith($name, 'investigations.lab.requests') => ['requests', 'investigations.lab.requests.index'],
            Str::startsWith($name, 'investigations.lab.samples') => ['specimens', 'investigations.lab.samples.index'],
            Str::startsWith($name, 'investigations.lab.results') => ['results', 'investigations.lab.results.index'],
            Str::startsWith($name, 'investigations.lab.tests') => ['tests', 'investigations.lab.tests.index'],
            Str::startsWith($name, 'investigations.investigation-catalogue') => ['catalogue', 'investigations.investigation-catalogue.index'],
            Str::startsWith($name, 'investigations.items') => ['items', 'investigations.items.index'],
            Str::startsWith($name, 'investigations.stock') => ['stock', 'investigations.stock.index'],
            Str::startsWith($name, 'investigations.patients') => ['patients', 'investigations.patients.index'],
            Str::startsWith($name, 'investigations.handoffs') => ['handoffs', 'investigations.handoffs.index'],
            Str::startsWith($name, 'investigations.reports') => ['reports', 'investigations.reports.index'],
            default => null,
        };

        if (! $resource) {
            return $crumbs;
        }

        [$key, $indexRoute] = $resource;
        $isIndex = $name === $indexRoute;
        $crumbs[] = [
            'label' => __('investigations.breadcrumbs.'.$key),
            'url' => $isIndex || ! Route::has($indexRoute) ? null : route($indexRoute),
        ];

        if (! $isIndex) {
            $action = Str::afterLast($name, '.');
            $crumbs[] = ['label' => __('investigations.breadcrumbs.'.match ($action) {
                'create' => 'create',
                'edit' => 'edit',
                default => 'details',
            }), 'url' => null];
        }

        return $crumbs;
    }

    /** @return list<array{label:string,url:?string}> */
    private function pharmacyBreadcrumbs(): array
    {
        $name = (string) $this->request->route()?->getName();
        $crumbs = [[
            'label' => __('pharmacy.breadcrumbs.pharmacy'),
            'url' => in_array($name, ['pharmacy.dashboard', 'pharmacy.dashboard.redirect'], true)
                ? null
                : route('pharmacy.dashboard'),
        ]];

        $resource = match (true) {
            Str::startsWith($name, 'pharmacy.prescriptions') => ['prescriptions', 'pharmacy.prescriptions.index'],
            Str::startsWith($name, 'pharmacy.dispensing') => ['dispensing', 'pharmacy.dispensing.index'],
            $name === 'pharmacy.history' => ['history', 'pharmacy.history'],
            Str::startsWith($name, 'pharmacy.drugs') => ['drugs', 'pharmacy.drugs.index'],
            Str::startsWith($name, 'pharmacy.product-stock') => ['stock', 'pharmacy.product-stock.balances'],
            Str::startsWith($name, 'pharmacy.patients') => ['patients', 'pharmacy.patients.index'],
            Str::startsWith($name, 'pharmacy.handoffs') => ['handoffs', 'pharmacy.handoffs.index'],
            Str::startsWith($name, 'pharmacy.reports') => ['reports', 'pharmacy.reports.index'],
            default => null,
        };

        if (! $resource) {
            return $crumbs;
        }

        [$key, $indexRoute] = $resource;
        $isIndex = $name === $indexRoute;
        $crumbs[] = [
            'label' => __('pharmacy.breadcrumbs.'.$key),
            'url' => $isIndex || ! Route::has($indexRoute) ? null : route($indexRoute),
        ];

        if (! $isIndex) {
            $action = Str::afterLast($name, '.');
            $crumbs[] = ['label' => __('pharmacy.breadcrumbs.'.match ($action) {
                'create' => 'create',
                'edit' => 'edit',
                default => 'details',
            }), 'url' => null];
        }

        return $crumbs;
    }

    /** @return list<array{label:string,url:?string}> */
    private function storesBreadcrumbs(): array
    {
        $name = (string) $this->request->route()?->getName();
        $crumbs = [[
            'label' => __('stores.breadcrumbs.stores'),
            'url' => in_array($name, ['stores.dashboard', 'stores.dashboard.redirect'], true)
                ? null
                : route('stores.dashboard'),
        ]];

        $resource = match (true) {
            Str::startsWith($name, 'stores.stock-requisitions') => ['requisitions', 'stores.stock-requisitions.index'],
            Str::startsWith($name, 'stores.purchase-orders') => ['purchase_orders', 'stores.purchase-orders.index'],
            Str::startsWith($name, 'stores.purchase-returns') => ['purchase_returns', 'stores.purchase-returns.index'],
            Str::startsWith($name, 'stores.suppliers') => ['suppliers', 'stores.suppliers.index'],
            Str::startsWith($name, 'stores.stock.adjustments') => ['adjustments', 'stores.stock.adjustments.index'],
            Str::startsWith($name, 'stores.stock.returns') => ['returns', 'stores.stock.returns.index'],
            Str::startsWith($name, 'stores.stock.transfers') => ['transfers', 'stores.stock.transfers.index'],
            Str::startsWith($name, 'stores.stock.valuation') => ['valuation', 'stores.stock.valuation'],
            Str::startsWith($name, 'stores.stock.ledger') => ['ledger', 'stores.stock.ledger'],
            Str::startsWith($name, 'stores.stock') => ['stock', 'stores.stock.balances'],
            Str::startsWith($name, 'stores.products') => ['products', 'stores.products.index'],
            Str::startsWith($name, 'stores.handoffs') => ['handoffs', 'stores.handoffs.index'],
            Str::startsWith($name, 'stores.reports') => ['reports', 'stores.reports.index'],
            default => null,
        };

        if (! $resource) {
            return $crumbs;
        }

        [$key, $indexRoute] = $resource;
        $isIndex = $name === $indexRoute;
        $crumbs[] = [
            'label' => __('stores.breadcrumbs.'.$key),
            'url' => $isIndex || ! Route::has($indexRoute) ? null : route($indexRoute),
        ];

        if (! $isIndex) {
            $action = Str::afterLast($name, '.');
            $crumbs[] = ['label' => __('stores.breadcrumbs.'.match ($action) {
                'create' => 'create',
                'edit' => 'edit',
                default => 'details',
            }), 'url' => null];
        }

        return $crumbs;
    }

    /** @return list<array{label:string,url:?string}> */
    private function inpatientBreadcrumbs(): array
    {
        $name = (string) $this->request->route()?->getName();
        $crumbs = [[
            'label' => __('inpatient.breadcrumbs.inpatient'),
            'url' => in_array($name, ['inpatient.dashboard', 'inpatient.dashboard.redirect'], true)
                ? null
                : route('inpatient.dashboard'),
        ]];

        $resource = match (true) {
            Str::startsWith($name, 'inpatient.admissions') => ['admissions', 'inpatient.admissions.index'],
            Str::startsWith($name, 'inpatient.wards') => ['wards', 'inpatient.wards.index'],
            Str::startsWith($name, 'inpatient.beds') => ['beds', 'inpatient.beds.index'],
            Str::startsWith($name, 'inpatient.patients') => ['patients', 'inpatient.patients.index'],
            Str::startsWith($name, 'inpatient.visits') => ['visits', 'inpatient.visits.index'],
            Str::startsWith($name, 'inpatient.rounds') => ['rounds', 'inpatient.rounds.index'],
            Str::startsWith($name, 'inpatient.sessions'), Str::startsWith($name, 'inpatient.consultations') => ['sessions', 'inpatient.sessions.index'],
            Str::startsWith($name, 'inpatient.vitals') => ['vitals', 'inpatient.vitals.index'],
            Str::startsWith($name, 'inpatient.tasks') => ['tasks', 'inpatient.tasks.index'],
            Str::startsWith($name, 'inpatient.medications'), Str::startsWith($name, 'inpatient.mar-chart') => ['medications', 'inpatient.medications.index'],
            Str::startsWith($name, 'inpatient.treatments') => ['treatments', 'inpatient.treatments.index'],
            Str::startsWith($name, 'inpatient.procedures'), Str::startsWith($name, 'inpatient.theatre') => ['procedures', 'inpatient.procedures.index'],
            Str::startsWith($name, 'inpatient.investigations'), Str::startsWith($name, 'inpatient.lab') => ['investigations', 'inpatient.investigations.index'],
            Str::startsWith($name, 'inpatient.handoffs') => ['handoffs', 'inpatient.handoffs.index'],
            Str::startsWith($name, 'inpatient.transfers') => ['transfers', 'inpatient.transfers.index'],
            Str::startsWith($name, 'inpatient.discharges') => ['discharges', 'inpatient.discharges.index'],
            Str::startsWith($name, 'inpatient.readmissions') => ['readmissions', 'inpatient.readmissions.index'],
            Str::startsWith($name, 'inpatient.reports') => ['reports', 'inpatient.reports.index'],
            default => null,
        };

        if (! $resource) {
            return $crumbs;
        }

        [$key, $indexRoute] = $resource;
        $isIndex = $name === $indexRoute || in_array($name, [
            'inpatient.admissions.pending', 'inpatient.admissions.active', 'inpatient.admissions.discharged',
            'inpatient.beds.availability', 'inpatient.discharges.readiness', 'inpatient.readmissions.index',
        ], true);

        $crumbs[] = [
            'label' => __('inpatient.breadcrumbs.'.$key),
            'url' => $isIndex || ! Route::has($indexRoute) ? null : route($indexRoute),
        ];

        if (! $isIndex) {
            $action = Str::afterLast($name, '.');
            $crumbs[] = ['label' => __('inpatient.breadcrumbs.'.match ($action) {
                'create' => 'create',
                'edit' => 'edit',
                default => 'details',
            }), 'url' => null];
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
