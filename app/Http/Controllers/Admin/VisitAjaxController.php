<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Patient;
use App\Models\PatientInsurance;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Services\InsuranceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VisitAjaxController extends Controller
{
    public function __construct(
        protected InsuranceService $insuranceService,
    ) {}

    /**
     * GET /admin/visits/ajax/departments/{department}/services
     *
     * Returns active services for a department, with specialty info.
     */
    public function servicesByDepartment(Department $department): JsonResponse
    {
        $services = $department->activeServices()
            ->with('specialties:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'category', 'price', 'nhis_price', 'is_nhis_covered', 'department_id']);

        return response()->json($services->map(fn ($s) => [
            'id'              => $s->id,
            'name'            => $s->name,
            'code'            => $s->code,
            'category'        => $s->category,
            'price'           => (float) $s->price,
            'formatted_price' => '₵' . number_format($s->price, 2),
            'specialty_ids'   => $s->specialties->pluck('id')->toArray(),
            'specialty_names' => $s->specialties->pluck('name')->implode(', '),
        ]));
    }

    /**
     * GET /admin/visits/ajax/doctors-by-services?service_ids[]=1&service_ids[]=2
     *
     * Returns doctors whose specialties overlap with the given services.
     * Falls back to all active doctors if no specialty links exist.
     */
    public function doctorsByServices(Request $request): JsonResponse
    {
        $serviceIds = array_filter((array) $request->input('service_ids', []));

        if (empty($serviceIds)) {
            // No services selected — return all active doctors
            $doctors = User::role('Doctor')
                ->active()
                ->with('department:id,name')
                ->orderBy('first_name')
                ->get();

            return response()->json($this->formatDoctors($doctors));
        }

        // Collect specialty IDs from the selected services
        $specialtyIds = ServiceCatalog::whereIn('id', $serviceIds)
            ->with('specialties:id')
            ->get()
            ->pluck('specialties')
            ->flatten()
            ->pluck('id')
            ->unique()
            ->toArray();

        if (empty($specialtyIds)) {
            // Services have no specialties assigned yet — fall back to department-routed doctors
            $departmentIds = ServiceCatalog::whereIn('id', $serviceIds)
                ->whereNotNull('department_id')
                ->pluck('department_id')
                ->unique()
                ->toArray();

            $doctors = User::role('Doctor')
                ->active()
                ->when(!empty($departmentIds), fn ($q) => $q->whereIn('department_id', $departmentIds))
                ->with('department:id,name')
                ->orderBy('first_name')
                ->get();

            return response()->json($this->formatDoctors($doctors, note: 'fallback'));
        }

        // Match doctors whose specialties intersect the service specialties
        $doctors = User::role('Doctor')
            ->active()
            ->whereHas('specialties', fn ($q) => $q->whereIn('specialties.id', $specialtyIds))
            ->with(['department:id,name', 'specialties:id,name'])
            ->orderBy('first_name')
            ->get();

        return response()->json($this->formatDoctors($doctors));
    }

    /**
     * GET /admin/visits/ajax/doctors/{doctor}/services
     *
     * Returns services linked to a doctor's specialties.
     */
    public function servicesByDoctor(User $doctor): JsonResponse
    {
        $specialtyIds = $doctor->specialties()->pluck('specialties.id')->toArray();

        if (empty($specialtyIds)) {
            // Doctor has no specialties — return all active services
            $services = ServiceCatalog::active()->orderBy('name')->get(['id', 'name', 'code', 'price', 'department_id']);
        } else {
            $services = ServiceCatalog::active()
                ->whereHas('specialties', fn ($q) => $q->whereIn('specialties.id', $specialtyIds))
                ->with('department:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'price', 'department_id']);
        }

        return response()->json($services->map(fn ($s) => [
            'id'              => $s->id,
            'name'            => $s->name,
            'code'            => $s->code,
            'price'           => (float) $s->price,
            'formatted_price' => '₵' . number_format($s->price, 2),
            'department_id'   => $s->department_id,
            'department_name' => $s->department?->name ?? '—',
        ]));
    }

    /**
     * POST /admin/visits/ajax/calculate-services
     *
     * Calculates insurance-adjusted pricing for a set of services.
     *
     * Body: { patient_id, insurance_id (optional), services: [{id, quantity}] }
     */
    public function calculateServices(Request $request): JsonResponse
    {
        $request->validate([
            'patient_id'           => ['required', 'exists:patients,id'],
            'insurance_id'         => ['nullable', 'exists:patient_insurances,id'],
            'services'             => ['required', 'array', 'min:1'],
            'services.*.id'        => ['required', 'exists:service_catalog,id'],
            'services.*.quantity'  => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $patient   = Patient::findOrFail($request->patient_id);
        $insurance = $this->insuranceService->resolveForPatient($patient, $request->insurance_id);
        $insData   = $this->insuranceService->getDisplayData($insurance);

        $lines = [];
        foreach ($request->services as $svc) {
            $service  = ServiceCatalog::findOrFail($svc['id']);
            $quantity = max(1, (int) ($svc['quantity'] ?? 1));
            $lines[]  = $this->insuranceService->calculateServicePricing($service, $insurance, $quantity);
        }

        $totals = $this->insuranceService->calculateTotal($lines);

        return response()->json([
            'insurance' => $insData,
            'lines'     => $lines,
            'totals'    => $totals,
        ]);
    }

    /**
     * GET /admin/patients/{patient}/insurance-status
     *
     * Returns all insurances for a patient with validity and limit data.
     */
    public function patientInsuranceStatus(Patient $patient): JsonResponse
    {
        $all      = $this->insuranceService->getAllForPatient($patient);
        $resolved = $this->insuranceService->resolveForPatient($patient);

        return response()->json([
            'insurances'           => $all,
            'resolved_insurance_id'=> $resolved->id,
            'resolved_display'     => $this->insuranceService->getDisplayData($resolved),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function formatDoctors($doctors, string $note = ''): array
    {
        return $doctors->map(fn ($d) => [
            'id'              => $d->id,
            'name'            => 'Dr. ' . $d->full_name,
            'department'      => $d->department?->name ?? '—',
            'specialties'     => isset($d->specialties)
                                     ? $d->specialties->pluck('name')->implode(', ')
                                     : '',
            'note'            => $note,
        ])->values()->toArray();
    }
}
