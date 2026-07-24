<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePrescriptionForVisitRequest;
use App\Http\Requests\StorePrescriptionRequest;
use App\Models\Drug;
use App\Models\MedicalRecord;
use App\Models\Prescription;
use App\Models\Visit;
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
        $filters = $request->all();

        if ($this->shouldScopeToLoggedInDoctor($request)) {
            $filters['doctor_id'] = $request->user()->id;
        }

        $prescriptions = $this->prescriptionService->list($filters);

        $drugs = collect();
        if ($request->user()?->can('prescriptions.create')) {
            $drugs = Drug::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'generic_name', 'strength', 'dosage_form', 'unit']);
        }

        return view('prescriptions.index', compact('prescriptions', 'drugs'));
    }

    /**
     * Show prescription details.
     */
    public function show(Prescription $prescription)
    {
        $this->authorizeDoctorWorkspacePrescription($prescription);

        $prescription->load(['patient', 'doctor', 'visit', 'items', 'medicalRecord']);
        $drugs = Drug::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'generic_name', 'strength', 'dosage_form', 'unit']);

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

        return view('prescriptions.show', compact('prescription', 'billingError', 'drugs'));
    }

    public function print(Prescription $prescription)
    {
        $this->authorizeDoctorWorkspacePrescription($prescription);

        $prescription->load(['patient', 'doctor', 'visit.patient', 'items.drug']);

        return view('reports.print-prescription', compact('prescription'));
    }

    /**
     * Search visits (AJAX) so a new Rx can be started for a patient/visit
     * that isn't already open — used by the "New Rx" modal on the
     * prescriptions index page.
     */
    public function visitSearch(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $visits = Visit::query()
            ->with('patient:id,patient_number,first_name,last_name')
            ->when($q !== '', function ($query) use ($q) {
                $query->where('visit_number', 'like', "%{$q}%")
                    ->orWhereHas('patient', function ($p) use ($q) {
                        $p->where('first_name', 'like', "%{$q}%")
                            ->orWhere('last_name', 'like', "%{$q}%")
                            ->orWhere('patient_number', 'like', "%{$q}%");
                    });
            })
            ->latest('visit_date')
            ->limit(20)
            ->get();

        return response()->json($visits->map(fn ($v) => [
            'id' => $v->id,
            'visit_number' => $v->visit_number,
            'patient_name' => $v->patient?->full_name,
            'patient_number' => $v->patient?->patient_number,
            'visit_date' => $v->visit_date?->format('d M Y'),
        ]));
    }

    /**
     * Start a brand-new prescription for a chosen visit, from the
     * prescriptions index page (no active consultation session required).
     */
    public function store(StorePrescriptionForVisitRequest $request)
    {
        $visit = Visit::findOrFail($request->validated('visit_id'));

        $record = MedicalRecord::firstOrCreate(
            ['visit_id' => $visit->id, 'consultation_route_id' => null],
            [
                'patient_id' => $visit->patient_id,
                'doctor_id' => $request->user()->id,
                'department_id' => $visit->department_id,
            ]
        );

        $prescription = $this->prescriptionService->create($record, $request->validated());

        return redirect()
            ->route('admin.prescriptions.show', $prescription)
            ->with('success', __('messages.consultations.prescription_created', ['number' => $prescription->prescription_number]));
    }

    public function storeAnother(StorePrescriptionRequest $request, Prescription $prescription)
    {
        $prescription->loadMissing('medicalRecord');

        abort_unless($prescription->medicalRecord, 422, 'This prescription is not linked to a medical record.');

        $newPrescription = $this->prescriptionService->create($prescription->medicalRecord, $request->validated());

        return redirect()
            ->route('admin.prescriptions.show', $newPrescription)
            ->with('success', __('messages.consultations.prescription_created', ['number' => $newPrescription->prescription_number]));
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

            return back()->with('success', __('messages.prescriptions.billed', ['count' => $created->count()]));
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

        return back()->with('success', __('messages.prescriptions.cancelled', ['number' => $prescription->prescription_number]));
    }

    private function shouldScopeToLoggedInDoctor(Request $request): bool
    {
        return $request->routeIs('doctor.*') && ! ($request->user()?->hasRole('Super Admin') ?? false);
    }

    private function authorizeDoctorWorkspacePrescription(Prescription $prescription): void
    {
        $request = request();

        abort_if(
            $this->shouldScopeToLoggedInDoctor($request)
                && (int) $prescription->doctor_id !== (int) $request->user()?->id,
            404
        );
    }
}
