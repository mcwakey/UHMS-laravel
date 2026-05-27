<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmergencyCase;
use App\Services\EmergencyInvestigationService;
use Illuminate\Http\Request;

class EmergencyInvestigationController extends Controller
{
    public function __construct(private EmergencyInvestigationService $investigations) {}

    public function store(Request $request, EmergencyCase $emergencyCase)
    {
        $data = $request->validate([
            'test_name' => ['required', 'string', 'max:180'],
            'target_department_id' => ['nullable', 'exists:departments,id'],
            'clinical_info' => ['nullable', 'string', 'max:2000'],
            'urgency' => ['nullable', 'in:routine,urgent,emergency'],
        ]);

        $this->investigations->request($emergencyCase, $data, $request->user());

        return back()->with('success', 'Emergency investigation requested.');
    }
}
