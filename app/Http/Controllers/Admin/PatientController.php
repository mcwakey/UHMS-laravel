<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Models\Patient;
use App\Services\PatientService;
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

    public function show(Patient $patient)
    {
        $patient->load('registeredBy');

        return view('patients.show', compact('patient'));
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
