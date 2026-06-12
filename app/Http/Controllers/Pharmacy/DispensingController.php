<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Services\PharmacyBillingSelectionService;
use App\Services\PharmacyService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DispensingController extends Controller
{
    public function __construct(
        protected PharmacyService $pharmacyService,
        protected PharmacyBillingSelectionService $billingSelections,
    ) {}

    /**
     * Dispensing queue — pending prescriptions.
     */
    public function index(Request $request)
    {
        $prescriptions = $this->pharmacyService->getPendingPrescriptions([
            'search' => $request->search,
        ]);

        $stats = $this->pharmacyService->getPharmacyStats();

        $prescriptionsPayload = $prescriptions->through(function (Prescription $rx) {
            $items = $rx->items;
            $dispensed = $items ? $items->where('is_dispensed', true)->count() : 0;
            return [
                'id'                  => $rx->id,
                'prescription_number' => $rx->prescription_number,
                'patient' => $rx->patient ? [
                    'full_name'      => $rx->patient->full_name,
                    'patient_number' => $rx->patient->patient_number,
                ] : null,
                'doctor_name' => optional($rx->doctor)->name,
                'items_count' => $items ? $items->count() : 0,
                'dispensed_count' => $dispensed,
                'status' => $rx->status ? [
                    'value' => $rx->status->value,
                    'label' => $rx->status->label(),
                    'color' => $rx->status->color(),
                ] : null,
                'created_at_date' => optional($rx->created_at)->format('d M Y'),
                'created_at_time' => optional($rx->created_at)->format('H:i'),
                'urls' => [
                    'show' => route('admin.pharmacy.dispensing.show', $rx),
                ],
            ];
        });

        return Inertia::render('Pharmacy/Dispensing', [
            'prescriptions' => $prescriptionsPayload,
            'stats'         => $stats,
            'filters'       => $request->only(['search']),
            'routes' => [
                'index'   => route('admin.pharmacy.dispensing.index'),
                'history' => route('admin.pharmacy.history'),
            ],
        ]);
    }

    /**
     * Show dispensing form for a prescription.
     */
    public function show(Prescription $prescription)
    {
        $prescription = $this->pharmacyService->getDispensingDetails($prescription);

        return view('pharmacy.dispense', compact('prescription'));
    }

    /**
     * Dispense a single prescription item.
     */
    public function dispenseItem(Request $request, PrescriptionItem $item)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:2000',
        ]);

        try {
            $this->pharmacyService->dispenseItem($item, $validated['quantity'], $validated['notes'] ?? null);
            return back()->with('success', __('messages.pharmacy.item_dispensed'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Batch dispense all items in a prescription.
     */
    public function batchDispense(Request $request, Prescription $prescription)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.quantity' => 'nullable|integer|min:0',
            'items.*.notes' => 'nullable|string|max:2000',
        ]);

        try {
            $this->pharmacyService->batchDispense($prescription, $validated['items']);
            return back()->with('success', __('messages.pharmacy.items_dispensed'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Dispensing history.
     */
    public function history(Request $request)
    {
        $records = $this->pharmacyService->getDispensingHistory([
            'search' => $request->search,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
        ]);

        return view('pharmacy.history', compact('records'));
    }
}
