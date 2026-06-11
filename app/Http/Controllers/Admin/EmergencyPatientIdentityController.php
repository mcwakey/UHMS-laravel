<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Enums\BloodGroup;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Models\EmergencyCase;
use App\Models\Patient;
use App\Services\EmergencyPatientIdentityService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

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
                ->with('success', __('messages.emergency.identity_confirmed', ['number' => $mergeRequest->mainPatient->patient_number]));
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['confirmed_patient_id' => $e->getMessage()]);
        }
    }

    public function register(Request $request, EmergencyCase $emergencyCase)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'other_names' => ['nullable', 'string', 'max:100'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'gender' => ['required', new Enum(Gender::class)],
            'blood_group' => ['nullable', new Enum(BloodGroup::class)],
            'marital_status' => ['nullable', new Enum(MaritalStatus::class)],
            'phone' => ['required', 'string', 'max:20'],
            'phone_secondary' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:191'],
            'ghana_card_number' => ['nullable', 'string', 'max:30', 'unique:patients,ghana_card_number'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'town' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'digital_address' => ['nullable', 'string', 'max:30'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'confirmed' => ['accepted'],
        ]);

        $reason = $data['reason'] ?? null;
        unset($data['reason'], $data['confirmed']);

        try {
            $mergeRequest = $this->identityService->registerNewPatient(
                $emergencyCase->load('patient'),
                $data,
                $request->user(),
                $reason,
            );

            return redirect()
                ->route('admin.emergency.cases.show', $emergencyCase->fresh())
                ->with('success', __('messages.emergency.patient_registered', ['number' => $mergeRequest->mainPatient->patient_number]));
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['first_name' => $e->getMessage()]);
        }
    }
}
