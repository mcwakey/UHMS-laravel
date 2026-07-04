<?php

namespace App\Http\Controllers\Admin\Emergency;

use App\Enums\AdmissionRequestSource;
use App\Http\Controllers\Controller;
use App\Models\EmergencyCase;
use App\Services\Admissions\AdmissionRequestService;
use App\Services\EmergencyDispositionService;
use Illuminate\Http\Request;

class EmergencyDispositionController extends Controller
{
    public function __construct(
        private EmergencyDispositionService $disposition,
        private AdmissionRequestService $admissionRequests,
    ) {}

    public function store(Request $request, EmergencyCase $emergencyCase)
    {
        $data = $request->validate([
            'disposition' => ['required', 'in:ADMITTED,DISCHARGED,TRANSFERRED_TO_OPD,TRANSFERRED_TO_THEATRE,REFERRED_OUT,LEFT_AGAINST_MEDICAL_ADVICE,ABSCONDED,DIED,DEAD_ON_ARRIVAL'],
            'disposition_notes' => ['nullable', 'required_if:disposition,REFERRED_OUT,LEFT_AGAINST_MEDICAL_ADVICE,DIED,DEAD_ON_ARRIVAL', 'string', 'max:3000'],
            'disposition_time' => ['nullable', 'date'],
            'cause_of_death' => ['nullable', 'string', 'max:255'],
        ]);

        $case = $this->disposition->dispose($emergencyCase, $data, $request->user());

        if ($case->disposition === EmergencyCase::DISPOSITION_ADMITTED) {
            $admissionRequest = $this->admissionRequests->createForVisit(
                $case->visit,
                AdmissionRequestSource::EMERGENCY,
                $case->id,
                [
                    'priority' => $case->triage_category,
                    'provisional_diagnosis' => $case->chief_complaint,
                    'clinical_summary' => $case->disposition_notes,
                ],
                $request->user()
            );

            return redirect()
                ->route('admin.admissions.create', [
                    'visit_id' => $case->visit_id,
                    'admission_request_id' => $admissionRequest->id,
                ])
                ->with('success', __('messages.emergency.case_marked_for_admission'));
        }

        return back()->with('success', __('messages.emergency.disposition_recorded'));
    }
}
