<?php

namespace App\Http\Controllers\Admin\Emergency;

use App\Http\Controllers\Controller;
use App\Models\EmergencyCase;
use App\Services\EmergencyNoteService;
use Illuminate\Http\Request;

class EmergencyNoteController extends Controller
{
    public function __construct(private EmergencyNoteService $notes) {}

    public function store(Request $request, EmergencyCase $emergencyCase)
    {
        $data = $request->validate([
            'note_type' => ['required', 'in:DOCTOR_ASSESSMENT,NURSING_NOTE,RESUSCITATION_NOTE,OBSERVATION_NOTE,GENERAL_NOTE'],
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $this->notes->create($emergencyCase, $data, $request->user());

        return back()->with('success', __('messages.emergency.note_added'));
    }
}
