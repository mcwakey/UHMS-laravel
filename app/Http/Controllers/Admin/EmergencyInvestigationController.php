<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmergencyCase;
use App\Services\EmergencyInvestigationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmergencyInvestigationController extends Controller
{
    public function __construct(private EmergencyInvestigationService $investigations) {}

    public function store(Request $request, EmergencyCase $emergencyCase)
    {
        $data = $request->validate([
            'target_department_id' => ['required', 'exists:departments,id'],
            'service_id' => [
                'required',
                Rule::exists('service_catalog', 'id')->where(fn ($query) => $query
                    ->where('is_active', true)
                    ->where('department_id', $request->input('target_department_id'))),
            ],
            'test_name' => ['nullable', 'string', 'max:180'],
            'clinical_info' => ['nullable', 'string', 'max:2000'],
            'urgency' => ['nullable', 'in:routine,urgent,emergency'],
        ]);

        $this->investigations->request($emergencyCase, $data, $request->user());

        return back()->with('success', 'Emergency investigation requested.');
    }
}
