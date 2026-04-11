<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use App\Services\PrescriptionService;
use Illuminate\Http\Request;

class PrescriptionController extends Controller
{
    public function __construct(
        protected PrescriptionService $prescriptionService,
    ) {}

    /**
     * List all prescriptions.
     */
    public function index(Request $request)
    {
        $prescriptions = $this->prescriptionService->list($request->all());

        return view('prescriptions.index', compact('prescriptions'));
    }

    /**
     * Show prescription details.
     */
    public function show(Prescription $prescription)
    {
        $prescription->load(['patient', 'doctor', 'visit', 'items', 'medicalRecord']);

        return view('prescriptions.show', compact('prescription'));
    }

    /**
     * Cancel a prescription.
     */
    public function cancel(Prescription $prescription)
    {
        $this->prescriptionService->cancel($prescription);

        return back()->with('success', "Prescription {$prescription->prescription_number} cancelled.");
    }
}
