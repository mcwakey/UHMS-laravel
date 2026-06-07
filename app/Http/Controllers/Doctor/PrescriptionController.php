<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use App\Services\PharmacyBillingSelectionService;
use App\Services\PharmacyService;
use App\Services\PrescriptionService;
use Illuminate\Http\Request;

class PrescriptionController extends Controller
{
    public function __construct(
        protected PrescriptionService $prescriptionService,
        protected PharmacyService $pharmacyService,
        protected PharmacyBillingSelectionService $billingSelections,
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

        // Enrich with pharmacy billing data so the prescription page can bill items.
        // Guarded: pharmacy may be unconfigured, or the prescription may have no visit.
        $billingError = null;
        if ($prescription->visit) {
            try {
                $prescription = $this->pharmacyService->getDispensingDetails($prescription);
            } catch (\Throwable $e) {
                $billingError = $e->getMessage();
            }
        }

        return view('prescriptions.show', compact('prescription', 'billingError'));
    }

    /**
     * Bill selected prescription items (moved here from the dispensing screen).
     * Dispensing is only allowed after these bills are settled.
     */
    public function bill(Request $request, Prescription $prescription)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.selected' => 'nullable|boolean',
            'items.*.quantity' => 'nullable|integer|min:0',
            'items.*.notes' => 'nullable|string|max:1000',
        ]);

        try {
            $created = $this->billingSelections->billSelectedItems($prescription, $validated['items']);

            return back()->with('success', $created->count().' item(s) billed. Collect payment, then dispense at the pharmacy.');
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
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
