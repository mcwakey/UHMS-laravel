<?php

namespace App\Http\Controllers\Admin\Emergency;

use App\Http\Controllers\Controller;
use App\Models\EmergencyCase;
use App\Services\EmergencyDispositionService;
use Illuminate\Http\Request;

class EmergencyDispositionController extends Controller
{
    public function __construct(private EmergencyDispositionService $disposition) {}

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
            return redirect()
                ->route('admin.admissions.create', ['visit_id' => $case->visit_id])
                ->with('success', __('messages.emergency.case_marked_for_admission'));
        }

        return back()->with('success', __('messages.emergency.disposition_recorded'));
    }
}
