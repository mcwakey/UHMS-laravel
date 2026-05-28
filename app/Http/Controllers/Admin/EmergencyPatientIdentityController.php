<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmergencyCase;
use App\Models\Patient;
use App\Services\EmergencyPatientIdentityService;
use Illuminate\Http\Request;

class EmergencyPatientIdentityController extends Controller
{
    public function __construct(private EmergencyPatientIdentityService $identityService) {}

    public function store(Request $request, EmergencyCase $emergencyCase)
    {
        $data = $request->validate([
            'confirmed_patient_id' => ['required', 'exists:patients,id'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'confirmed' => ['accepted'],
        ]);

        try {
            $confirmedPatient = Patient::findOrFail($data['confirmed_patient_id'])->getFinalPatient();
            $mergeRequest = $this->identityService->confirm(
                $emergencyCase->load('patient'),
                $confirmedPatient,
                $request->user(),
                $data['reason'] ?? null,
            );

            return redirect()
                ->route('admin.emergency.cases.show', $emergencyCase->fresh())
                ->with('success', "Emergency identity confirmed and merged under {$mergeRequest->mainPatient->patient_number}.");
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['confirmed_patient_id' => $e->getMessage()]);
        }
    }
}
