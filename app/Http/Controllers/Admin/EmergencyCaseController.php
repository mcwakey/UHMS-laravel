<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DepartmentType;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\EmergencyBay;
use App\Models\EmergencyCase;
use App\Models\MedicationFrequency;
use App\Models\Patient;
use App\Models\Product;
use App\Models\ServiceCatalog;
use App\Models\StockLocation;
use App\Models\User;
use App\Services\EmergencyCaseService;
use Illuminate\Http\Request;

class EmergencyCaseController extends Controller
{
    public function __construct(private EmergencyCaseService $cases) {}

    public function create(Request $request)
    {
        return view('emergency.create', [
            'patients' => Patient::query()
                ->when($request->query('search'), fn ($q, $search) => $q->search($search))
                ->latest()
                ->limit(20)
                ->get(),
            'bays' => EmergencyBay::available()->orderBy('name')->get(),
            'users' => User::where('status', 'active')->orderBy('first_name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id' => ['nullable', 'exists:patients,id', 'required_without:temporary_display_name'],
            'temporary_display_name' => ['nullable', 'string', 'max:120', 'required_without:patient_id'],
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
        ]);

        $case = $this->cases->create($data, $request->user());

        return redirect()
            ->route('admin.emergency.cases.show', $case)
            ->with('success', "Emergency case {$case->emergency_number} created.");
    }

    public function show(EmergencyCase $emergencyCase)
    {
        $emergencyCase->load([
            'patient',
            'visit.latestInvoice.items',
            'bay',
            'assignedDoctor',
            'assignedNurse',
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
            'clinicalTasks',
        ]);

        return view('emergency.show', [
            'case' => $emergencyCase,
            'bays' => EmergencyBay::active()->orderBy('name')->get(),
            'users' => User::where('status', 'active')->orderBy('first_name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->limit(100)->get(),
            'frequencies' => MedicationFrequency::where('is_active', true)->orderBy('code')->get(),
            'stockLocations' => StockLocation::active()->whereIn('type', ['emergency', 'ward'])->orderBy('name')->get(),
            'services' => ServiceCatalog::active()->orderBy('name')->limit(100)->get(),
            'investigationDepartments' => Department::acceptsRequests()->orderBy('name')->get(),
            'procedureDepartments' => Department::where('type', DepartmentType::PROCEDURE->value)->where('status', 'active')->orderBy('name')->get(),
            'procedureServices' => ServiceCatalog::active()->where('category', 'procedure')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, EmergencyCase $emergencyCase)
    {
        $data = $request->validate([
            'assigned_doctor_id' => ['nullable', 'exists:users,id'],
            'assigned_nurse_id' => ['nullable', 'exists:users,id'],
            'emergency_status' => ['nullable', 'in:ARRIVED,WAITING_TRIAGE,TRIAGED,UNDER_EMERGENCY_CARE,OBSERVATION,READY_FOR_DISPOSITION,CANCELLED'],
        ]);

        $emergencyCase->update($data);

        return back()->with('success', 'Emergency case updated.');
    }
}
