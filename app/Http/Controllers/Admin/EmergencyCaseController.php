<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DepartmentType;
use App\Enums\ProductType;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\EmergencyBay;
use App\Models\EmergencyCase;
use App\Models\MedicationFrequency;
use App\Models\Patient;
use App\Models\Product;
use App\Models\ServiceCatalog;
use App\Models\StockBalance;
use App\Models\StockLocation;
use App\Models\User;
use App\Models\Visit;
use App\Models\Ward;
use App\Services\EmergencyCaseService;
use App\Services\EmergencySessionService;
use App\Services\PatientComplaintService;
use Illuminate\Http\Request;

class EmergencyCaseController extends Controller
{
    public function __construct(
        private EmergencyCaseService $cases,
        private EmergencySessionService $sessions,
        private PatientComplaintService $patientComplaints,
    ) {}

    public function create(Request $request)
    {
        $existingVisit = $request->query('visit_id')
            ? Visit::with('patient')->find($request->query('visit_id'))
            : null;

        $patients = Patient::query()
            ->when($request->query('search'), fn ($q, $search) => $q->search($search))
            ->latest()
            ->limit(20)
            ->get();

        if ($existingVisit?->patient && ! $patients->contains('id', $existingVisit->patient_id)) {
            $patients->prepend($existingVisit->patient);
        }

        return view('emergency.create', [
            'patients' => $patients,
            'bays' => EmergencyBay::available()->orderBy('name')->get(),
            'users' => User::where('status', 'active')->orderBy('first_name')->get(),
            'existingVisit' => $existingVisit,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id' => ['nullable', 'exists:patients,id', 'required_without_all:temporary_display_name,visit_id'],
            'temporary_display_name' => ['nullable', 'string', 'max:120', 'required_without_all:patient_id,visit_id'],
            'temporary_gender' => ['nullable', 'in:male,female'],
            'estimated_age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'temporary_reason' => ['nullable', 'string', 'max:500'],
            'arrival_mode' => ['required', 'in:WALK_IN,AMBULANCE,POLICE,FAMILY_BROUGHT,REFERRAL,TRANSFER_FROM_OPD,TRANSFER_FROM_WARD,UNKNOWN'],
            'arrival_time' => ['required', 'date'],
            'brought_by' => ['nullable', 'string', 'max:120'],
            'source' => ['nullable', 'string', 'max:120'],
            'referral_facility' => ['nullable', 'string', 'max:180'],
            'chief_complaint' => ['nullable', 'string', 'max:2000'],
            'initial_condition' => ['nullable', 'string', 'max:2000'],
            'emergency_bay_id' => ['nullable', 'exists:emergency_bays,id'],
            'assigned_doctor_id' => ['nullable', 'exists:users,id'],
            'assigned_nurse_id' => ['nullable', 'exists:users,id'],
            'visit_id' => ['nullable', 'exists:visits,id'],
        ]);

        try {
            $case = $this->cases->create($data, $request->user());
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['patient_id' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.emergency.cases.show', $case)
            ->with('success', "Emergency case {$case->emergency_number} created.");
    }

    public function show(EmergencyCase $emergencyCase)
    {
        $this->sessions->getOrCreateForCase($emergencyCase, request()->user());

        $emergencyCase->load([
            'patient',
            'visit.visitInsurance.insuranceProvider',
            'visit.latestInvoice.items.creator',
            'visit.latestInvoice.payments',
            'bay.ward',
            'bay.bed',
            'assignedDoctor',
            'assignedNurse',
            'activeEmergencySession.mainDoctor',
            'activeEmergencySession.primaryNurse',
            'activeEmergencySession.department',
            'activeEmergencySession.startedBy',
            'activeEmergencySession.contributors.user',
            'activeBayAssignment.ward',
            'activeBayAssignment.bed',
            'activeBayAssignment.emergencyBay',
            'triagedBy',
            'disposedBy',
            'latestVitals.recordedBy',
            'vitals.recordedBy',
            'notes.creator',
            'logs.performedBy',
            'medicationOrders.product',
            'medicationOrders.frequency',
            'medicationOrders.prescriber',
            'medicationOrders.schedules.clinicalTask',
            'medicationOrders.administrations.administeredBy',
            'labRequests.requestedBy',
            'labRequests.targetDepartment',
            'labRequests.items.labTest',
            'procedureRequests.service',
            'procedureRequests.department',
            'procedureRequests.requestingDoctor',
            'procedureRequests.billingItem',
            'clinicalTasks.assignedUser',
            'clinicalTasks.completedBy',
            'consumableUsages.product',
            'consumableUsages.stockLocation',
            'consumableUsages.invoiceItem',
            'consumableUsages.user',
        ]);

        $vitalsChartRows = $emergencyCase->vitals->sortBy('recorded_at')->values()->map(fn ($vital) => [
            'label' => $vital->recorded_at?->format('d M H:i'),
            'systolic' => $vital->blood_pressure_systolic !== null ? (int) $vital->blood_pressure_systolic : null,
            'diastolic' => $vital->blood_pressure_diastolic !== null ? (int) $vital->blood_pressure_diastolic : null,
            'heart_rate' => $vital->heart_rate !== null ? (int) $vital->heart_rate : null,
            'temperature' => $vital->temperature !== null ? (float) $vital->temperature : null,
            'spo2' => $vital->spo2 !== null ? (int) $vital->spo2 : null,
            'respiratory_rate' => $vital->respiratory_rate !== null ? (int) $vital->respiratory_rate : null,
        ]);

        $vitalsChartData = [
            'labels' => $vitalsChartRows->pluck('label')->all(),
            'systolic' => $vitalsChartRows->pluck('systolic')->all(),
            'diastolic' => $vitalsChartRows->pluck('diastolic')->all(),
            'heart_rate' => $vitalsChartRows->pluck('heart_rate')->all(),
            'temperature' => $vitalsChartRows->pluck('temperature')->all(),
            'spo2' => $vitalsChartRows->pluck('spo2')->all(),
            'respiratory_rate' => $vitalsChartRows->pluck('respiratory_rate')->all(),
        ];

        $billingGroups = $this->groupEmergencyBillingItems($emergencyCase);

        $medicationProducts = $this->productsWithStock([ProductType::DRUG->value]);
        $consumableProducts = $this->productsWithStock([
            ProductType::CONSUMABLE->value,
            ProductType::SURGICAL_SUPPLY->value,
            ProductType::MEDICAL_SUPPLY->value,
            ProductType::SUPPLY->value,
            ProductType::GENERAL_ITEM->value,
        ]);

        $investigationDepartments = Department::acceptsRequests()->orderBy('name')->get();
        $procedureDepartments = Department::where('type', DepartmentType::PROCEDURE->value)->where('status', 'active')->orderBy('name')->get();
        $emergencyDepartmentId = $this->emergencyDepartmentId($emergencyCase);

        return view('emergency.show', [
            'case' => $emergencyCase,
            'bays' => EmergencyBay::active()->with(['ward', 'bed'])->orderBy('name')->get(),
            'wards' => Ward::active()->with(['beds' => fn ($query) => $query->orderBy('bed_number')])->orderBy('name')->get(),
            'users' => User::where('status', 'active')->orderBy('first_name')->get(),
            'products' => $medicationProducts,
            'consumableProducts' => $consumableProducts,
            'frequencies' => MedicationFrequency::where('is_active', true)->orderBy('code')->get(),
            'stockLocations' => StockLocation::active()->whereIn('type', ['emergency', 'ward'])->orderBy('name')->get(),
            'services' => ServiceCatalog::active()
                ->when($emergencyDepartmentId !== null, fn ($query) => $query->where('department_id', $emergencyDepartmentId), fn ($query) => $query->whereRaw('1 = 0'))
                ->orderBy('name')
                ->limit(100)
                ->get(),
            'investigationDepartments' => $investigationDepartments,
            'investigationServices' => ServiceCatalog::active()->whereIn('department_id', $investigationDepartments->pluck('id'))->orderBy('name')->get(),
            'procedureDepartments' => $procedureDepartments,
            'procedureServices' => ServiceCatalog::active()->whereIn('department_id', $procedureDepartments->pluck('id'))->orderBy('name')->get(),
            'vitalsChartData' => $vitalsChartData,
            'billingGroups' => $billingGroups,
            'identityCandidates' => Patient::active()
                ->where('is_temporary', false)
                ->where('id', '!=', $emergencyCase->patient_id)
                ->orderByDesc('created_at')
                ->limit(50)
                ->get(),
        ]);
    }

    public function update(Request $request, EmergencyCase $emergencyCase)
    {
        $data = $request->validate([
            'arrival_mode' => ['nullable', 'in:WALK_IN,AMBULANCE,POLICE,FAMILY_BROUGHT,REFERRAL,TRANSFER_FROM_OPD,TRANSFER_FROM_WARD,UNKNOWN'],
            'arrival_time' => ['nullable', 'date'],
            'brought_by' => ['nullable', 'string', 'max:120'],
            'source' => ['nullable', 'string', 'max:120'],
            'referral_facility' => ['nullable', 'string', 'max:180'],
            'chief_complaint' => ['nullable', 'string', 'max:2000'],
            'initial_condition' => ['nullable', 'string', 'max:2000'],
            'assigned_doctor_id' => ['nullable', 'exists:users,id'],
            'assigned_nurse_id' => ['nullable', 'exists:users,id'],
            'emergency_status' => ['nullable', 'in:ARRIVED,WAITING_TRIAGE,TRIAGED,UNDER_EMERGENCY_CARE,OBSERVATION,READY_FOR_DISPOSITION,CANCELLED'],
        ]);

        $emergencyCase->update($data);

        if (array_key_exists('chief_complaint', $data) || array_key_exists('initial_condition', $data)) {
            $emergencyCase->visit?->update([
                'chief_complaint' => $data['chief_complaint'] ?? $emergencyCase->visit?->chief_complaint,
                'notes' => $data['initial_condition'] ?? $emergencyCase->visit?->notes,
            ]);
        }

        $session = $this->sessions->syncTeam($emergencyCase->fresh(['activeEmergencySession']));
        if (array_key_exists('chief_complaint', $data)) {
            $session?->loadMissing('medicalRecord');
            if ($session?->medicalRecord) {
                $this->patientComplaints->syncEmergencyChiefComplaint($emergencyCase->fresh(), $session->medicalRecord, $request->user());
            }
        }

        return back()->with('success', 'Emergency case updated.');
    }

    private function emergencyDepartmentId(EmergencyCase $case): ?int
    {
        $case->loadMissing(['visit', 'activeEmergencySession']);

        return $case->visit?->current_department_id
            ?: $case->activeEmergencySession?->department_id
            ?: Department::query()
                ->whereIn('code', ['ER', 'EMR'])
                ->orWhere('name', 'like', '%Emergency%')
                ->value('id');
    }

    private function groupEmergencyBillingItems(EmergencyCase $case): array
    {
        $items = $case->visit?->latestInvoice?->items ?? collect();

        return $items->groupBy(function ($item) {
            return match ($item->source_type) {
                'emergency_service' => 'Emergency Services',
                'emergency_medication_order' => 'Emergency Medications',
                'emergency_consumable' => 'Emergency Consumables',
                'investigation_service', 'emergency_investigation' => 'Emergency Investigations',
                'procedure_service', 'emergency_procedure' => 'Emergency Procedures',
                default => str_starts_with((string) $item->source_type, 'emergency') ? 'Other Emergency Charges' : 'Visit Charges',
            };
        })->all();
    }

    private function productsWithStock(array $productTypes)
    {
        $products = Product::query()
            ->where('is_active', true)
            ->whereIn('product_type', $productTypes)
            ->orderBy('name')
            ->limit(150)
            ->get();

        $productIds = $products->pluck('id')->all();
        $locationIdsByType = StockLocation::active()
            ->whereIn('type', ['emergency', 'pharmacy', 'store'])
            ->get(['id', 'type', 'is_main'])
            ->groupBy(fn ($location) => $location->is_main ? 'main' : $location->type)
            ->map(fn ($locations) => $locations->pluck('id')->all());

        $balances = empty($productIds)
            ? collect()
            : StockBalance::query()
                ->whereIn('product_id', $productIds)
                ->selectRaw('product_id, stock_location_id, SUM(quantity_on_hand) as quantity')
                ->groupBy('product_id', 'stock_location_id')
                ->get()
                ->groupBy('product_id');

        return $products->map(function (Product $product) use ($balances, $locationIdsByType) {
            $rows = $balances->get($product->id, collect());
            $sumFor = fn (string $type): float => (float) $rows
                ->whereIn('stock_location_id', $locationIdsByType->get($type, []))
                ->sum('quantity');

            $product->emergency_available_quantity = $sumFor('emergency');
            $product->pharmacy_available_quantity = $sumFor('pharmacy');
            $product->main_available_quantity = $sumFor('main') + $sumFor('store');

            return $product;
        });
    }
}
