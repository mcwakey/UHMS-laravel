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
        // Accept a single service_id or an array (multi-select) interchangeably.
        $request->merge([
            'service_id' => array_values(array_filter(
                \Illuminate\Support\Arr::wrap($request->input('service_id')),
                fn ($v) => $v !== null && $v !== '',
            )),
        ]);

        $data = $request->validate([
            'target_department_id' => ['required', 'exists:departments,id'],
            'service_id' => ['required', 'array', 'min:1'],
            'service_id.*' => [
                Rule::exists('service_catalog', 'id')->where(fn ($query) => $query
                    ->where('is_active', true)
                    ->where('department_id', $request->input('target_department_id'))),
            ],
            'test_name' => ['nullable', 'string', 'max:180'],
            'clinical_info' => ['nullable', 'string', 'max:2000'],
            'urgency' => ['nullable', 'in:routine,urgent,emergency'],
        ]);

        $this->investigations->request($emergencyCase, $data, $request->user());

        return back()->with('success', 'Emergency investigation(s) requested.');
    }
}
