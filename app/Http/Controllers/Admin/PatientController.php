<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Models\InsuranceProvider;
use App\Models\Patient;
use App\Services\PatientService;
use App\Services\VisitService;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function __construct(
        private PatientService $patientService
    ) {}

    public function index(Request $request)
    {
        $patients = $this->patientService->list($request->all());

        return view('patients.index', compact('patients'));
    }

    public function create()
    {
        return view('patients.create');
    }

    public function store(StorePatientRequest $request)
    {
        $patient = $this->patientService->create($request->validated());

        return redirect()
            ->route('admin.patients.show', $patient)
            ->with('success', "Patient {$patient->patient_number} registered successfully.");
    }

    public function show(Patient $patient, Request $request)
    {
        // AJAX: Return patient insurances as JSON
        if ($request->ajax() && $request->get('format') === 'insurances') {
            $patient->load('insurances.insuranceProvider');
            return response()->json([
                'insurances' => $patient->insurances
                    ->where('is_active', true)
                    ->map(fn($ins) => [
                        'id' => $ins->id,
                        'provider_name' => $ins->insuranceProvider->name,
                        'membership_number' => $ins->membership_number,
                        'is_primary' => $ins->is_primary,
                        'is_expired' => $ins->is_expired,
                    ])->values(),
            ]);
        }

        $patient->load([
            'registeredBy',
            'insurances.insuranceProvider',
            'emergencyContacts',
        ]);

        // Load visits separately to avoid window-function queries on older MariaDB
        $visits = \App\Models\Visit::where('patient_id', $patient->id)
            ->with(['department', 'assignedDoctor', 'invoices'])
            ->latest('visit_date')
            ->take(20)
            ->get();
        $patient->setRelation('visits', $visits);

        $insuranceProviders = InsuranceProvider::where('is_active', true)->orderBy('name')->get();
        $upcomingVisits = app(VisitService::class)->upcomingForPatient($patient->id);

        return view('patients.show', compact('patient', 'insuranceProviders', 'upcomingVisits'));
    }

    public function edit(Patient $patient)
    {
        return view('patients.edit', compact('patient'));
    }

    public function update(UpdatePatientRequest $request, Patient $patient)
    {
        $this->patientService->update($patient, $request->validated());

        return redirect()
            ->route('admin.patients.show', $patient)
            ->with('success', 'Patient updated successfully.');
    }

    public function toggleStatus(Patient $patient)
    {
        $this->patientService->toggleStatus($patient);

        return back()->with('success', "Patient status changed to {$patient->status}.");
    }
}
