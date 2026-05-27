<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MedicationAdministration;
use App\Models\MedicationAdministrationSchedule;
use App\Models\MedicationOrder;
use App\Services\MedicationAdministrationService;
use App\Services\MedicationOrderService;
use Illuminate\Http\Request;

class MedicationAdministrationController extends Controller
{
    public function __construct(
        private MedicationAdministrationService $administrations,
        private MedicationOrderService $orders,
    ) {}

    public function administerSchedule(Request $request, MedicationAdministrationSchedule $schedule)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:GIVEN,PARTIALLY_GIVEN,MISSED,HELD,REFUSED,SKIPPED,NOT_GIVEN'],
            'administered_at' => ['nullable', 'date'],
            'dose_given' => ['nullable', 'string', 'max:120'],
            'dose_unit' => ['nullable', 'string', 'max:40'],
            'route' => ['nullable', 'string', 'max:80'],
            'reason_not_given' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'reaction' => ['nullable', 'string', 'max:2000'],
            'source_stock_type' => ['required', 'in:PATIENT_DISPENSED_STOCK,WARD_STOCK,EMERGENCY_STOCK,OTHER_DEPARTMENT_STOCK'],
            'stock_location_id' => ['nullable', 'exists:stock_locations,id'],
            'witnessed_by' => ['nullable', 'exists:users,id'],
        ]);

        $administration = $this->administrations->administerSchedule($schedule, $validated, $request->user());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Medication administration recorded.',
                'administration_id' => $administration->id,
            ]);
        }

        return back()->with('success', 'Medication administration recorded.');
    }

    public function administerPrn(Request $request, MedicationOrder $order)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:GIVEN,PARTIALLY_GIVEN,MISSED,HELD,REFUSED,SKIPPED,NOT_GIVEN'],
            'administered_at' => ['nullable', 'date'],
            'dose_given' => ['required_if:status,GIVEN,PARTIALLY_GIVEN', 'nullable', 'string', 'max:120'],
            'route' => ['nullable', 'string', 'max:80'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'reason_not_given' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'reaction' => ['nullable', 'string', 'max:2000'],
            'source_stock_type' => ['required', 'in:PATIENT_DISPENSED_STOCK,WARD_STOCK,EMERGENCY_STOCK,OTHER_DEPARTMENT_STOCK'],
            'stock_location_id' => ['nullable', 'exists:stock_locations,id'],
            'witnessed_by' => ['nullable', 'exists:users,id'],
        ]);

        $administration = $this->administrations->administerPrn($order, $validated, $request->user());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'PRN/SOS medication administration recorded.',
                'administration_id' => $administration->id,
            ]);
        }

        return back()->with('success', 'PRN/SOS medication administration recorded.');
    }

    public function holdOrder(Request $request, MedicationOrder $order)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $this->orders->hold($order, $request->user(), $validated['reason']);

        return back()->with('success', 'Medication order held.');
    }

    public function stopOrder(Request $request, MedicationOrder $order)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $this->orders->stop($order, $request->user(), $validated['reason']);

        return back()->with('success', 'Medication order stopped and future doses cancelled.');
    }

    public function correct(Request $request, MedicationAdministration $administration)
    {
        $validated = $request->validate([
            'dose_given' => ['nullable', 'string', 'max:120'],
            'route' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', 'in:GIVEN,PARTIALLY_GIVEN,MISSED,HELD,REFUSED,SKIPPED,NOT_GIVEN'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'reaction' => ['nullable', 'string', 'max:2000'],
            'correction_reason' => ['required', 'string', 'max:2000'],
        ]);

        $this->administrations->correct($administration, $validated, $request->user());

        return back()->with('success', 'Administration record corrected with audit log.');
    }
}
